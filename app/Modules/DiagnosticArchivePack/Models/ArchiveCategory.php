<?php

namespace App\Modules\DiagnosticArchivePack\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveCategory extends Model
{
    use HasFactory;

    protected $table = 'archive_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'position',
        'is_system',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_system' => 'boolean',
    ];

    public function primaryItems(): HasMany
    {
        return $this->hasMany(ArchiveItem::class, 'category_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(
            ArchiveItem::class,
            'archive_item_categories',
            'archive_category_id',
            'archive_item_id'
        )->withTimestamps();
    }
}
