<?php

namespace App\Enums;

enum ClientType: string
{
    case Entreprise = 'entreprise';
    case Organisation = 'organisation';
    case Particulier = 'particulier';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Entreprise => 'Entreprise',
            self::Organisation => 'Organisation',
            self::Particulier => 'Particulier',
            self::Autre => 'Autre',
        };
    }
}
