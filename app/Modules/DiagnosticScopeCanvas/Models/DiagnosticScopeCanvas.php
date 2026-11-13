<?php

namespace App\Modules\DiagnosticScopeCanvas\Models;

use App\Models\User;
use App\Modules\ProjectManagement\Http\Models\Mission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiagnosticScopeCanvas extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_scope_canvases';

    protected $fillable = [
        'mission_id',
        'title',
        'scope_items',
        'status',
        'submitted_at',
        'validated_at',
        'created_by',
        'validated_by',
        'input_snapshot',
        'output_snapshot',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope_items' => 'array',
            'input_snapshot' => 'array',
            'output_snapshot' => 'array',
            'submitted_at' => 'datetime',
            'validated_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Obtenir les éléments inclus du périmètre.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getIncludedItemsAttribute(): array
    {
        return array_values(array_filter($this->scope_items ?? [], function (array $item) {
            return ($item['status'] ?? '') === 'included';
        }));
    }

    /**
     * Obtenir les éléments exclus du périmètre.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getExcludedItemsAttribute(): array
    {
        return array_values(array_filter($this->scope_items ?? [], function (array $item) {
            return ($item['status'] ?? '') === 'excluded';
        }));
    }

    /**
     * Obtenir les éléments à confirmer.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getToConfirmItemsAttribute(): array
    {
        return array_values(array_filter($this->scope_items ?? [], function (array $item) {
            return ($item['status'] ?? '') === 'to_confirm';
        }));
    }

    /**
     * Obtenir la liste distincte des grands domaines inclus.
     *
     * @return array<int, string>
     */
    public function getIncludedDomainsAttribute(): array
    {
        $domains = [];
        foreach ($this->included_items as $item) {
            if (! empty($item['domain']) && ! in_array($item['domain'], $domains, true)) {
                $domains[] = $item['domain'];
            }
        }

        return $domains;
    }
}
