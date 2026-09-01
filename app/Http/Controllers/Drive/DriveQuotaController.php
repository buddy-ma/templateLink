<?php

declare(strict_types=1);

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\UpdateDriveQuotaRequest;
use App\Services\Drive\DriveQuotaService;
use Illuminate\Http\RedirectResponse;

class DriveQuotaController extends Controller
{
    public function __construct(private readonly DriveQuotaService $quota) {}

    public function update(UpdateDriveQuotaRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->can('drive.manage_quota'), 403);

        $this->quota->setQuotaBytes((int) $request->validated('quota_bytes'));

        return back()->with('success', __('drive.flash.quota_updated'));
    }
}
