<?php

declare(strict_types=1);

namespace App\Http\Controllers\Drive;

use App\Enums\DriveSharePermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\Drive\StoreDriveShareLinkRequest;
use App\Http\Requests\Drive\UnlockDriveShareLinkRequest;
use App\Http\Resources\Drive\DriveFileResource;
use App\Http\Resources\Drive\DriveFolderResource;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShareLink;
use App\Services\Drive\DriveWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DriveShareLinkController extends Controller
{
    public function __construct(private readonly DriveWorkflowService $workflow) {}

    public function storeFolder(StoreDriveShareLinkRequest $request, DriveFolder $folder): RedirectResponse
    {
        $this->authorize('share', $folder);

        return $this->store($request, $folder);
    }

    public function storeFile(StoreDriveShareLinkRequest $request, DriveFile $file): RedirectResponse
    {
        $this->authorize('share', $file);

        return $this->store($request, $file);
    }

    public function destroy(DriveShareLink $shareLink): RedirectResponse
    {
        $shareable = $shareLink->shareable;
        if ($shareable instanceof DriveFolder || $shareable instanceof DriveFile) {
            $this->authorize('share', $shareable);
        } else {
            abort(404);
        }

        $this->workflow->revokeShareLink($shareLink);

        return back()->with('success', __('drive.flash.link_revoked'));
    }

    public function show(Request $request, string $token): Response|RedirectResponse
    {
        $link = DriveShareLink::query()->where('token', $token)->firstOrFail();

        if (! $link->isActive()) {
            abort(410, __('drive.errors.link_inactive'));
        }

        if ($link->hasPassword() && ! $this->linkUnlocked($request, $link)) {
            return Inertia::render('drive/shared/Show', [
                'requiresPassword' => true,
                'token' => $token,
                'item' => null,
                'folders' => [],
                'files' => [],
            ]);
        }

        $item = $link->shareable;
        if (! $item instanceof DriveFolder && ! $item instanceof DriveFile) {
            abort(404);
        }

        if ($item instanceof DriveFile) {
            return Inertia::render('drive/shared/Show', [
                'requiresPassword' => false,
                'token' => $token,
                'item' => (new DriveFileResource($item))->resolve(),
                'folders' => [],
                'files' => [],
                'permission' => $link->permission->value,
            ]);
        }

        $folderId = $request->filled('folder') ? (int) $request->input('folder') : $item->id;
        $current = DriveFolder::query()->findOrFail($folderId);

        if (! $this->isWithinSharedFolder($item, $current)) {
            abort(403);
        }

        $folders = DriveFolder::query()->inFolder($current->id)->orderBy('name')->get();
        $files = DriveFile::query()->inFolder($current->id)->orderBy('name')->get();

        return Inertia::render('drive/shared/Show', [
            'requiresPassword' => false,
            'token' => $token,
            'item' => (new DriveFolderResource($current))->resolve(),
            'folders' => DriveFolderResource::collection($folders)->resolve(),
            'files' => DriveFileResource::collection($files)->resolve(),
            'permission' => $link->permission->value,
            'rootFolderId' => $item->id,
        ]);
    }

    public function unlock(UnlockDriveShareLinkRequest $request, string $token): RedirectResponse
    {
        $link = DriveShareLink::query()->where('token', $token)->firstOrFail();

        if (! $link->isActive()) {
            abort(410, __('drive.errors.link_inactive'));
        }

        if (! $link->checkPassword($request->validated('password'))) {
            return back()->withErrors(['password' => __('drive.errors.invalid_password')]);
        }

        $request->session()->put($this->sessionKey($link), true);

        return redirect()->route('drive.shared.show', ['token' => $token]);
    }

    public function download(Request $request, string $token, DriveFile $file): StreamedResponse
    {
        $link = DriveShareLink::query()->where('token', $token)->firstOrFail();

        if (! $link->isActive()) {
            abort(410, __('drive.errors.link_inactive'));
        }

        if ($link->hasPassword() && ! $this->linkUnlocked($request, $link)) {
            abort(403);
        }

        $shareable = $link->shareable;

        if ($shareable instanceof DriveFile) {
            if ($shareable->id !== $file->id) {
                abort(403);
            }
        } elseif ($shareable instanceof DriveFolder) {
            if ($file->folder_id === null || ! $this->fileWithinFolderTree($shareable, $file)) {
                abort(403);
            }
        } else {
            abort(404);
        }

        $inline = $request->boolean('inline') && $file->isPreviewableInline();

        return Storage::disk($file->disk)->response(
            $file->path,
            $file->original_name,
            [
                'Content-Type' => $file->mime ?: 'application/octet-stream',
            ],
            $inline ? 'inline' : 'attachment',
        );
    }

    private function store(StoreDriveShareLinkRequest $request, DriveFolder|DriveFile $item): RedirectResponse
    {
        $password = $request->validated('password');
        $expiresAt = $request->validated('expires_at');

        $link = $this->workflow->createShareLink(
            $request->user(),
            $item,
            DriveSharePermission::default(),
            is_string($password) ? $password : null,
            $expiresAt ? new \DateTimeImmutable((string) $expiresAt) : null,
        );

        return back()->with('success', __('drive.flash.link_created'))
            ->with('drive_share_link_url', route('drive.shared.show', ['token' => $link->token]));
    }

    private function linkUnlocked(Request $request, DriveShareLink $link): bool
    {
        return (bool) $request->session()->get($this->sessionKey($link), false);
    }

    private function sessionKey(DriveShareLink $link): string
    {
        return 'drive_share_link_'.$link->id;
    }

    private function isWithinSharedFolder(DriveFolder $root, DriveFolder $current): bool
    {
        if ($current->id === $root->id) {
            return true;
        }

        $cursor = $current;
        while ($cursor->parent_id !== null) {
            if ($cursor->parent_id === $root->id) {
                return true;
            }
            $cursor = $cursor->parent;
            if ($cursor === null) {
                break;
            }
        }

        return false;
    }

    private function fileWithinFolderTree(DriveFolder $root, DriveFile $file): bool
    {
        if ($file->folder_id === $root->id) {
            return true;
        }

        $folder = $file->folder;
        while ($folder !== null) {
            if ($folder->id === $root->id) {
                return true;
            }
            $folder = $folder->parent;
        }

        return false;
    }
}
