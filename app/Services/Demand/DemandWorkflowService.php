<?php

declare(strict_types=1);

namespace App\Services\Demand;

use App\Enums\DemandAttachmentCollection;
use App\Enums\DemandStatus;
use App\Enums\DemandValidatorStatus;
use App\Models\Demand;
use App\Models\DemandAttachment;
use App\Models\DemandEvent;
use App\Models\DemandValidator;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\MaterialNature;
use App\Models\User;
use App\Services\Drive\DriveWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DemandWorkflowService
{
    public const MIN_VALIDATORS = 3;

    /** Final regulatory group step — any member may approve; approval closes the demand. */
    public const ROLE_REGLEMENTAIRES = 'reglementaires';

    public function __construct(
        private readonly DemandNotificationService $notifications,
        private readonly DriveWorkflowService $drive,
    ) {}

    /**
     * @param  array{brand_id:int, material_nature_id?:int|null, material_nature_name?:string|null, description:string, validator_ids:array<int,int>, submit?:bool}  $data
     * @param  array<int, UploadedFile>  $natureFiles
     * @param  array<int, UploadedFile>  $referentielFiles
     */
    public function create(User $actor, array $data, array $natureFiles = [], array $referentielFiles = []): Demand
    {
        $validatorIds = $this->normalizeValidatorIds($data['validator_ids'] ?? []);

        return DB::transaction(function () use ($actor, $data, $validatorIds, $natureFiles, $referentielFiles): Demand {
            $materialNatureId = $this->resolveMaterialNatureId($data);

            $demand = Demand::query()->create([
                'reference' => $this->generateReference(),
                'created_by' => $actor->id,
                'brand_id' => $data['brand_id'],
                'material_nature_id' => $materialNatureId,
                'description' => $data['description'],
                'status' => DemandStatus::Draft,
                'current_step' => null,
            ]);

            $this->syncValidators($demand, $validatorIds);
            $this->storeAttachments($demand, $actor, DemandAttachmentCollection::NatureMateriel, $natureFiles);
            $this->storeAttachments($demand, $actor, DemandAttachmentCollection::ReferentielProduit, $referentielFiles);

            $this->log($demand, $actor, 'created', null, DemandStatus::Draft->value);

            if (! empty($data['submit'])) {
                $this->submit($demand, $actor);
            }

            return $demand->fresh(['validators.user', 'brand', 'materialNature', 'attachments', 'events.actor']) ?? $demand;
        });
    }

    /**
     * @param  array{brand_id?:int, material_nature_id?:int|null, material_nature_name?:string|null, description?:string, validator_ids?:array<int,int>, submit?:bool}  $data
     * @param  array<int, UploadedFile>  $natureFiles
     * @param  array<int, UploadedFile>  $referentielFiles
     * @param  array<int, int>  $removeAttachmentIds
     */
    public function update(Demand $demand, User $actor, array $data, array $natureFiles = [], array $referentielFiles = [], array $removeAttachmentIds = []): Demand
    {
        if (! $demand->status->isEditableByCreator()) {
            throw ValidationException::withMessages([
                'status' => __('demands.messages.cannot_edit_status'),
            ]);
        }

        if ($demand->created_by !== $actor->id && ! $actor->can('demands.view_all')) {
            throw ValidationException::withMessages([
                'demand' => __('demands.messages.cannot_edit'),
            ]);
        }

        return DB::transaction(function () use ($demand, $actor, $data, $natureFiles, $referentielFiles, $removeAttachmentIds): Demand {
            $updates = [];

            if (isset($data['brand_id'])) {
                $updates['brand_id'] = $data['brand_id'];
            }
            if (array_key_exists('material_nature_id', $data) || array_key_exists('material_nature_name', $data)) {
                $updates['material_nature_id'] = $this->resolveMaterialNatureId($data);
            }
            if (isset($data['description'])) {
                $updates['description'] = $data['description'];
            }

            if ($updates !== []) {
                $demand->fill($updates)->save();
            }

            if (isset($data['validator_ids'])) {
                $this->syncValidators($demand, $this->normalizeValidatorIds($data['validator_ids']));
            }

            if ($removeAttachmentIds !== []) {
                $this->removeAttachments($demand, $removeAttachmentIds);
            }

            $this->storeAttachments($demand, $actor, DemandAttachmentCollection::NatureMateriel, $natureFiles);
            $this->storeAttachments($demand, $actor, DemandAttachmentCollection::ReferentielProduit, $referentielFiles);

            $this->log($demand, $actor, 'updated', $demand->status->value, $demand->status->value);

            if (! empty($data['submit'])) {
                $this->submit($demand->fresh() ?? $demand, $actor);
            }

            return $demand->fresh(['validators.user', 'brand', 'materialNature', 'attachments', 'events.actor']) ?? $demand;
        });
    }

    public function submit(Demand $demand, User $actor): Demand
    {
        if (! $demand->status->isEditableByCreator()) {
            throw ValidationException::withMessages([
                'status' => __('demands.messages.cannot_submit_status'),
            ]);
        }

        if ($demand->created_by !== $actor->id && ! $actor->can('demands.view_all')) {
            throw ValidationException::withMessages([
                'demand' => __('demands.messages.cannot_submit'),
            ]);
        }

        $demand->loadMissing('validators', 'attachments', 'creator');

        if ($demand->validators->count() < self::MIN_VALIDATORS) {
            throw ValidationException::withMessages([
                'validator_ids' => __('demands.messages.validators_required', ['count' => self::MIN_VALIDATORS]),
            ]);
        }

        if ($demand->attachments->where('collection', DemandAttachmentCollection::NatureMateriel)->isEmpty()) {
            throw ValidationException::withMessages([
                'nature_materiel_files' => __('demands.messages.nature_pdf_required'),
            ]);
        }

        $managerId = $demand->creator?->manager_id;
        $requiresManager = $managerId !== null && $managerId !== $actor->id;

        $updated = DB::transaction(function () use ($demand, $actor, $managerId, $requiresManager): Demand {
            $from = $demand->status;

            foreach ($demand->validators as $validator) {
                $validator->update([
                    'status' => DemandValidatorStatus::Pending,
                    'acted_at' => null,
                    'comment' => null,
                    'acted_by' => null,
                ]);
            }

            $toStatus = $requiresManager
                ? DemandStatus::PendingManager
                : DemandStatus::PendingValidation;

            $demand->update([
                'status' => $toStatus,
                'current_step' => $requiresManager ? null : 1,
                'manager_id' => $requiresManager ? $managerId : null,
                'manager_approved_at' => null,
                'refused_reason' => null,
                'blocked_reason' => null,
            ]);

            $this->log(
                $demand,
                $actor,
                $from === DemandStatus::Refused ? 'resubmitted' : 'submitted',
                $from->value,
                $toStatus->value,
                $requiresManager ? null : 1,
            );

            return $demand->fresh(['validators.user', 'brand', 'creator', 'manager']) ?? $demand;
        });

        $this->notifications->onSubmitted($updated, $actor);

        return $updated;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function managerApprove(Demand $demand, User $actor, ?string $comment = null, array $files = []): Demand
    {
        $this->assertCurrentManager($demand, $actor);

        $updated = DB::transaction(function () use ($demand, $actor, $comment, $files): Demand {
            $from = $demand->status->value;

            $demand->update([
                'status' => DemandStatus::PendingValidation,
                'current_step' => 1,
                'manager_approved_at' => now(),
            ]);

            $event = $this->log(
                $demand,
                $actor,
                'manager_approved',
                $from,
                DemandStatus::PendingValidation->value,
                1,
                $comment,
            );
            $this->storeDecisionFiles($demand, $actor, $event, $files);

            return $demand->fresh(['validators.user', 'brand', 'creator', 'manager']) ?? $demand;
        });

        $this->notifications->onManagerApproved($updated, $actor);

        return $updated;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function approve(Demand $demand, User $actor, ?string $comment = null, array $files = []): Demand
    {
        $this->assertCurrentValidator($demand, $actor);

        $updated = DB::transaction(function () use ($demand, $actor, $comment, $files): Demand {
            $step = (int) $demand->current_step;
            $validator = $demand->validators()->where('position', $step)->firstOrFail();
            $validator->update([
                'status' => DemandValidatorStatus::Approved,
                'acted_at' => now(),
                'comment' => $comment,
                'acted_by' => $actor->id,
            ]);

            $max = (int) $demand->validators()->max('position');
            $from = $demand->status->value;
            $closesDemand = $validator->isGroupStep()
                && $validator->role_name === self::ROLE_REGLEMENTAIRES
                && $step >= $max;

            if ($closesDemand) {
                $demand->update([
                    'status' => DemandStatus::Closed,
                    'current_step' => null,
                    'closed_at' => now(),
                    'closed_by' => $actor->id,
                ]);
                $event = $this->log($demand, $actor, 'closed', $from, DemandStatus::Closed->value, $step, $comment);
            } elseif ($step >= $max) {
                $demand->update([
                    'status' => DemandStatus::PendingBusinessDev,
                    'current_step' => null,
                ]);
                $event = $this->log($demand, $actor, 'validator_approved', $from, DemandStatus::PendingBusinessDev->value, $step, $comment);
            } else {
                $demand->update(['current_step' => $step + 1]);
                $event = $this->log($demand, $actor, 'validator_approved', $from, DemandStatus::PendingValidation->value, $step, $comment);
            }

            $this->storeDecisionFiles($demand, $actor, $event, $files);

            return $demand->fresh(['validators.user', 'validators.actor', 'brand', 'creator']) ?? $demand;
        });

        if ($updated->status === DemandStatus::Closed) {
            $this->notifications->onClosed($updated, $actor);
        } else {
            $this->notifications->onApproved($updated, $actor);
        }

        return $updated;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function refuse(Demand $demand, User $actor, string $reason, array $files = []): Demand
    {
        $this->assertCanRefuseOrBlock($demand, $actor);

        $updated = DB::transaction(function () use ($demand, $actor, $reason, $files): Demand {
            $from = $demand->status->value;
            $step = $demand->current_step;

            $demand->update([
                'status' => DemandStatus::Refused,
                'refused_reason' => $reason,
                'blocked_reason' => null,
                'current_step' => null,
            ]);

            $eventType = match ($from) {
                DemandStatus::PendingBusinessDev->value => 'business_refused',
                DemandStatus::PendingManager->value => 'manager_refused',
                default => 'validator_refused',
            };

            $event = $this->log($demand, $actor, $eventType, $from, DemandStatus::Refused->value, $step, $reason);
            $this->storeDecisionFiles($demand, $actor, $event, $files);

            return $demand->fresh(['brand', 'creator']) ?? $demand;
        });

        $this->notifications->onRefused($updated, $actor, $reason);

        return $updated;
    }

    public function block(Demand $demand, User $actor, string $reason): Demand
    {
        $this->assertCanRefuseOrBlock($demand, $actor);

        $updated = DB::transaction(function () use ($demand, $actor, $reason): Demand {
            $from = $demand->status->value;
            $step = $demand->current_step;

            $demand->update([
                'status' => DemandStatus::Blocked,
                'blocked_reason' => $reason,
            ]);

            $eventType = match ($from) {
                DemandStatus::PendingBusinessDev->value => 'business_blocked',
                DemandStatus::PendingManager->value => 'manager_blocked',
                default => 'validator_blocked',
            };

            $this->log($demand, $actor, $eventType, $from, DemandStatus::Blocked->value, $step, $reason);

            return $demand->fresh(['brand', 'creator']) ?? $demand;
        });

        $this->notifications->onBlocked($updated, $actor, $reason);

        return $updated;
    }

    public function unblock(Demand $demand, User $actor): Demand
    {
        if ($demand->status !== DemandStatus::Blocked) {
            throw ValidationException::withMessages([
                'status' => __('demands.messages.only_blocked_unblock'),
            ]);
        }

        $updated = DB::transaction(function () use ($demand, $actor): Demand {
            $step = $demand->current_step;
            $from = $demand->status->value;

            // Blocked before manager approval → resume manager review.
            if ($demand->manager_id !== null && $demand->manager_approved_at === null) {
                $demand->update([
                    'status' => DemandStatus::PendingManager,
                    'blocked_reason' => null,
                    'current_step' => null,
                ]);

                $this->log($demand, $actor, 'unblocked', $from, DemandStatus::PendingManager->value);

                return $demand->fresh(['validators.user', 'brand', 'creator', 'manager']) ?? $demand;
            }

            $resumeStatus = $step !== null
                ? DemandStatus::PendingValidation
                : DemandStatus::PendingBusinessDev;

            // If blocked during business_dev, current_step is null — resume there.
            // If blocked during validation, keep current_step.
            if ($step === null) {
                $allApproved = $demand->validators()
                    ->where('status', '!=', DemandValidatorStatus::Approved->value)
                    ->doesntExist();
                $resumeStatus = $allApproved
                    ? DemandStatus::PendingBusinessDev
                    : DemandStatus::PendingValidation;

                if ($resumeStatus === DemandStatus::PendingValidation) {
                    $next = $demand->validators()
                        ->where('status', DemandValidatorStatus::Pending->value)
                        ->orderBy('position')
                        ->first();
                    $step = $next?->position ?? 1;
                }
            }

            $demand->update([
                'status' => $resumeStatus,
                'blocked_reason' => null,
                'current_step' => $resumeStatus === DemandStatus::PendingValidation ? $step : null,
            ]);

            $this->log($demand, $actor, 'unblocked', $from, $resumeStatus->value, $demand->current_step);

            return $demand->fresh(['validators.user', 'brand', 'creator', 'manager']) ?? $demand;
        });

        $this->notifications->onUnblocked($updated, $actor);

        return $updated;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function businessApprove(Demand $demand, User $actor, ?string $comment = null, array $files = []): Demand
    {
        if ($demand->status !== DemandStatus::PendingBusinessDev) {
            throw ValidationException::withMessages([
                'status' => __('demands.messages.not_awaiting_business'),
            ]);
        }

        $updated = DB::transaction(function () use ($demand, $actor, $comment, $files): Demand {
            $from = $demand->status->value;
            $demand->update([
                'status' => DemandStatus::PendingClosure,
            ]);
            $event = $this->log($demand, $actor, 'business_approved', $from, DemandStatus::PendingClosure->value, null, $comment);
            $this->storeDecisionFiles($demand, $actor, $event, $files);

            return $demand->fresh(['brand', 'creator']) ?? $demand;
        });

        $this->notifications->onBusinessApproved($updated, $actor);

        return $updated;
    }

    public function close(Demand $demand, User $actor): Demand
    {
        if ($demand->status !== DemandStatus::PendingClosure) {
            throw ValidationException::withMessages([
                'status' => __('demands.messages.not_awaiting_closure'),
            ]);
        }

        $updated = DB::transaction(function () use ($demand, $actor): Demand {
            $from = $demand->status->value;
            $demand->update([
                'status' => DemandStatus::Closed,
                'closed_at' => now(),
                'closed_by' => $actor->id,
            ]);
            $this->log($demand, $actor, 'closed', $from, DemandStatus::Closed->value);

            return $demand->fresh(['brand', 'creator']) ?? $demand;
        });

        $this->notifications->onClosed($updated, $actor);

        return $updated;
    }

    /**
     * @param  array<int, int>  $validatorIds
     * @return array<int, int>
     */
    public function normalizeValidatorIds(array $validatorIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $validatorIds)));

        if (count($ids) < self::MIN_VALIDATORS) {
            throw ValidationException::withMessages([
                'validator_ids' => __('demands.messages.validators_distinct_required', ['count' => self::MIN_VALIDATORS]),
            ]);
        }

        $users = User::query()->whereIn('id', $ids)->get();
        if ($users->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'validator_ids' => __('demands.messages.validators_invalid'),
            ]);
        }

        foreach ($users as $user) {
            if (! $user->hasRole('validator') && ! $user->can('demands.validate')) {
                throw ValidationException::withMessages([
                    'validator_ids' => __('demands.messages.validators_must_be'),
                ]);
            }
        }

        // Preserve order from input
        $ordered = [];
        foreach ($ids as $id) {
            $ordered[] = $id;
        }

        return $ordered;
    }

    /**
     * @param  array<int, int>  $validatorIds
     */
    private function syncValidators(Demand $demand, array $validatorIds): void
    {
        $demand->validators()->delete();

        $position = 1;
        foreach ($validatorIds as $userId) {
            DemandValidator::query()->create([
                'demand_id' => $demand->id,
                'user_id' => $userId,
                'role_name' => null,
                'position' => $position,
                'status' => DemandValidatorStatus::Pending,
            ]);
            $position++;
        }

        DemandValidator::query()->create([
            'demand_id' => $demand->id,
            'user_id' => null,
            'role_name' => self::ROLE_REGLEMENTAIRES,
            'position' => $position,
            'status' => DemandValidatorStatus::Pending,
        ]);
    }

    private function assertCurrentValidator(Demand $demand, User $actor): void
    {
        if ($demand->status !== DemandStatus::PendingValidation) {
            throw ValidationException::withMessages([
                'status' => __('demands.messages.not_awaiting_validator'),
            ]);
        }

        $current = $demand->validators()->where('position', $demand->current_step)->first();
        if ($current === null || ! $current->canBeActedBy($actor)) {
            throw ValidationException::withMessages([
                'demand' => __('demands.messages.not_current_validator'),
            ]);
        }
    }

    /**
     * @param  array{material_nature_id?:int|null, material_nature_name?:string|null}  $data
     */
    private function resolveMaterialNatureId(array $data): int
    {
        if (! empty($data['material_nature_id'])) {
            return (int) $data['material_nature_id'];
        }

        $name = trim((string) ($data['material_nature_name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages([
                'material_nature_id' => __('demands.messages.nature_required'),
            ]);
        }

        $nature = MaterialNature::query()->firstOrCreate(
            ['name' => $name],
            ['name' => $name],
        );

        return $nature->id;
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(
        Demand $demand,
        User $actor,
        DemandAttachmentCollection $collection,
        array $files,
        ?DemandEvent $event = null,
    ): void {
        if ($files === []) {
            return;
        }

        $folder = $this->ensureDemandDriveFolder($demand, $actor);

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $driveFile = $this->drive->uploadFile($actor, $file, $folder);

            DemandAttachment::query()->create([
                'demand_id' => $demand->id,
                'collection' => $collection,
                'disk' => $driveFile->disk,
                'path' => $driveFile->path,
                'original_name' => $driveFile->original_name,
                'mime' => $driveFile->mime,
                'size' => $driveFile->size,
                'uploaded_by' => $actor->id,
                'drive_file_id' => $driveFile->id,
                'demand_event_id' => $event?->id,
            ]);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeDecisionFiles(Demand $demand, User $actor, DemandEvent $event, array $files): void
    {
        $this->storeAttachments($demand, $actor, DemandAttachmentCollection::Decision, $files, $event);
    }

    private function ensureDemandDriveFolder(Demand $demand, User $actor): DriveFolder
    {
        $rootName = 'Demands';

        $root = DriveFolder::query()
            ->where('owner_id', $actor->id)
            ->whereNull('parent_id')
            ->where('name', $rootName)
            ->first();

        if ($root === null) {
            $root = $this->drive->createFolder($actor, $rootName);
        }

        $folder = DriveFolder::query()
            ->where('owner_id', $actor->id)
            ->where('parent_id', $root->id)
            ->where('name', $demand->reference)
            ->first();

        if ($folder === null) {
            $folder = $this->drive->createFolder($actor, $demand->reference, $root);
        }

        return $folder;
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function removeAttachments(Demand $demand, array $ids): void
    {
        $attachments = $demand->attachments()->whereIn('id', $ids)->get();
        foreach ($attachments as $attachment) {
            if ($attachment->drive_file_id) {
                $driveFile = DriveFile::query()->find($attachment->drive_file_id);
                if ($driveFile !== null) {
                    $this->drive->trashFile($driveFile);
                }
                $attachment->delete();

                continue;
            }

            $attachment->deleteFile();
            $attachment->delete();
        }
    }

    private function assertCurrentManager(Demand $demand, User $actor): void
    {
        if ($demand->status !== DemandStatus::PendingManager) {
            throw ValidationException::withMessages([
                'status' => __('demands.messages.not_awaiting_manager'),
            ]);
        }

        if ($demand->manager_id !== $actor->id) {
            throw ValidationException::withMessages([
                'demand' => __('demands.messages.not_current_manager'),
            ]);
        }
    }

    private function assertCanRefuseOrBlock(Demand $demand, User $actor): void
    {
        if ($demand->status === DemandStatus::PendingManager) {
            $this->assertCurrentManager($demand, $actor);

            return;
        }

        if ($demand->status === DemandStatus::PendingValidation) {
            $this->assertCurrentValidator($demand, $actor);

            return;
        }

        if ($demand->status === DemandStatus::PendingBusinessDev) {
            if (! $actor->can('demands.business_validate')) {
                throw ValidationException::withMessages([
                    'demand' => __('demands.messages.cannot_act'),
                ]);
            }

            return;
        }

        throw ValidationException::withMessages([
            'status' => __('demands.messages.cannot_refuse_block'),
        ]);
    }

    private function generateReference(): string
    {
        do {
            $reference = 'DEM-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Demand::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function log(
        Demand $demand,
        ?User $actor,
        string $type,
        ?string $from,
        ?string $to,
        ?int $step = null,
        ?string $comment = null,
        ?array $meta = null,
    ): DemandEvent {
        return DemandEvent::query()->create([
            'demand_id' => $demand->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'step' => $step,
            'comment' => $comment,
            'meta' => $meta,
            'created_at' => now(),
        ]);
    }
}
