<?php

use App\Enums\DemandStatus;
use App\Models\Demand;
use App\Models\DemandAttachment;
use App\Models\User;
use App\Notifications\Demand\DemandNeedsValidationNotification;
use App\Notifications\Demand\DemandPendingBusinessDevNotification;
use App\Notifications\Demand\DemandRefusedNotification;
use App\Notifications\Demand\DemandSubmittedNotification;
use App\Services\Demand\DemandWorkflowService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function notificationValidators(int $count = 3): array
{
    return User::factory()->count($count)->validator()->create()->all();
}

function notificationAttachValidators(Demand $demand, array $validators): void
{
    foreach ($validators as $index => $validator) {
        $demand->validators()->create([
            'user_id' => $validator->id,
            'position' => $index + 1,
            'status' => 'pending',
        ]);
    }
}

function notificationSeedPdf(Demand $demand, User $uploader): void
{
    Storage::fake('local');
    $path = "demands/{$demand->id}/nature_materiel/sample.pdf";
    Storage::disk('local')->put($path, 'pdf');

    DemandAttachment::query()->create([
        'demand_id' => $demand->id,
        'collection' => 'nature_materiel',
        'disk' => 'local',
        'path' => $path,
        'original_name' => 'sample.pdf',
        'mime' => 'application/pdf',
        'size' => 3,
        'uploaded_by' => $uploader->id,
    ]);
}

it('notifies the first validator when a demand is submitted', function () {
    Notification::fake();

    $pm = User::factory()->projectManager()->create();
    $validators = notificationValidators(3);
    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::Draft,
    ]);
    notificationAttachValidators($demand, $validators);
    notificationSeedPdf($demand, $pm);

    app(DemandWorkflowService::class)->submit($demand, $pm);

    Notification::assertSentTo($validators[0], DemandSubmittedNotification::class);
    Notification::assertNotSentTo($validators[1], DemandSubmittedNotification::class);
    Notification::assertNotSentTo($pm, DemandSubmittedNotification::class);
});

it('notifies the next validator when a step is approved', function () {
    Notification::fake();

    $pm = User::factory()->projectManager()->create();
    $validators = notificationValidators(3);
    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);
    notificationAttachValidators($demand, $validators);

    app(DemandWorkflowService::class)->approve($demand, $validators[0]);

    Notification::assertSentTo($validators[1], DemandNeedsValidationNotification::class);
    Notification::assertNotSentTo($validators[0], DemandNeedsValidationNotification::class);
});

it('notifies business development after the last validator approves', function () {
    Notification::fake();

    $pm = User::factory()->projectManager()->create();
    $validators = notificationValidators(3);
    $biz = User::factory()->businessDev()->create();
    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 3,
    ]);
    notificationAttachValidators($demand, $validators);

    app(DemandWorkflowService::class)->approve($demand->fresh(), $validators[2]);

    Notification::assertSentTo($biz, DemandPendingBusinessDevNotification::class);
});

it('notifies the creator when a demand is refused', function () {
    Notification::fake();

    $pm = User::factory()->projectManager()->create();
    $validators = notificationValidators(3);
    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);
    notificationAttachValidators($demand, $validators);

    app(DemandWorkflowService::class)->refuse($demand, $validators[0], 'Missing brief');

    Notification::assertSentTo($pm, DemandRefusedNotification::class);
});

it('marks a notification as read and redirects to the demand', function () {
    $pm = User::factory()->projectManager()->create();
    $validator = User::factory()->validator()->create();
    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);

    $validator->notifyNow(new DemandSubmittedNotification($demand, $pm));

    $notification = $validator->notifications()->first();
    expect($notification)->not->toBeNull()
        ->and($notification->read_at)->toBeNull();

    $this->actingAs($validator)
        ->post(route('notifications.read', $notification->id))
        ->assertRedirect(route('demands.show', $demand));

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('marks all notifications as read', function () {
    $pm = User::factory()->projectManager()->create();
    $validator = User::factory()->validator()->create();
    $demand = Demand::factory()->create(['created_by' => $pm->id]);

    $validator->notifyNow(new DemandSubmittedNotification($demand, $pm));
    $validator->notifyNow(new DemandSubmittedNotification($demand, $pm));

    expect($validator->unreadNotifications()->count())->toBe(2);

    $this->actingAs($validator)
        ->post(route('notifications.read-all'))
        ->assertRedirect();

    expect($validator->fresh()->unreadNotifications()->count())->toBe(0);
});
