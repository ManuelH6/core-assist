<?php

namespace App\Enums;

enum WorkType: string
{
    case Investigation = 'investigation';
    case Analysis = 'analysis';
    case Study = 'study';
    case Collection = 'collection';
    case Consolidation = 'consolidation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Investigation => 'Investigation terrain',
            self::Analysis => 'Analyse quantitative/qualitative',
            self::Study => 'Étude documentaire',
            self::Collection => 'Collecte d\'informations',
            self::Consolidation => 'Consolidation intermédiaire',
            self::Other => 'Autre travail',
        };
    }
}
