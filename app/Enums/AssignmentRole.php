<?php

namespace App\Enums;

enum AssignmentRole: string
{
    case Lead = 'lead';
    case Contributor = 'contributor';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Responsable du travail',
            self::Contributor => 'Contributeur',
        };
    }
}
