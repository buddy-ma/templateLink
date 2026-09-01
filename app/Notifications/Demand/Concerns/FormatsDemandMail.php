<?php

declare(strict_types=1);

namespace App\Notifications\Demand\Concerns;

use App\Models\Demand;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;

trait FormatsDemandMail
{
    protected function demandMail(
        Demand $demand,
        string $subjectKey,
        string $lineKey,
        ?User $actor = null,
        ?string $comment = null,
    ): MailMessage {
        $appName = (string) settings('branding.app_name', config('app.name'));
        $actorName = $actor?->name ?? __('demands.notifications.system_actor');

        $mail = (new MailMessage)
            ->subject(__($subjectKey, [
                'reference' => $demand->reference,
                'app' => $appName,
            ]))
            ->greeting(__('demands.notifications.greeting'))
            ->line(__($lineKey, [
                'reference' => $demand->reference,
                'actor' => $actorName,
                'status' => __('demands.status.'.$demand->status->value),
                'brand' => $demand->brand?->name ?? '—',
            ]));

        if ($comment !== null && $comment !== '') {
            $mail->line(__('demands.notifications.comment_line', ['comment' => $comment]));
        }

        return $mail
            ->action(
                __('demands.notifications.view_demand'),
                route('demands.show', $demand),
            )
            ->salutation(__('demands.notifications.salutation', ['app' => $appName]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function demandDatabasePayload(
        Demand $demand,
        string $event,
        ?User $actor = null,
        ?string $comment = null,
    ): array {
        return [
            'event' => $event,
            'demand_id' => $demand->id,
            'reference' => $demand->reference,
            'status' => $demand->status->value,
            'brand_name' => $demand->brand?->name,
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'comment' => $comment,
            'url' => route('demands.show', $demand),
            'message_key' => 'notifications.events.'.$event,
        ];
    }
}
