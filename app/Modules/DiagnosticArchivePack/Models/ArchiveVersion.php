<?php

namespace App\Modules\DiagnosticArchivePack\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveVersion extends Model
{
    use HasFactory;

    protected $table = 'archive_versions';

    protected $fillable = [
        'archive_item_id',
        'version_number',
        'version_tag',
        'title',
        'description',
        'location_path',
        'payload_snapshot',
        'created_by',
        'produced_at',
        'archived_at',
        'changelog',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'payload_snapshot' => 'array',
        'produced_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ArchiveItem::class, 'archive_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
