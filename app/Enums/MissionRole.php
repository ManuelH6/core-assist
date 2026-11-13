<?php

namespace App\Enums;

enum MissionRole: string
{
    case Manager = 'manager';
    case MissionLead = 'mission_lead';
    case Consultant = 'consultant';
    case Contributor = 'contributor';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'Manager de portefeuille',
            self::MissionLead => 'Chef de mission',
            self::Consultant => 'Consultant',
            self::Contributor => 'Contributeur',
        };
    }
}
