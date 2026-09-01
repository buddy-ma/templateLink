<?php

declare(strict_types=1);

namespace App\Http\Controllers\Demand;

use App\Http\Controllers\Controller;
use App\Http\Requests\Demand\UpdateValidationPipelineRequest;
use App\Models\User;
use App\Models\ValidationPipeline;
use App\Models\ValidationPipelineStep;
use App\Services\Demand\DemandWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ValidationPipelineController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('demands.manage_pipeline'), 403);

        $pipeline = ValidationPipeline::query()
            ->where('is_default', true)
            ->with(['steps.user'])
            ->first();

        if ($pipeline === null) {
            $pipeline = ValidationPipeline::query()->create([
                'name' => 'Default pipeline',
                'is_default' => true,
            ]);
            $pipeline->load(['steps.user']);
        }

        $userSteps = $pipeline->steps->filter(fn (ValidationPipelineStep $step) => $step->user_id !== null);

        return Inertia::render('demands/pipeline/Index', [
            'pipeline' => [
                'id' => $pipeline->id,
                'name' => $pipeline->name,
                'is_default' => $pipeline->is_default,
                'validator_ids' => $userSteps->pluck('user_id')->values()->all(),
                'final_group_role' => DemandWorkflowService::ROLE_REGLEMENTAIRES,
                'steps' => $pipeline->steps->map(fn (ValidationPipelineStep $step) => [
                    'id' => $step->id,
                    'position' => $step->position,
                    'user_id' => $step->user_id,
                    'role_name' => $step->role_name,
                    'is_group' => $step->isGroupStep(),
                    'user' => $step->user ? [
                        'id' => $step->user->id,
                        'name' => $step->user->name,
                        'email' => $step->user->email,
                    ] : null,
                ])->values()->all(),
            ],
            'validators' => User::role('validator')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'minValidators' => DemandWorkflowService::MIN_VALIDATORS,
        ]);
    }

    public function update(UpdateValidationPipelineRequest $request, ValidationPipeline $pipeline): RedirectResponse
    {
        $ids = array_values(array_unique(array_map('intval', $request->input('validator_ids', []))));

        if (count($ids) < DemandWorkflowService::MIN_VALIDATORS) {
            return back()->withErrors([
                'validator_ids' => __('demands.messages.validators_required', [
                    'count' => DemandWorkflowService::MIN_VALIDATORS,
                ]),
            ]);
        }

        DB::transaction(function () use ($request, $pipeline, $ids): void {
            $pipeline->update([
                'name' => $request->string('name')->toString(),
                'is_default' => true,
            ]);

            if ($request->boolean('is_default', true)) {
                ValidationPipeline::query()
                    ->where('id', '!=', $pipeline->id)
                    ->update(['is_default' => false]);
            }

            $pipeline->steps()->delete();

            $position = 1;
            foreach ($ids as $userId) {
                ValidationPipelineStep::query()->create([
                    'pipeline_id' => $pipeline->id,
                    'user_id' => $userId,
                    'role_name' => null,
                    'position' => $position,
                ]);
                $position++;
            }

            ValidationPipelineStep::query()->create([
                'pipeline_id' => $pipeline->id,
                'user_id' => null,
                'role_name' => DemandWorkflowService::ROLE_REGLEMENTAIRES,
                'position' => $position,
            ]);
        });

        return back()->with('success', __('demands.messages.pipeline_updated'));
    }
}
