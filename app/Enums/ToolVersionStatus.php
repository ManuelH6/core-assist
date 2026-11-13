<?php

namespace App\Enums;

enum ToolVersionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Deprecated = 'deprecated';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon de définition',
            self::Published => 'Publié au catalogue',
            self::Deprecated => 'Obsolète',
        };
    }
}
