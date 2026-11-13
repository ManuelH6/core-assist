<?php

namespace App\Enums;

enum MissionToolUsageStatus: string
{
    case Configured = 'configured';
    case Ready = 'ready';
    case Active = 'active';
    case Completed = 'completed';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Configured => 'Configuré pour la mission',
            self::Ready => 'Prêt au déploiement',
            self::Active => 'En cours d\'utilisation',
            self::Completed => 'Traitements terminés',
            self::Disabled => 'Désactivé',
        };
    }
}
