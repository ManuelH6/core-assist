<?php

namespace App\Modules\DiagnosticIntakeRegister\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReceivedItem extends Model
{
    use HasFactory;

    protected $table = 'received_items';

    protected $fillable = [
        'register_id',
        'title',
        'item_type',
        'source_origin',
        'file_path',
        'file_name',
        'file_size',
        'received_at',
        'received_by',
        'reliability_score',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }

    public function register(): BelongsTo
    {
        return $this->belongsTo(DiagnosticIntakeRegister::class, 'register_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function requiredInformation(): BelongsToMany
    {
        return $this->belongsToMany(RequiredInformation::class, 'received_item_information')
            ->withPivot(['coverage_degree', 'notes'])
            ->withTimestamps();
    }
}
