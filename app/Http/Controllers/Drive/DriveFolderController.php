<?php

declare(strict_types=1);

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\StoreDriveFolderRequest;
use App\Http\Requests\Drive\UpdateDriveFolderRequest;
use App\Models\Drive\DriveFolder;
use App\Services\Drive\DriveWorkflowService;
use Illuminate\Http\RedirectResponse;

class DriveFolderController extends Controller
{
    public function __construct(private readonly DriveWorkflowService $workflow) {}

    public function store(StoreDriveFolderRequest $request): RedirectResponse
    {
        $this->authorize('create', DriveFolder::class);

        $parent = null;
        if ($request->filled('parent_id')) {
            $parent = DriveFolder::query()->findOrFail((int) $request->input('parent_id'));
            $this->authorize('update', $parent);
        }

        $folder = $this->workflow->createFolder(
            $request->user(),
            (string) $request->validated('name'),
            $parent,
        );

        return redirect()
            ->route('drive.index', ['folder' => $folder->parent_id])
            ->with('success', __('drive.flash.folder_created'));
    }

    public function update(UpdateDriveFolderRequest $request, DriveFolder $folder): RedirectResponse
    {
        $this->authorize('update', $folder);

        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $this->workflow->renameFolder($folder, (string) $data['name']);
        }

        if (array_key_exists('parent_id', $data)) {
            $destination = $data['parent_id'] !== null
                ? DriveFolder::query()->findOrFail((int) $data['parent_id'])
                : null;

            if ($destination !== null) {
                $this->authorize('update', $destination);
            }

            $this->workflow->moveFolder($folder, $destination);
        }

        return back()->with('success', __('drive.flash.folder_updated'));
    }

    public function destroy(DriveFolder $folder): RedirectResponse
    {
        $this->authorize('delete', $folder);
        $this->workflow->trashFolder($folder);

        return redirect()
            ->route('drive.index', ['folder' => $folder->parent_id])
            ->with('success', __('drive.flash.folder_trashed'));
    }

    public function restore(int $folder): RedirectResponse
    {
        $model = DriveFolder::onlyTrashed()->findOrFail($folder);
        $this->authorize('restore', $model);
        $this->workflow->restoreFolder($model);

        return back()->with('success', __('drive.flash.folder_restored'));
    }

    public function forceDestroy(int $folder): RedirectResponse
    {
        $model = DriveFolder::onlyTrashed()->findOrFail($folder);
        $this->authorize('forceDelete', $model);
        $this->workflow->forceDeleteFolder($model);

        return back()->with('success', __('drive.flash.folder_deleted'));
    }
}
