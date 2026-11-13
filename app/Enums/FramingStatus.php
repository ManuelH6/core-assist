<?php

namespace App\Enums;

enum FramingStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'Soumis au client',
            self::Accepted => 'Accepté',
            self::Rejected => 'Refusé',
            self::Superseded => 'Remplacé par révision',
        };
    }
}
