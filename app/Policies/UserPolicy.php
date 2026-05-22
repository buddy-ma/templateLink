<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Directory of users for impersonation / support.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('impersonate_users');
    }

    public function impersonate(User $actor, User $target): bool
    {
        if (! $actor->can('impersonate_users')) {
            return false;
        }

        if ($actor->id === $target->id) {
            return false;
        }

        return ! request()->session()->has('impersonate.original_user_id');
    }
}
