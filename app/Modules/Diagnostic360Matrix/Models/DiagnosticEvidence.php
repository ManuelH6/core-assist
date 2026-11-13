<?php

namespace App\Modules\Diagnostic360Matrix\Models;

use App\Models\User;
use App\Modules\DiagnosticIntakeRegister\Models\ReceivedItem;
use App\Modules\StakeholderInputGrid\Models\ReceivedContribution;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DiagnosticEvidence extends Model
{
    use HasFactory;

    protected $table = 'diagnostic_evidences';

    protected $fillable = [
        'matrix_id',
        'source_type',
        'received_item_id',
        'received_contribution_id',
        'title',
        'content_summary',
        'assessed_reliability',
        'limits_or_reservations',
        'comments',
        'recorded_by',
    ];

    public function matrix(): BelongsTo
    {
        return $this->belongsTo(Diagnostic360Matrix::class, 'matrix_id');
    }

    public function receivedItem(): BelongsTo
    {
        return $this->belongsTo(ReceivedItem::class, 'received_item_id');
    }

    public function receivedContribution(): BelongsTo
    {
        return $this->belongsTo(ReceivedContribution::class, 'received_contribution_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function assessments(): BelongsToMany
    {
        return $this->belongsToMany(
            DiagnosticAssessment::class,
            'assessment_evidence',
            'evidence_id',
            'assessment_id'
        )->withPivot(['relevance_degree', 'notes'])->withTimestamps();
    }
}
