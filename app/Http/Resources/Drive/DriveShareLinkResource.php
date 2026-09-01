<?php

declare(strict_types=1);

namespace App\Http\Resources\Drive;

use App\Models\Drive\DriveShareLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DriveShareLink */
class DriveShareLinkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'url' => route('drive.shared.show', ['token' => $this->token]),
            'permission' => $this->permission?->value,
            'has_password' => $this->hasPassword(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'revoked_at' => $this->revoked_at?->toIso8601String(),
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
