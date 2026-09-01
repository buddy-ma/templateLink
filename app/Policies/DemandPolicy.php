<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\DemandStatus;
use App\Models\Demand;
use App\Models\User;

class DemandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('demands.access');
    }

    public function view(User $user, Demand $demand): bool
    {
        if (! $user->can('demands.access')) {
            return false;
        }

        if ($user->can('demands.view_all')) {
            return true;
        }

        if ($demand->created_by === $user->id) {
            return true;
        }

        if ($demand->manager_id === $user->id) {
            return true;
        }

        if ($user->reports()->where('id', $demand->created_by)->exists()) {
            return true;
        }

        if ($demand->validators()->where('user_id', $user->id)->exists()) {
            return true;
        }

        $roleNames = $user->getRoleNames()->all();
        if ($roleNames !== [] && $demand->validators()->whereIn('role_name', $roleNames)->exists()) {
            return true;
        }

        if ($user->can('demands.business_validate')) {
            return in_array($demand->status, [
                DemandStatus::PendingBusinessDev,
                DemandStatus::PendingClosure,
                DemandStatus::Closed,
                DemandStatus::Blocked,
                DemandStatus::Refused,
            ], true);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('demands.create');
    }

    public function update(User $user, Demand $demand): bool
    {
        if (! $demand->status->isEditableByCreator()) {
            return false;
        }

        if ($user->can('demands.view_all')) {
            return true;
        }

        return $demand->created_by === $user->id && $user->can('demands.create');
    }

    public function validate(User $user, Demand $demand): bool
    {
        if (! $user->can('demands.validate')) {
            return false;
        }

        if ($demand->status !== DemandStatus::PendingValidation) {
            return false;
        }

        $current = $demand->validators()->where('position', $demand->current_step)->first();

        return $current !== null && $current->canBeActedBy($user);
    }

    public function managerValidate(User $user, Demand $demand): bool
    {
        return $demand->status === DemandStatus::PendingManager
            && $demand->manager_id === $user->id;
    }

    public function businessValidate(User $user, Demand $demand): bool
    {
        return $user->can('demands.business_validate')
            && $demand->status === DemandStatus::PendingBusinessDev;
    }

    public function refuseOrBlock(User $user, Demand $demand): bool
    {
        return $this->validate($user, $demand)
            || $this->managerValidate($user, $demand)
            || $this->businessValidate($user, $demand);
    }

    public function unblock(User $user, Demand $demand): bool
    {
        return $user->can('demands.unblock') && $demand->status === DemandStatus::Blocked;
    }

    public function close(User $user, Demand $demand): bool
    {
        return $user->can('demands.close') && $demand->status === DemandStatus::PendingClosure;
    }

    public function manageCatalog(User $user): bool
    {
        return $user->can('demands.manage_catalog');
    }

    public function managePipeline(User $user): bool
    {
        return $user->can('demands.manage_pipeline');
    }
}
