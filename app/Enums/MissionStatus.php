<?php

namespace App\Enums;

enum MissionStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Suspended = 'suspended';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planifiée',
            self::Active => 'En cours',
            self::Suspended => 'Suspendue',
            self::Closed => 'Clôturée',
        };
    }
}
