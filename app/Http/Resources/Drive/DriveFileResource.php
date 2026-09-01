<?php

declare(strict_types=1);

namespace App\Http\Resources\Drive;

use App\Models\Drive\DriveFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DriveFile */
class DriveFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'file',
            'name' => $this->name,
            'folder_id' => $this->folder_id,
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size,
            'owner_id' => $this->owner_id,
            'owner' => $this->whenLoaded('owner', fn () => [
                'id' => $this->owner?->id,
                'name' => $this->owner?->name,
                'email' => $this->owner?->email,
            ]),
            'uploaded_by' => $this->uploaded_by,
            'previewable' => $this->isPreviewableInline(),
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
