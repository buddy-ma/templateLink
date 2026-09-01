<?php

declare(strict_types=1);

namespace App\Services\Demand;

use App\Enums\DemandStatus;
use App\Models\Demand;
use App\Models\User;
use App\Notifications\Demand\DemandBlockedNotification;
use App\Notifications\Demand\DemandClosedNotification;
use App\Notifications\Demand\DemandNeedsValidationNotification;
use App\Notifications\Demand\DemandPendingBusinessDevNotification;
use App\Notifications\Demand\DemandPendingClosureNotification;
use App\Notifications\Demand\DemandPendingManagerNotification;
use App\Notifications\Demand\DemandRefusedNotification;
use App\Notifications\Demand\DemandSubmittedNotification;
use App\Notifications\Demand\DemandUnblockedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class DemandNotificationService
{
    public function onSubmitted(Demand $demand, User $actor): void
    {
        $demand->loadMissing(['validators.user', 'brand', 'creator', 'manager']);

        if ($demand->status === DemandStatus::PendingManager) {
            $manager = $demand->manager;
            if ($manager !== null && $manager->id !== $actor->id) {
                $manager->notify(new DemandPendingManagerNotification($demand, $actor));
            }

            return;
        }

        $this->notifyCurrentValidators($demand, $actor, new DemandSubmittedNotification($demand, $actor));
    }

    public function onManagerApproved(Demand $demand, User $actor): void
    {
        $demand->loadMissing(['validators.user', 'brand', 'creator']);

        $this->notifyCurrentValidators($demand, $actor, new DemandSubmittedNotification($demand, $actor));
    }

    public function onApproved(Demand $demand, User $actor): void
    {
        $demand->loadMissing(['validators.user', 'brand', 'creator']);

        if ($demand->status === DemandStatus::PendingBusinessDev) {
            $this->notifyPermissionHolders(
                'demands.business_validate',
                new DemandPendingBusinessDevNotification($demand, $actor),
                $actor,
            );

            return;
        }

        $this->notifyCurrentValidators($demand, $actor, new DemandNeedsValidationNotification($demand, $actor));
    }

    public function onRefused(Demand $demand, User $actor, string $reason): void
    {
        $demand->loadMissing(['brand', 'creator']);
        $creator = $demand->creator;

        if ($creator !== null && $creator->id !== $actor->id) {
            $creator->notify(new DemandRefusedNotification($demand, $actor, $reason));
        }
    }

    public function onBlocked(Demand $demand, User $actor, string $reason): void
    {
        $demand->loadMissing(['brand', 'creator']);
        $creator = $demand->creator;

        if ($creator !== null && $creator->id !== $actor->id) {
            $creator->notify(new DemandBlockedNotification($demand, $actor, $reason));
        }
    }

    public function onUnblocked(Demand $demand, User $actor): void
    {
        $demand->loadMissing(['validators.user', 'brand', 'creator', 'manager']);

        if ($demand->status === DemandStatus::PendingManager) {
            $manager = $demand->manager;
            if ($manager !== null && $manager->id !== $actor->id) {
                $manager->notify(new DemandUnblockedNotification($demand, $actor));
            }

            return;
        }

        if ($demand->status === DemandStatus::PendingBusinessDev) {
            $this->notifyPermissionHolders(
                'demands.business_validate',
                new DemandUnblockedNotification($demand, $actor),
                $actor,
            );

            return;
        }

        $this->notifyCurrentValidators($demand, $actor, new DemandUnblockedNotification($demand, $actor));
    }

    public function onBusinessApproved(Demand $demand, User $actor): void
    {
        $demand->loadMissing(['brand', 'creator']);

        $this->notifyPermissionHolders(
            'demands.close',
            new DemandPendingClosureNotification($demand, $actor),
            $actor,
        );
    }

    public function onClosed(Demand $demand, User $actor): void
    {
        $demand->loadMissing(['brand', 'creator']);
        $creator = $demand->creator;

        if ($creator !== null && $creator->id !== $actor->id) {
            $creator->notify(new DemandClosedNotification($demand, $actor));
        }
    }

    private function notifyCurrentValidators(Demand $demand, User $actor, object $notification): void
    {
        $users = $this->currentValidatorUsers($demand)
            ->filter(fn (User $user): bool => $user->id !== $actor->id);

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, $notification);
    }

    /**
     * @return Collection<int, User>
     */
    private function currentValidatorUsers(Demand $demand): Collection
    {
        if ($demand->status !== DemandStatus::PendingValidation || $demand->current_step === null) {
            return collect();
        }

        $row = $demand->validators->firstWhere('position', $demand->current_step);
        if ($row === null) {
            return collect();
        }

        if ($row->isGroupStep()) {
            return User::role((string) $row->role_name)->get();
        }

        return $row->user ? collect([$row->user]) : collect();
    }

    private function notifyPermissionHolders(string $permission, object $notification, User $actor): void
    {
        /** @var Collection<int, User> $users */
        $users = User::permission($permission)->get()->filter(
            fn (User $user): bool => $user->id !== $actor->id,
        );

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, $notification);
    }
}
