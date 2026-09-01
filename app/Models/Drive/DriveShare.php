<?php

declare(strict_types=1);

namespace App\Models\Drive;

use App\Enums\DriveSharePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DriveShare extends Model
{
    protected $fillable = [
        'shareable_type',
        'shareable_id',
        'user_id',
        'permission',
        'shared_by',
    ];

    protected function casts(): array
    {
        return [
            'permission' => DriveSharePermission::class,
        ];
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shared_by');
    }
}
