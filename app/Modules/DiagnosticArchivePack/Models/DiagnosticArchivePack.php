<?php

namespace App\Modules\DiagnosticArchivePack\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiagnosticArchivePack extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_archive_packs';

    protected $fillable = [
        'mission_id',
        'reference',
        'title',
        'description',
        'status',
        'responsible_id',
        'closed_at',
        'closure_observations',
        'completeness_score',
        'input_snapshot',
        'output_snapshot',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'completeness_score' => 'integer',
        'input_snapshot' => 'array',
        'output_snapshot' => 'array',
    ];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class, 'mission_id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ArchiveItem::class, 'pack_id');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isCompiled(): bool
    {
        return $this->status === 'compiled';
    }
}
