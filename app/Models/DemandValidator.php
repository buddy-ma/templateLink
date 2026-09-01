<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DemandValidatorStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemandValidator extends Model
{
    protected $fillable = [
        'demand_id',
        'user_id',
        'role_name',
        'position',
        'status',
        'acted_at',
        'comment',
        'acted_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DemandValidatorStatus::class,
            'position' => 'integer',
            'acted_at' => 'datetime',
        ];
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(Demand::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function isGroupStep(): bool
    {
        return filled($this->role_name);
    }

    public function canBeActedBy(User $actor): bool
    {
        if ($this->isGroupStep()) {
            return $actor->hasRole((string) $this->role_name);
        }

        return $this->user_id === $actor->id;
    }
}
