<?php

declare(strict_types=1);

namespace App\Http\Controllers\Drive;

use App\Http\Controllers\Controller;
use App\Http\Resources\Drive\DriveFileResource;
use App\Http\Resources\Drive\DriveFolderResource;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\User;
use App\Services\Drive\DriveAccessService;
use App\Services\Drive\DriveQuotaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DriveController extends Controller
{
    public function __construct(
        private readonly DriveAccessService $access,
        private readonly DriveQuotaService $quota,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        assert($user instanceof User);

        $this->authorize('viewAny', DriveFolder::class);

        $scope = $request->string('scope')->toString() ?: 'mine';
        $folderId = $request->filled('folder') ? (int) $request->input('folder') : null;
        $search = trim((string) $request->input('q', ''));
        $type = $request->string('type')->toString();
        $ownerId = $request->filled('owner_id') ? (int) $request->input('owner_id') : null;
        $minSize = $request->filled('min_size') ? (int) $request->input('min_size') : null;
        $maxSize = $request->filled('max_size') ? (int) $request->input('max_size') : null;
        $from = $request->input('from');
        $to = $request->input('to');

        $currentFolder = null;
        if ($folderId !== null) {
            $currentFolder = DriveFolder::query()->findOrFail($folderId);
            $this->authorize('view', $currentFolder);
        }

        $folderQuery = DriveFolder::query()->with(['owner', 'shares.user', 'shareLinks'])->orderBy('name');
        $fileQuery = DriveFile::query()->with(['owner', 'shares.user', 'shareLinks'])->orderBy('name');

        if ($scope === 'trash') {
            $folderQuery->onlyTrashed();
            $fileQuery->onlyTrashed();
            $this->access->scopeVisibleFolders($folderQuery, $user);
            $this->access->scopeVisibleFiles($fileQuery, $user);
        } elseif ($scope === 'shared') {
            $sharedFolderIds = $this->access->sharedFolderIdsIncludingDescendants($user);
            $directlySharedFileIds = DriveShare::query()
                ->where('shareable_type', DriveFile::class)
                ->where('user_id', $user->id)
                ->pluck('shareable_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $folderQuery->where(function ($q) use ($user, $sharedFolderIds, $folderId): void {
                $q->whereIn('id', $sharedFolderIds)->where('owner_id', '!=', $user->id);
                if ($folderId !== null) {
                    $q->where('parent_id', $folderId);
                } else {
                    // Shared roots only (folders that were shared, not their nested children).
                    $q->where(function ($inner) use ($sharedFolderIds): void {
                        $inner->whereNull('parent_id')
                            ->orWhereNotIn('parent_id', $sharedFolderIds);
                    });
                }
            });

            $fileQuery->where('owner_id', '!=', $user->id);
            if ($folderId !== null) {
                // Inside a shared folder: list files in that folder the user can see.
                $this->access->scopeVisibleFiles($fileQuery, $user);
                $fileQuery->where('folder_id', $folderId);
            } else {
                // Shared-with-me root: files shared directly with the user (any folder).
                $fileQuery->whereIn('id', $directlySharedFileIds !== [] ? $directlySharedFileIds : [0]);
            }
        } else {
            $this->access->scopeVisibleFolders($folderQuery, $user);
            $this->access->scopeVisibleFiles($fileQuery, $user);
            $folderQuery->inFolder($folderId);
            $fileQuery->inFolder($folderId);

            // Regular users: Mon Drive = owned items only. Drive admins (drive.manage) see all.
            if ($scope === 'mine' && ! $this->access->canManageAll($user)) {
                $folderQuery->where('owner_id', $user->id);
                $fileQuery->where('owner_id', $user->id);
            }
        }

        if ($search !== '') {
            $folderQuery->where('name', 'like', "%{$search}%");
            $fileQuery->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%");
            });
        }

        if ($ownerId !== null) {
            $folderQuery->where('owner_id', $ownerId);
            $fileQuery->where('owner_id', $ownerId);
        }

        if ($from) {
            $folderQuery->whereDate('created_at', '>=', $from);
            $fileQuery->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $folderQuery->whereDate('created_at', '<=', $to);
            $fileQuery->whereDate('created_at', '<=', $to);
        }

        if ($minSize !== null) {
            $fileQuery->where('size', '>=', $minSize);
        }

        if ($maxSize !== null) {
            $fileQuery->where('size', '<=', $maxSize);
        }

        if ($type !== '') {
            match ($type) {
                'image' => $fileQuery->where('mime', 'like', 'image/%'),
                'pdf' => $fileQuery->where('mime', 'application/pdf'),
                'office' => $fileQuery->where(function ($q): void {
                    $q->whereIn('mime', [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    ])->orWhere('name', 'like', '%.doc%')
                        ->orWhere('name', 'like', '%.xls%')
                        ->orWhere('name', 'like', '%.ppt%');
                }),
                'other' => $fileQuery->where(function ($q): void {
                    $q->where(function ($inner): void {
                        $inner->whereNull('mime')
                            ->orWhere(function ($m): void {
                                $m->where('mime', 'not like', 'image/%')
                                    ->where('mime', '!=', 'application/pdf');
                            });
                    });
                }),
                default => null,
            };

            if ($type !== 'folder') {
                $folderQuery->whereRaw('1 = 0');
            } else {
                $fileQuery->whereRaw('1 = 0');
            }
        }

        $folders = $folderQuery->limit(200)->get();
        $files = $fileQuery->limit(200)->get();

        $breadcrumbs = $currentFolder
            ? $this->access->breadcrumb($currentFolder)->map(fn (DriveFolder $f) => [
                'id' => $f->id,
                'name' => $f->name,
            ])->values()->all()
            : [];

        $shareUsers = User::query()
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'email']);

        $moveFolderQuery = DriveFolder::query()->orderBy('name');
        $this->access->scopeVisibleFolders($moveFolderQuery, $user);
        $moveFolders = $moveFolderQuery
            ->limit(300)
            ->get(['id', 'name', 'parent_id'])
            ->map(fn (DriveFolder $folder) => [
                'id' => $folder->id,
                'name' => $folder->name,
                'parent_id' => $folder->parent_id,
            ])
            ->values()
            ->all();

        $canEditCurrent = $currentFolder === null
            ? $user->can('drive.upload')
            : $this->access->canEdit($user, $currentFolder);

        return Inertia::render('drive/Index', [
            'folders' => DriveFolderResource::collection($folders)->resolve(),
            'files' => DriveFileResource::collection($files)->resolve(),
            'currentFolder' => $currentFolder
                ? (new DriveFolderResource($currentFolder->load(['owner', 'shares.user', 'shareLinks'])))->resolve()
                : null,
            'breadcrumbs' => $breadcrumbs,
            'filters' => [
                'scope' => $scope,
                'folder' => $folderId,
                'q' => $search,
                'type' => $type,
                'owner_id' => $ownerId,
                'min_size' => $minSize,
                'max_size' => $maxSize,
                'from' => $from,
                'to' => $to,
            ],
            'storage' => $this->quota->usageFor($user),
            'shareUsers' => $shareUsers,
            'moveFolders' => $moveFolders,
            'can' => [
                'upload' => $user->can('drive.upload') && $canEditCurrent,
                'share' => $user->can('drive.share'),
                'manage' => $user->can('drive.manage'),
                'manage_quota' => $user->can('drive.manage_quota'),
            ],
        ]);
    }

    public function trash(Request $request): Response
    {
        $request->merge(['scope' => 'trash']);

        return $this->index($request);
    }
}
