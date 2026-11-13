<?php

namespace App\Modules\DiagnosticArchivePack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveSource extends Model
{
    use HasFactory;

    protected $table = 'archive_sources';

    protected $fillable = [
        'archive_item_id',
        'source_tool',
        'source_type',
        'source_id',
        'source_reference',
        'relationship_type',
        'notes',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ArchiveItem::class, 'archive_item_id');
    }
}
