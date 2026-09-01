<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DemandAttachmentCollection;
use App\Models\Drive\DriveFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DemandAttachment extends Model
{
    protected $fillable = [
        'demand_id',
        'collection',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
        'drive_file_id',
        'demand_event_id',
    ];

    protected function casts(): array
    {
        return [
            'collection' => DemandAttachmentCollection::class,
            'size' => 'integer',
        ];
    }

    public function demand(): BelongsTo
    {
        return $this->belongsTo(Demand::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function driveFile(): BelongsTo
    {
        return $this->belongsTo(DriveFile::class, 'drive_file_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(DemandEvent::class, 'demand_event_id');
    }

    public function deleteFile(): void
    {
        if ($this->drive_file_id !== null) {
            return;
        }

        if ($this->path !== '' && Storage::disk($this->disk)->exists($this->path)) {
            Storage::disk($this->disk)->delete($this->path);
        }
    }
}
