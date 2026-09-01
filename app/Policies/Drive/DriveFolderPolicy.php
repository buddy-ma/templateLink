<?php

declare(strict_types=1);

namespace App\Policies\Drive;

use App\Models\Drive\DriveFolder;
use App\Models\User;
use App\Services\Drive\DriveAccessService;

class DriveFolderPolicy
{
    public function __construct(private readonly DriveAccessService $access) {}

    public function viewAny(User $user): bool
    {
        return $user->can('drive.access');
    }

    public function view(User $user, DriveFolder $folder): bool
    {
        return $user->can('drive.access') && $this->access->canView($user, $folder);
    }

    public function create(User $user): bool
    {
        return $user->can('drive.upload');
    }

    public function update(User $user, DriveFolder $folder): bool
    {
        return $user->can('drive.access') && $this->access->canEdit($user, $folder);
    }

    public function delete(User $user, DriveFolder $folder): bool
    {
        return $this->update($user, $folder);
    }

    public function restore(User $user, DriveFolder $folder): bool
    {
        return $user->can('drive.manage')
            || ($user->can('drive.access') && $folder->owner_id === $user->id);
    }

    public function forceDelete(User $user, DriveFolder $folder): bool
    {
        return $user->can('drive.manage')
            || ($folder->owner_id === $user->id && $user->can('drive.upload'));
    }

    public function share(User $user, DriveFolder $folder): bool
    {
        return $this->access->canShare($user, $folder);
    }
}
