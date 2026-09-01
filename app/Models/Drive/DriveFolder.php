<?php

declare(strict_types=1);

namespace App\Models\Drive;

use App\Models\User;
use Database\Factories\DriveFolderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriveFolder extends Model
{
    /** @use HasFactory<DriveFolderFactory> */
    use HasFactory;

    use SoftDeletes;

    protected static function newFactory(): DriveFolderFactory
    {
        return DriveFolderFactory::new();
    }

    protected $fillable = [
        'parent_id',
        'name',
        'owner_id',
        'created_by',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(DriveFile::class, 'folder_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(DriveShare::class, 'shareable');
    }

    public function shareLinks(): MorphMany
    {
        return $this->morphMany(DriveShareLink::class, 'shareable');
    }

    /**
     * @return list<self>
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current !== null) {
            $ancestors[] = $current;
            $current = $current->parent;
        }

        return array_reverse($ancestors);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInFolder(Builder $query, ?int $parentId): Builder
    {
        return $parentId === null
            ? $query->whereNull('parent_id')
            : $query->where('parent_id', $parentId);
    }
}
