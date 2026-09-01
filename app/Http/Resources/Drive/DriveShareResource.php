<?php

declare(strict_types=1);

namespace App\Http\Resources\Drive;

use App\Models\Drive\DriveShare;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DriveShare */
class DriveShareResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            'permission' => $this->permission?->value,
            'shared_by' => $this->shared_by,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
