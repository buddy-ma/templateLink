<?php

declare(strict_types=1);

namespace App\Http\Controllers\Demand;

use App\Http\Controllers\Controller;
use App\Http\Requests\Demand\DemandActionRequest;
use App\Models\Demand;
use App\Services\Demand\DemandWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class DemandValidationController extends Controller
{
    public function __construct(
        private readonly DemandWorkflowService $workflow,
    ) {}

    public function approve(DemandActionRequest $request, Demand $demand): RedirectResponse
    {
        $reason = $this->optionalReason($request);
        $files = $request->uploadedFiles();
        $user = $request->user();

        if ($user->can('managerValidate', $demand)) {
            $this->workflow->managerApprove($demand, $user, $reason, $files);

            return back()->with('success', __('demands.messages.manager_approved'));
        }

        $this->authorize('validate', $demand);
        $this->workflow->approve($demand, $user, $reason, $files);

        return back()->with('success', __('demands.messages.approved'));
    }

    public function refuse(DemandActionRequest $request, Demand $demand): RedirectResponse
    {
        $this->authorize('refuseOrBlock', $demand);
        $reason = $this->requiredReason($request);

        $this->workflow->refuse($demand, $request->user(), $reason, $request->uploadedFiles());

        return back()->with('success', __('demands.messages.refused'));
    }

    public function businessApprove(DemandActionRequest $request, Demand $demand): RedirectResponse
    {
        $this->authorize('businessValidate', $demand);
        $reason = $this->optionalReason($request);
        $this->workflow->businessApprove($demand, $request->user(), $reason, $request->uploadedFiles());

        return back()->with('success', __('demands.messages.business_approved'));
    }

    public function close(Demand $demand): RedirectResponse
    {
        $this->authorize('close', $demand);
        $this->workflow->close($demand, request()->user());

        return back()->with('success', __('demands.messages.closed'));
    }

    private function optionalReason(DemandActionRequest $request): ?string
    {
        $reason = (string) ($request->input('reason') ?: $request->input('comment') ?: '');

        return trim(strip_tags($reason)) === '' ? null : $reason;
    }

    private function requiredReason(DemandActionRequest $request): string
    {
        $reason = $this->optionalReason($request);
        if ($reason === null) {
            throw ValidationException::withMessages([
                'reason' => __('demands.messages.reason_required'),
            ]);
        }

        return $reason;
    }
}
