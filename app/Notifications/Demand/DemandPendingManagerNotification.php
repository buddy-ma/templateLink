<?php

declare(strict_types=1);

namespace App\Notifications\Demand;

use App\Models\Demand;
use App\Models\User;
use App\Notifications\Demand\Concerns\FormatsDemandMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DemandPendingManagerNotification extends Notification implements ShouldQueue
{
    use FormatsDemandMail;
    use Queueable;

    public function __construct(
        public Demand $demand,
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
        return $this->demandMail(
            $this->demand,
            'demands.notifications.pending_manager.subject',
            'demands.notifications.pending_manager.line',
            $this->actor,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->demandDatabasePayload($this->demand, 'pending_manager', $this->actor);
    }
}
