<?php

declare(strict_types=1);

namespace App\Policies\Drive;

use App\Models\Drive\DriveFile;
use App\Models\User;
use App\Services\Drive\DriveAccessService;

class DriveFilePolicy
{
    public function __construct(private readonly DriveAccessService $access) {}

    public function viewAny(User $user): bool
    {
        return $user->can('drive.access');
    }

    public function view(User $user, DriveFile $file): bool
    {
        return $user->can('drive.access') && $this->access->canView($user, $file);
    }

    public function create(User $user): bool
    {
        return $user->can('drive.upload');
    }

    public function update(User $user, DriveFile $file): bool
    {
        return $user->can('drive.access') && $this->access->canEdit($user, $file);
    }

    public function delete(User $user, DriveFile $file): bool
    {
        return $this->update($user, $file);
    }

    public function restore(User $user, DriveFile $file): bool
    {
        return $user->can('drive.manage')
            || ($user->can('drive.access') && $file->owner_id === $user->id);
    }

    public function forceDelete(User $user, DriveFile $file): bool
    {
        return $user->can('drive.manage')
            || ($file->owner_id === $user->id && $user->can('drive.upload'));
    }

    public function share(User $user, DriveFile $file): bool
    {
        return $this->access->canShare($user, $file);
    }

    public function download(User $user, DriveFile $file): bool
    {
        return $this->view($user, $file);
    }
}
