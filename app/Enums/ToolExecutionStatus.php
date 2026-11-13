<?php

namespace App\Enums;

enum ToolExecutionStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Running => 'Calcul en cours',
            self::Completed => 'Exécution terminée',
            self::Failed => 'Échec d\'exécution',
            self::Cancelled => 'Annulée',
        };
    }
}
