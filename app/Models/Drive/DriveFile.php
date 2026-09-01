<?php

declare(strict_types=1);

namespace App\Models\Drive;

use App\Models\User;
use Database\Factories\DriveFileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class DriveFile extends Model
{
    /** @use HasFactory<DriveFileFactory> */
    use HasFactory;

    use SoftDeletes;

    protected static function newFactory(): DriveFileFactory
    {
        return DriveFileFactory::new();
    }

    protected $fillable = [
        'folder_id',
        'name',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'owner_id',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DriveFolder::class, 'folder_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function shares(): MorphMany
    {
        return $this->morphMany(DriveShare::class, 'shareable');
    }

    public function shareLinks(): MorphMany
    {
        return $this->morphMany(DriveShareLink::class, 'shareable');
    }

    public function deleteFile(): void
    {
        if ($this->path !== '' && Storage::disk($this->disk)->exists($this->path)) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }

    public function isPreviewableInline(): bool
    {
        $mime = (string) $this->mime;

        return str_starts_with($mime, 'image/') || $mime === 'application/pdf';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInFolder(Builder $query, ?int $folderId): Builder
    {
        return $folderId === null
            ? $query->whereNull('folder_id')
            : $query->where('folder_id', $folderId);
    }
}
