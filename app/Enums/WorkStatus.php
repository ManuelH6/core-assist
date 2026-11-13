<?php

namespace App\Enums;

enum WorkStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case RevisionRequested = 'revision_requested';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon privé',
            self::Planned => 'Planifié',
            self::InProgress => 'En cours d\'investigation',
            self::Submitted => 'Soumis pour revue',
            self::UnderReview => 'En cours d\'examen',
            self::RevisionRequested => 'Corrections demandées',
            self::Completed => 'Validé & Terminé',
            self::Cancelled => 'Annulé',
        };
    }
}
