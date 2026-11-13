<?php

namespace App\Modules\DiagnosticArchivePack\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveItem extends Model
{
    use HasFactory;

    protected $table = 'archive_items';

    protected $fillable = [
        'pack_id',
        'category_id',
        'reference',
        'title',
        'item_type',
        'source_tool',
        'source_id',
        'source_reference',
        'current_version_number',
        'author_name',
        'author_id',
        'produced_at',
        'archived_at',
        'description',
        'location_type',
        'location_path',
        'storage_disk',
        'metadata',
        'status',
    ];

    protected $casts = [
        'current_version_number' => 'integer',
        'produced_at' => 'datetime',
        'archived_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function pack(): BelongsTo
    {
        return $this->belongsTo(DiagnosticArchivePack::class, 'pack_id');
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(ArchiveCategory::class, 'category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            ArchiveCategory::class,
            'archive_item_categories',
            'archive_item_id',
            'archive_category_id'
        )->withTimestamps();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ArchiveVersion::class, 'archive_item_id')->orderByDesc('version_number');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(ArchiveSource::class, 'archive_item_id');
    }
}
