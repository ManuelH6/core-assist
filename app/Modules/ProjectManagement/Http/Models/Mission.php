<?php

namespace App\Modules\ProjectManagement\Http\Models;

use App\Models\User;
use App\Modules\ClientRelationship\Http\Models\Framing;
use App\Modules\Diagnostic360Matrix\Models\Diagnostic360Matrix;
use App\Modules\DiagnosticArchivePack\Models\DiagnosticArchivePack;
use App\Modules\DiagnosticIntakeRegister\Models\DiagnosticIntakeRegister;
use App\Modules\DiagnosticRestitutionDeck\Models\DiagnosticRestitutionDeck;
use App\Modules\DiagnosticScopeCanvas\Models\DiagnosticScopeCanvas;
use App\Modules\LiveDecisionCapture\Models\LiveDecisionCapture;
use App\Modules\MissionFramingSheet\Models\MissionFramingSheet;
use App\Modules\NextStepRouter\Models\NextStepRouter;
use App\Modules\ProblemStructuringGrid\Models\ProblemStructuringGrid;
use App\Modules\StakeholderInputGrid\Models\StakeholderInputGrid;
use App\Modules\ToolManagement\Http\Models\MissionToolUsage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'framing_id',
        'reference',
        'title',
        'description',
        'objective',
        'status',
        'created_by',
        'started_at',
        'closed_at',
        'closed_by',
        'start_date',
        'due_date',
        'budget',
        'manager_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
            'budget' => 'decimal:2',
        ];
    }

    public function framing(): BelongsTo
    {
        return $this->belongsTo(Framing::class);
    }

    public function framingSheet(): HasOne
    {
        return $this->hasOne(MissionFramingSheet::class);
    }

    public function scopeCanvas(): HasOne
    {
        return $this->hasOne(DiagnosticScopeCanvas::class);
    }

    public function intakeRegister(): HasOne
    {
        return $this->hasOne(DiagnosticIntakeRegister::class);
    }

    public function stakeholderGrid(): HasOne
    {
        return $this->hasOne(StakeholderInputGrid::class);
    }

    public function diagnosticMatrix(): HasOne
    {
        return $this->hasOne(Diagnostic360Matrix::class);
    }

    public function problemGrid(): HasOne
    {
        return $this->hasOne(ProblemStructuringGrid::class);
    }

    public function restitutionDeck(): HasOne
    {
        return $this->hasOne(DiagnosticRestitutionDeck::class);
    }

    public function decisionCapture(): HasOne
    {
        return $this->hasOne(LiveDecisionCapture::class);
    }

    public function archivePack(): HasOne
    {
        return $this->hasOne(DiagnosticArchivePack::class);
    }

    public function nextStepRouter(): HasOne
    {
        return $this->hasOne(NextStepRouter::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class, 'manager_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(MissionMember::class);
    }

    public function teamUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'mission_members')
            ->withPivot(['id', 'mission_role', 'status', 'joined_at'])
            ->withTimestamps();
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    public function toolUsages(): HasMany
    {
        return $this->hasMany(MissionToolUsage::class);
    }

    /**
     * Progression de la mission en pourcentage de tâches terminées.
     */
    public function progressPercentage(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        $done = $this->tasks()->where('status', 'done')->count();

        return (int) round(($done / $total) * 100);
    }

    /**
     * Volume d'heures par semaine mobilisé sur l'ensemble de la mission.
     */
    public function totalWeeklyHours(): float
    {
        return (float) $this->tasks()->whereIn('status', ['todo', 'doing', 'in_progress'])->sum('weekly_hours');
    }

    /**
     * Vérifier si la mission est en retard critique sur son échéance.
     */
    public function isOverdue(): bool
    {
        return $this->status !== 'completed'
            && $this->due_date !== null
            && $this->due_date->endOfDay()->isPast();
    }

    /**
     * Nombre de jours de retard.
     */
    public function daysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) max(1, $this->due_date->startOfDay()->diffInDays(now()->startOfDay()));
    }

    /**
     * Nombre de jours calendaires restants avant l'échéance.
     */
    public function daysRemaining(): int
    {
        if ($this->due_date === null) {
            return 0;
        }

        return (int) max(0, now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false));
    }

    /**
     * Vérifier si la mission présente un risque élevé (échéance sous 7 jours et avancement < 50%).
     */
    public function isAtRisk(): bool
    {
        if ($this->status === 'completed' || $this->due_date === null || $this->isOverdue()) {
            return false;
        }

        $daysRemaining = (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);

        return $daysRemaining >= 0 && $daysRemaining <= 7 && $this->progressPercentage() < 50;
    }

    /**
     * Scope pour les missions actives (non terminées).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed');
    }

    /**
     * Scope pour les missions en retard.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', today());
    }
}
