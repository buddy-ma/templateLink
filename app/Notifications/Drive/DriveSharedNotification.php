<?php

declare(strict_types=1);

namespace App\Notifications\Drive;

use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DriveSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DriveFolder|DriveFile $item,
        public DriveShare $share,
        public User $actor,
    ) {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->item->name;
        $permission = $this->share->permission->value;
        $url = $this->item instanceof DriveFolder
            ? route('drive.index', ['folder' => $this->item->id])
            : route('drive.index', ['folder' => $this->item->folder_id]);

        return (new MailMessage)
            ->subject(__('drive.notifications.shared.subject', ['name' => $name]))
            ->line(__('drive.notifications.shared.line', [
                'actor' => $this->actor->name,
                'name' => $name,
                'permission' => $permission,
            ]))
            ->action(__('drive.notifications.shared.action'), $url);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $isFolder = $this->item instanceof DriveFolder;

        return [
            'event' => 'drive_shared',
            'item_type' => $isFolder ? 'folder' : 'file',
            'item_id' => $this->item->id,
            'item_name' => $this->item->name,
            'permission' => $this->share->permission->value,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'url' => $isFolder
                ? route('drive.index', ['folder' => $this->item->id], false)
                : route('drive.index', ['folder' => $this->item->folder_id], false),
            'message_key' => 'drive.notifications.shared.line',
        ];
    }
}
