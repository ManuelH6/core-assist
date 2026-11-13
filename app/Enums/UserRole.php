<?php

namespace App\Enums;

enum UserRole: string
{
    case Dg = 'dg';
    case Manager = 'manager';
    case MissionLead = 'mission_lead';
    case Consultant = 'consultant';
    case Expert = 'expert';
    case Client = 'client';

    public function label(): string
    {
        return match ($this) {
            self::Dg => 'Directeur Général',
            self::Manager => 'Manager',
            self::MissionLead => 'Chef de mission',
            self::Consultant => 'Consultant',
            self::Expert => 'Expert',
            self::Client => 'Client',
        };
    }

    public function isClient(): bool
    {
        return $this === self::Client;
    }

    public function isCabinet(): bool
    {
        return $this !== self::Client;
    }

    public function canManageClientsAndRequests(): bool
    {
        return in_array($this, [self::Dg, self::Manager, self::MissionLead], true);
    }

    public function canConfigureMissions(): bool
    {
        return in_array($this, [self::Dg, self::Manager], true);
    }
}
