<?php

namespace App\Modules\ProblemStructuringGrid\Models;

use App\Modules\Diagnostic360Matrix\Models\DiagnosticEvidence;
use App\Modules\DiagnosticIntakeRegister\Models\ReceivedItem;
use App\Modules\StakeholderInputGrid\Models\ReceivedContribution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProblemEvidence extends Model
{
    use HasFactory;

    protected $table = 'problem_evidences';

    protected $fillable = [
        'grid_id',
        'problem_id',
        'cause_id',
        'consequence_id',
        'source_type',
        'd360_evidence_id',
        'received_item_id',
        'received_contribution_id',
        'title',
        'content_summary',
        'assessed_reliability',
        'notes',
    ];

    public function grid(): BelongsTo
    {
        return $this->belongsTo(ProblemStructuringGrid::class, 'grid_id');
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(StructuredProblem::class, 'problem_id');
    }

    public function cause(): BelongsTo
    {
        return $this->belongsTo(ProblemCause::class, 'cause_id');
    }

    public function consequence(): BelongsTo
    {
        return $this->belongsTo(ProblemConsequence::class, 'consequence_id');
    }

    public function d360Evidence(): BelongsTo
    {
        return $this->belongsTo(DiagnosticEvidence::class, 'd360_evidence_id');
    }

    public function receivedItem(): BelongsTo
    {
        return $this->belongsTo(ReceivedItem::class, 'received_item_id');
    }

    public function receivedContribution(): BelongsTo
    {
        return $this->belongsTo(ReceivedContribution::class, 'received_contribution_id');
    }
}
