<?php

declare(strict_types=1);

namespace App\Http\Resources\Drive;

use App\Models\Drive\DriveFolder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DriveFolder */
class DriveFolderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'folder',
            'name' => $this->name,
            'parent_id' => $this->parent_id,
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
                'email' => $this->owner?->email,
            ]),
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            'shares' => $this->when(
                $this->relationLoaded('shares'),
                fn () => $this->shares
                    ->map(fn ($share) => (new DriveShareResource($share))->resolve())
                    ->values()
                    ->all(),
            ),
            'share_links' => $this->when(
                $this->relationLoaded('shareLinks'),
                fn () => $this->shareLinks
                    ->map(fn ($link) => (new DriveShareLinkResource($link))->resolve())
                    ->values()
                    ->all(),
            ),
        ];
    }
}
