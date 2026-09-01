<?php

declare(strict_types=1);

namespace App\Http\Controllers\Drive;

use App\Enums\DriveSharePermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\StoreDriveShareRequest;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\User;
use App\Services\Drive\DriveWorkflowService;
use Illuminate\Http\RedirectResponse;

class DriveShareController extends Controller
{
    public function __construct(private readonly DriveWorkflowService $workflow) {}

    public function storeFolder(StoreDriveShareRequest $request, DriveFolder $folder): RedirectResponse
    {
        $this->authorize('share', $folder);

        return $this->store($request, $folder);
    }

    public function storeFile(StoreDriveShareRequest $request, DriveFile $file): RedirectResponse
    {
        $this->authorize('share', $file);

        return $this->store($request, $file);
    }

    public function destroy(DriveShare $share): RedirectResponse
    {
        $shareable = $share->shareable;
        if ($shareable instanceof DriveFolder || $shareable instanceof DriveFile) {
            $this->authorize('share', $shareable);
        } else {
            abort(404);
        }

        $this->workflow->revokeShare($share);

        return back()->with('success', __('drive.flash.share_revoked'));
    }

    private function store(StoreDriveShareRequest $request, DriveFolder|DriveFile $item): RedirectResponse
    {
        $recipient = User::query()->findOrFail((int) $request->validated('user_id'));

        if ($recipient->id === $request->user()->id) {
            return back()->withErrors(['user_id' => __('drive.errors.cannot_share_self')]);
        }

        $this->workflow->shareWithUser(
            $request->user(),
            $item,
            $recipient,
            DriveSharePermission::default(),
        );

        return back()->with('success', __('drive.flash.shared'));
    }
}
