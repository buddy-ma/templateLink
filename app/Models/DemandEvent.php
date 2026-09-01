<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemandEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'demand_id',
        'actor_id',
        'type',
        'from_status',
        'to_status',
        'step',
        'comment',
        'meta',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'step' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(Demand::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DemandAttachment::class, 'demand_event_id');
    }
}
