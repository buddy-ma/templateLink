<?php

declare(strict_types=1);

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\StoreDriveFileRequest;
use App\Http\Requests\Drive\UpdateDriveFileRequest;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Services\Drive\DriveWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveFileController extends Controller
{
    public function __construct(private readonly DriveWorkflowService $workflow) {}

    public function store(StoreDriveFileRequest $request): RedirectResponse
    {
        $this->authorize('create', DriveFile::class);

        $folder = null;
        if ($request->filled('folder_id')) {
            $folder = DriveFolder::query()->findOrFail((int) $request->input('folder_id'));
            $this->authorize('update', $folder);
        }

        $file = $this->workflow->uploadFile(
            $request->user(),
            $request->file('file'),
            $folder,
            $request->validated('name'),
        );

        return redirect()
            ->route('drive.index', ['folder' => $file->folder_id])
            ->with('success', __('drive.flash.file_uploaded'));
    }

    public function update(UpdateDriveFileRequest $request, DriveFile $file): RedirectResponse
    {
        $this->authorize('update', $file);

        $data = $request->validated();

        if (array_key_exists('name', $data)) {
            $this->workflow->renameFile($file, (string) $data['name']);
        }

        if (array_key_exists('folder_id', $data)) {
            $destination = $data['folder_id'] !== null
                ? DriveFolder::query()->findOrFail((int) $data['folder_id'])
                : null;

            if ($destination !== null) {
                $this->authorize('update', $destination);
            }

            $this->workflow->moveFile($file, $destination);
        }

        return back()->with('success', __('drive.flash.file_updated'));
    }

    public function destroy(DriveFile $file): RedirectResponse
    {
        $this->authorize('delete', $file);
        $folderId = $file->folder_id;
        $this->workflow->trashFile($file);

        return redirect()
            ->route('drive.index', ['folder' => $folderId])
            ->with('success', __('drive.flash.file_trashed'));
    }

    public function restore(int $file): RedirectResponse
    {
        $model = DriveFile::onlyTrashed()->findOrFail($file);
        $this->authorize('restore', $model);
        $this->workflow->restoreFile($model);

        return back()->with('success', __('drive.flash.file_restored'));
    }

    public function forceDestroy(int $file): RedirectResponse
    {
        $model = DriveFile::onlyTrashed()->findOrFail($file);
        $this->authorize('forceDelete', $model);
        $this->workflow->forceDeleteFile($model);

        return back()->with('success', __('drive.flash.file_deleted'));
    }

    public function download(DriveFile $file): StreamedResponse
    {
        $this->authorize('download', $file);

        $inline = request()->boolean('inline') && $file->isPreviewableInline();

        return Storage::disk($file->disk)->response(
            $file->path,
            $file->original_name,
            [
                'Content-Type' => $file->mime ?: 'application/octet-stream',
            ],
            $inline ? 'inline' : 'attachment',
        );
    }
}
