<?php

use App\Enums\DemandStatus;
use App\Models\Brand;
use App\Models\Demand;
use App\Models\DemandAttachment;
use App\Models\MaterialNature;
use App\Models\User;
use App\Models\ValidationPipeline;
use App\Models\ValidationPipelineStep;
use App\Services\Demand\DemandWorkflowService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function createValidators(int $count = 3): array
{
    return User::factory()->count($count)->validator()->create()->all();
}

function attachValidators(Demand $demand, array $validators): void
{
    foreach ($validators as $index => $validator) {
        $demand->validators()->create([
            'user_id' => $validator->id,
            'position' => $index + 1,
            'status' => 'pending',
        ]);
    }
}

function seedPdfAttachment(Demand $demand, User $uploader): DemandAttachment
{
    Storage::fake('local');
    $path = "demands/{$demand->id}/nature_materiel/sample.pdf";
    Storage::disk('local')->put($path, 'pdf');

    return DemandAttachment::query()->create([
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

it('lets a marketing manager see demands created by their project managers', function () {
    $rm = User::factory()->responsableMarketing()->create();
    $pm = User::factory()->projectManager()->create(['manager_id' => $rm->id]);
    $otherRm = User::factory()->responsableMarketing()->create();

    $demand = Demand::factory()->create(['created_by' => $pm->id]);

    $this->withoutVite()
        ->actingAs($rm)
        ->get('/demands')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('demands/Index')
            ->has('demands.data', 1));

    $this->withoutVite()
        ->actingAs($otherRm)
        ->get('/demands')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('demands.data', 0));

    $this->actingAs($otherRm)
        ->get("/demands/{$demand->id}")
        ->assertForbidden();
});

it('requires at least three validators when creating a demand', function () {
    Storage::fake('local');
    $pm = User::factory()->projectManager()->create();
    $brand = Brand::factory()->create();
    $validators = createValidators(2);

    $this->actingAs($pm)
        ->post('/demands', [
            'brand_id' => $brand->id,
            'material_nature_name' => 'Brochure',
            'description' => 'Need description',
            'validator_ids' => collect($validators)->pluck('id')->all(),
            'submit' => false,
        ])
        ->assertSessionHasErrors('validator_ids');
});

it('creates a demand with find-or-create material nature and default pipeline snapshot', function () {
    Storage::fake('local');
    $pm = User::factory()->projectManager()->create();
    $brand = Brand::factory()->create();
    $validators = createValidators(3);

    $pipeline = ValidationPipeline::query()->create([
        'name' => 'Default',
        'is_default' => true,
    ]);
    foreach ($validators as $index => $validator) {
        ValidationPipelineStep::query()->create([
            'pipeline_id' => $pipeline->id,
            'user_id' => $validator->id,
            'position' => $index + 1,
        ]);
    }

    $response = $this->actingAs($pm)->post('/demands', [
        'brand_id' => $brand->id,
        'material_nature_name' => 'Flyer A4',
        'description' => 'Need flyers for launch',
        'validator_ids' => collect($validators)->pluck('id')->all(),
        'nature_materiel_files' => [UploadedFile::fake()->create('nature.pdf', 100, 'application/pdf')],
        'submit' => true,
    ]);

    $demand = Demand::query()->first();
    expect($demand)->not->toBeNull();
    $response->assertRedirect(route('demands.show', $demand));

    expect($demand->status)->toBe(DemandStatus::PendingValidation)
        ->and($demand->current_step)->toBe(1)
        ->and($demand->validators)->toHaveCount(4)
        ->and($demand->validators->last()?->role_name)->toBe(DemandWorkflowService::ROLE_REGLEMENTAIRES)
        ->and(MaterialNature::query()->where('name', 'Flyer A4')->exists())->toBeTrue()
        ->and($demand->attachments)->toHaveCount(1)
        ->and($demand->attachments->first()?->drive_file_id)->not->toBeNull();
});

it('forbids out-of-order validator approval', function () {
    $pm = User::factory()->projectManager()->create();
    $validators = createValidators(3);
    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);
    attachValidators($demand, $validators);
    seedPdfAttachment($demand, $pm);

    $this->actingAs($validators[1])
        ->post("/demands/{$demand->id}/approve", ['reason' => 'Looks good'])
        ->assertForbidden();
});

it('advances through validators then business dev then admin close', function () {
    $pm = User::factory()->projectManager()->create();
    $validators = createValidators(3);
    $biz = User::factory()->businessDev()->create();
    $admin = User::factory()->admin()->create();

    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);
    attachValidators($demand, $validators);
    seedPdfAttachment($demand, $pm);

    foreach ($validators as $index => $validator) {
        $this->actingAs($validator)
            ->post("/demands/{$demand->id}/approve", ['reason' => 'Approved step '.($index + 1)])
            ->assertRedirect();

        $demand->refresh();
        if ($index < 2) {
            expect($demand->status)->toBe(DemandStatus::PendingValidation)
                ->and($demand->current_step)->toBe($index + 2);
        }
    }

    expect($demand->fresh()->status)->toBe(DemandStatus::PendingBusinessDev);

    $this->actingAs($biz)
        ->post("/demands/{$demand->id}/business-approve", ['reason' => 'Business OK'])
        ->assertRedirect();

    expect($demand->fresh()->status)->toBe(DemandStatus::PendingClosure);

    $this->actingAs($admin)
        ->post("/demands/{$demand->id}/close")
        ->assertRedirect();

    expect($demand->fresh()->status)->toBe(DemandStatus::Closed);
});

it('refuses a demand and restarts validation on resubmit', function () {
    Storage::fake('local');
    $pm = User::factory()->projectManager()->create();
    $validators = createValidators(3);

    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);
    attachValidators($demand, $validators);
    seedPdfAttachment($demand, $pm);

    $this->actingAs($validators[0])
        ->post("/demands/{$demand->id}/refuse", ['reason' => 'Missing details'])
        ->assertRedirect();

    $demand->refresh();
    expect($demand->status)->toBe(DemandStatus::Refused)
        ->and($demand->refused_reason)->toBe('Missing details');

    $this->actingAs($pm)
        ->post("/demands/{$demand->id}", [
            'description' => 'Updated description',
            'brand_id' => $demand->brand_id,
            'material_nature_id' => $demand->material_nature_id,
            'validator_ids' => collect($validators)->pluck('id')->all(),
            'submit' => true,
        ])
        ->assertRedirect();

    $demand->refresh();
    expect($demand->status)->toBe(DemandStatus::PendingValidation)
        ->and($demand->current_step)->toBe(1)
        ->and($demand->validators()->where('status', 'pending')->count())->toBe(4);
});

it('allows approval without a reason but requires a reason to refuse', function () {
    $pm = User::factory()->projectManager()->create();
    $validators = createValidators(3);
    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);
    attachValidators($demand, $validators);
    seedPdfAttachment($demand, $pm);

    $this->actingAs($validators[0])
        ->post("/demands/{$demand->id}/approve")
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($demand->fresh()->current_step)->toBe(2);

    $this->actingAs($validators[1])
        ->post("/demands/{$demand->id}/refuse")
        ->assertSessionHasErrors('reason');

    expect($demand->fresh()->status)->toBe(DemandStatus::PendingValidation);
});

it('blocks a demand and only admin can unblock', function () {
    $pm = User::factory()->projectManager()->create();
    $validators = createValidators(3);
    $admin = User::factory()->admin()->create();
    $workflow = app(DemandWorkflowService::class);

    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 2,
    ]);
    attachValidators($demand, $validators);
    seedPdfAttachment($demand, $pm);

    $workflow->block($demand, $validators[1], 'Regulatory issue');

    $demand->refresh();
    expect($demand->status)->toBe(DemandStatus::Blocked)
        ->and($demand->current_step)->toBe(2)
        ->and($pm->can('unblock', $demand))->toBeFalse()
        ->and($admin->can('unblock', $demand))->toBeTrue();

    $workflow->unblock($demand, $admin);

    $demand->refresh();
    expect($demand->status)->toBe(DemandStatus::PendingValidation)
        ->and($demand->current_step)->toBe(2);
});

it('authorizes attachment download for visible users only', function () {
    $rm = User::factory()->responsableMarketing()->create();
    $pm = User::factory()->projectManager()->create(['manager_id' => $rm->id]);
    $stranger = User::factory()->projectManager()->create();

    $demand = Demand::factory()->create(['created_by' => $pm->id]);
    $attachment = seedPdfAttachment($demand, $pm);

    $this->actingAs($rm)
        ->get("/demands/{$demand->id}/attachments/{$attachment->id}")
        ->assertOk();

    $this->actingAs($stranger)
        ->get("/demands/{$demand->id}/attachments/{$attachment->id}")
        ->assertForbidden();
});

it('sends a child demand to the manager before validators', function () {
    Storage::fake('local');
    $rm = User::factory()->responsableMarketing()->create();
    $pm = User::factory()->projectManager()->create(['manager_id' => $rm->id]);
    $validators = createValidators(3);
    $brand = Brand::factory()->create();

    $this->actingAs($pm)->post('/demands', [
        'brand_id' => $brand->id,
        'material_nature_name' => 'Leaflet',
        'description' => 'Need leaflet',
        'validator_ids' => collect($validators)->pluck('id')->all(),
        'nature_materiel_files' => [UploadedFile::fake()->create('nature.pdf', 100, 'application/pdf')],
        'submit' => true,
    ])->assertRedirect();

    $demand = Demand::query()->first();
    expect($demand)->not->toBeNull()
        ->and($demand->status)->toBe(DemandStatus::PendingManager)
        ->and($demand->manager_id)->toBe($rm->id)
        ->and($demand->current_step)->toBeNull();

    $this->actingAs($validators[0])
        ->post("/demands/{$demand->id}/approve", ['reason' => 'Too early'])
        ->assertForbidden();

    $this->actingAs($rm)
        ->post("/demands/{$demand->id}/approve", ['reason' => 'Manager OK'])
        ->assertRedirect();

    $demand->refresh();
    expect($demand->status)->toBe(DemandStatus::PendingValidation)
        ->and($demand->current_step)->toBe(1)
        ->and($demand->manager_approved_at)->not->toBeNull();
});

it('skips manager approval when the creator has no manager', function () {
    Storage::fake('local');
    $rm = User::factory()->responsableMarketing()->create();
    $validators = createValidators(3);
    $brand = Brand::factory()->create();

    $this->actingAs($rm)->post('/demands', [
        'brand_id' => $brand->id,
        'material_nature_name' => 'Poster',
        'description' => 'Need poster',
        'validator_ids' => collect($validators)->pluck('id')->all(),
        'nature_materiel_files' => [UploadedFile::fake()->create('nature.pdf', 100, 'application/pdf')],
        'submit' => true,
    ])->assertRedirect();

    $demand = Demand::query()->first();
    expect($demand)->not->toBeNull()
        ->and($demand->status)->toBe(DemandStatus::PendingValidation)
        ->and($demand->manager_id)->toBeNull()
        ->and($demand->current_step)->toBe(1);
});

it('lets any reglementaire close the demand on the final group step', function () {
    $pm = User::factory()->projectManager()->create();
    $validators = createValidators(3);
    $regA = User::factory()->reglementaires()->create();
    $regB = User::factory()->reglementaires()->create();

    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 4,
    ]);
    attachValidators($demand, $validators);
    $demand->validators()->create([
        'user_id' => null,
        'role_name' => DemandWorkflowService::ROLE_REGLEMENTAIRES,
        'position' => 4,
        'status' => 'pending',
    ]);
    seedPdfAttachment($demand, $pm);

    $this->actingAs($regA)
        ->post("/demands/{$demand->id}/approve", ['reason' => 'Regulatory OK'])
        ->assertRedirect();

    $demand->refresh();
    expect($demand->status)->toBe(DemandStatus::Closed)
        ->and($demand->closed_by)->toBe($regA->id)
        ->and($demand->validators()->where('position', 4)->first()?->acted_by)->toBe($regA->id);

    // Second reglementaire would not act after close — demand already closed.
    expect($regB->can('validate', $demand))->toBeFalse();
});

it('stores supporting files when refusing a demand', function () {
    Storage::fake('local');
    $pm = User::factory()->projectManager()->create();
    $validators = createValidators(3);
    $demand = Demand::factory()->create([
        'created_by' => $pm->id,
        'status' => DemandStatus::PendingValidation,
        'current_step' => 1,
    ]);
    attachValidators($demand, $validators);
    seedPdfAttachment($demand, $pm);

    $this->actingAs($validators[0])
        ->post("/demands/{$demand->id}/refuse", [
            'reason' => 'Missing legal mention',
            'files' => [UploadedFile::fake()->create('note.pdf', 80, 'application/pdf')],
        ])
        ->assertRedirect();

    $demand->refresh();
    $decision = $demand->attachments()->where('collection', 'decision')->first();
    $event = $demand->events()->where('type', 'validator_refused')->first();

    expect($demand->status)->toBe(DemandStatus::Refused)
        ->and($decision)->not->toBeNull()
        ->and($decision?->demand_event_id)->toBe($event?->id)
        ->and($decision?->drive_file_id)->not->toBeNull();
});
