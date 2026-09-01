<?php

declare(strict_types=1);

namespace App\Services\Drive;

use App\Enums\DriveSharePermission;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\Drive\DriveShareLink;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriveWorkflowService
{
    public function __construct(
        private readonly DriveQuotaService $quota,
        private readonly DriveAccessService $access,
        private readonly DriveNotificationService $notifications,
    ) {}

    public function createFolder(User $actor, string $name, ?DriveFolder $parent = null): DriveFolder
    {
        if ($parent !== null && ! $this->access->canEdit($actor, $parent)) {
            throw ValidationException::withMessages([
                'name' => __('drive.errors.forbidden'),
            ]);
        }

        return DriveFolder::query()->create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'owner_id' => $parent?->owner_id ?? $actor->id,
            'created_by' => $actor->id,
        ]);
    }

    public function renameFolder(DriveFolder $folder, string $name): DriveFolder
    {
        $folder->update(['name' => $name]);

        return $folder->refresh();
    }

    public function moveFolder(DriveFolder $folder, ?DriveFolder $destination): DriveFolder
    {
        if ($destination !== null) {
            if ($destination->id === $folder->id) {
                throw ValidationException::withMessages([
                    'parent_id' => __('drive.errors.invalid_move'),
                ]);
            }

            $descendantIds = $this->access->descendantFolderIds([$folder->id]);
            if (in_array($destination->id, $descendantIds, true)) {
                throw ValidationException::withMessages([
                    'parent_id' => __('drive.errors.invalid_move'),
                ]);
            }
        }

        $folder->update(['parent_id' => $destination?->id]);

        return $folder->refresh();
    }

    public function uploadFile(User $actor, UploadedFile $file, ?DriveFolder $folder = null, ?string $name = null): DriveFile
    {
        if ($folder !== null && ! $this->access->canEdit($actor, $folder)) {
            throw ValidationException::withMessages([
                'file' => __('drive.errors.forbidden'),
            ]);
        }

        $this->quota->assertCanStore((int) $file->getSize());

        $disk = 'local';
        $folderKey = $folder?->id ?? 'root';
        $storedPath = $file->store("drive/{$folderKey}", $disk);

        if ($storedPath === false) {
            throw ValidationException::withMessages([
                'file' => __('drive.errors.upload_failed'),
            ]);
        }

        return DriveFile::query()->create([
            'folder_id' => $folder?->id,
            'name' => $name ?: $file->getClientOriginalName(),
            'disk' => $disk,
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType() ?: $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'owner_id' => $folder?->owner_id ?? $actor->id,
            'uploaded_by' => $actor->id,
        ]);
    }

    public function renameFile(DriveFile $file, string $name): DriveFile
    {
        $file->update(['name' => $name]);

        return $file->refresh();
    }

    public function moveFile(DriveFile $file, ?DriveFolder $destination): DriveFile
    {
        $file->update(['folder_id' => $destination?->id]);

        return $file->refresh();
    }

    public function trashFolder(DriveFolder $folder): void
    {
        DB::transaction(function () use ($folder): void {
            $this->trashFolderRecursive($folder);
        });
    }

    public function trashFile(DriveFile $file): void
    {
        $file->delete();
    }

    public function restoreFolder(DriveFolder $folder): void
    {
        $folder->restore();

        DriveFolder::onlyTrashed()
            ->where('parent_id', $folder->id)
            ->get()
            ->each(fn (DriveFolder $child) => $this->restoreFolder($child));

        DriveFile::onlyTrashed()
            ->where('folder_id', $folder->id)
            ->restore();
    }

    public function restoreFile(DriveFile $file): void
    {
        $file->restore();
    }

    public function forceDeleteFolder(DriveFolder $folder): void
    {
        DB::transaction(function () use ($folder): void {
            $this->forceDeleteFolderRecursive($folder);
        });
    }

    public function forceDeleteFile(DriveFile $file): void
    {
        $file->deleteFile();
        $file->forceDelete();
    }

    public function shareWithUser(
        User $actor,
        DriveFolder|DriveFile $item,
        User $recipient,
        DriveSharePermission $permission,
    ): DriveShare {
        $share = DriveShare::query()->updateOrCreate(
            [
                'shareable_type' => $item::class,
                'shareable_id' => $item->id,
                'user_id' => $recipient->id,
            ],
            [
                'permission' => $permission,
                'shared_by' => $actor->id,
            ],
        );

        $this->notifications->onShared($share, $actor);

        return $share;
    }

    public function revokeShare(DriveShare $share): void
    {
        $share->delete();
    }

    public function createShareLink(
        User $actor,
        DriveFolder|DriveFile $item,
        DriveSharePermission $permission,
        ?string $password = null,
        ?\DateTimeInterface $expiresAt = null,
    ): DriveShareLink {
        return DriveShareLink::query()->create([
            'shareable_type' => $item::class,
            'shareable_id' => $item->id,
            'token' => DriveShareLink::generateToken(),
            'password' => filled($password) ? $password : null,
            'permission' => $permission,
            'expires_at' => $expiresAt,
            'created_by' => $actor->id,
        ]);
    }

    public function revokeShareLink(DriveShareLink $link): void
    {
        $link->revoke();
    }

    private function trashFolderRecursive(DriveFolder $folder): void
    {
        $folder->children()->get()->each(fn (DriveFolder $child) => $this->trashFolderRecursive($child));
        $folder->files()->get()->each(fn (DriveFile $file) => $file->delete());
        $folder->delete();
    }

    private function forceDeleteFolderRecursive(DriveFolder $folder): void
    {
        DriveFolder::withTrashed()
            ->where('parent_id', $folder->id)
            ->get()
            ->each(fn (DriveFolder $child) => $this->forceDeleteFolderRecursive($child));

        DriveFile::withTrashed()
            ->where('folder_id', $folder->id)
            ->get()
            ->each(function (DriveFile $file): void {
                $file->deleteFile();
                $file->forceDelete();
            });

        $folder->forceDelete();
    }
}
