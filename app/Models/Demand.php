<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DemandStatus;
use Database\Factories\DemandFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Demand extends Model
{
    /** @use HasFactory<DemandFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'created_by',
        'manager_id',
        'manager_approved_at',
        'brand_id',
        'material_nature_id',
        'description',
        'status',
        'current_step',
        'refused_reason',
        'blocked_reason',
        'closed_at',
        'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DemandStatus::class,
            'current_step' => 'integer',
            'manager_approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function materialNature(): BelongsTo
    {
        return $this->belongsTo(MaterialNature::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function validators(): HasMany
    {
        return $this->hasMany(DemandValidator::class)->orderBy('position');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DemandAttachment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DemandEvent::class)->orderBy('created_at');
    }

    public function currentValidator(): ?DemandValidator
    {
        if ($this->current_step === null) {
            return null;
        }

        return $this->validators->firstWhere('position', $this->current_step);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('demands.view_all')) {
            return $query;
        }

        $reportIds = $user->reports()->pluck('id');

        return $query->where(function (Builder $q) use ($user, $reportIds): void {
            $q->where('created_by', $user->id);

            if ($reportIds->isNotEmpty()) {
                $q->orWhereIn('created_by', $reportIds);
            }

            $q->orWhere('manager_id', $user->id);

            $q->orWhereHas('validators', fn (Builder $vq) => $vq->where('user_id', $user->id));

            $roleNames = $user->getRoleNames()->all();
            if ($roleNames !== []) {
                $q->orWhereHas(
                    'validators',
                    fn (Builder $vq) => $vq->whereIn('role_name', $roleNames),
                );
            }

            if ($user->can('demands.business_validate')) {
                $q->orWhereIn('status', [
                    DemandStatus::PendingBusinessDev->value,
                    DemandStatus::PendingClosure->value,
                    DemandStatus::Closed->value,
                    DemandStatus::Blocked->value,
                    DemandStatus::Refused->value,
                ]);
            }
        });
    }
}
