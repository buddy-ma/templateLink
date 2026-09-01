<?php

declare(strict_types=1);

namespace App\Services\Drive;

use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\User;
use App\Notifications\Drive\DriveSharedNotification;

class DriveNotificationService
{
    public function onShared(DriveShare $share, User $actor): void
    {
        $share->loadMissing(['user', 'shareable']);

        $recipient = $share->user;
        if ($recipient === null || $recipient->id === $actor->id) {
            return;
        }

        $shareable = $share->shareable;
        if (! $shareable instanceof DriveFolder && ! $shareable instanceof DriveFile) {
            return;
        }

        $recipient->notify(new DriveSharedNotification($shareable, $share, $actor));
    }
}
