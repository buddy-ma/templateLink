<?php

declare(strict_types=1);

namespace App\Models\Drive;

use App\Enums\DriveSharePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DriveShareLink extends Model
{
    protected $fillable = [
        'shareable_type',
        'shareable_id',
        'token',
        'password',
        'permission',
        'expires_at',
        'created_by',
        'revoked_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'permission' => DriveSharePermission::class,
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function generateToken(): string
    {
        return Str::random(48);
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasPassword(): bool
    {
        return filled($this->password);
    }

    public function checkPassword(?string $plain): bool
    {
        if (! $this->hasPassword()) {
            return true;
        }

        return is_string($plain) && Hash::check($plain, $this->password);
    }

    public function revoke(): void
    {
        $this->forceFill(['revoked_at' => now()])->save();
    }
}
