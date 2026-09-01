<?php

declare(strict_types=1);

namespace App\Services\Drive;

use App\Enums\DriveSharePermission;
use App\Models\Drive\DriveFile;
use App\Models\Drive\DriveFolder;
use App\Models\Drive\DriveShare;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DriveAccessService
{
    public function canManageAll(User $user): bool
    {
        return $user->can('drive.manage');
    }

    public function effectivePermission(User $user, DriveFolder|DriveFile $item): ?DriveSharePermission
    {
        if ($this->canManageAll($user)) {
            return DriveSharePermission::Editor;
        }

        if ($item->owner_id === $user->id) {
            return DriveSharePermission::Editor;
        }

        $direct = $this->directSharePermission($user, $item);
        if ($direct !== null) {
            return $direct;
        }

        $folder = $item instanceof DriveFile ? $item->folder : $item->parent;
        while ($folder !== null) {
            if ($folder->owner_id === $user->id) {
                return DriveSharePermission::Editor;
            }

            $inherited = $this->directSharePermission($user, $folder);
            if ($inherited !== null) {
                return $inherited;
            }

            $folder = $folder->parent;
        }

        return null;
    }

    public function canView(User $user, DriveFolder|DriveFile $item): bool
    {
        return $this->effectivePermission($user, $item) !== null;
    }

    public function canEdit(User $user, DriveFolder|DriveFile $item): bool
    {
        return $this->canView($user, $item);
    }

    public function canShare(User $user, DriveFolder|DriveFile $item): bool
    {
        if (! $user->can('drive.share')) {
            return false;
        }

        return $this->canEdit($user, $item) || $item->owner_id === $user->id || $this->canManageAll($user);
    }

    /**
     * @param  Builder<DriveFolder>  $query
     * @return Builder<DriveFolder>
     */
    public function scopeVisibleFolders(Builder $query, User $user): Builder
    {
        if ($this->canManageAll($user)) {
            return $query;
        }

        $sharedFolderIds = $this->sharedFolderIdsIncludingDescendants($user);

        return $query->where(function (Builder $builder) use ($user, $sharedFolderIds): void {
            $builder->where('owner_id', $user->id);

            if ($sharedFolderIds !== []) {
                $builder->orWhereIn('id', $sharedFolderIds);
            }
        });
    }

    /**
     * @param  Builder<DriveFile>  $query
     * @return Builder<DriveFile>
     */
    public function scopeVisibleFiles(Builder $query, User $user): Builder
    {
        if ($this->canManageAll($user)) {
            return $query;
        }

        $sharedFolderIds = $this->sharedFolderIdsIncludingDescendants($user);
        $sharedFileIds = DriveShare::query()
            ->where('shareable_type', DriveFile::class)
            ->where('user_id', $user->id)
            ->pluck('shareable_id')
            ->all();

        return $query->where(function (Builder $builder) use ($user, $sharedFolderIds, $sharedFileIds): void {
            $builder->where('owner_id', $user->id);

            if ($sharedFileIds !== []) {
                $builder->orWhereIn('id', $sharedFileIds);
            }

            if ($sharedFolderIds !== []) {
                $builder->orWhereIn('folder_id', $sharedFolderIds);
            }
        });
    }

    /**
     * @return list<int>
     */
    public function sharedFolderIdsIncludingDescendants(User $user): array
    {
        $rootSharedIds = DriveShare::query()
            ->where('shareable_type', DriveFolder::class)
            ->where('user_id', $user->id)
            ->pluck('shareable_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($rootSharedIds === []) {
            return [];
        }

        return $this->descendantFolderIds($rootSharedIds);
    }

    /**
     * @param  list<int>  $rootIds
     * @return list<int>
     */
    public function descendantFolderIds(array $rootIds): array
    {
        $all = $rootIds;
        $frontier = $rootIds;

        while ($frontier !== []) {
            $children = DriveFolder::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $new = array_values(array_diff($children, $all));
            $all = [...$all, ...$new];
            $frontier = $new;
        }

        return $all;
    }

    /**
     * @return Collection<int, DriveFolder>
     */
    public function breadcrumb(DriveFolder $folder): Collection
    {
        $folder->loadMissing('parent');
        $crumbs = collect($folder->ancestors());
        $crumbs->push($folder);

        return $crumbs->values();
    }

    private function directSharePermission(User $user, DriveFolder|DriveFile $item): ?DriveSharePermission
    {
        $share = DriveShare::query()
            ->where('shareable_type', $item::class)
            ->where('shareable_id', $item->id)
            ->where('user_id', $user->id)
            ->first();

        return $share?->permission;
    }
}
