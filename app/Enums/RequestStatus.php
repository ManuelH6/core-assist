<?php

namespace App\Enums;

enum RequestStatus: string
{
    case Received = 'received';
    case BeingStudied = 'being_studied';
    case Framed = 'framed';
    case Converted = 'converted';
    case Rejected = 'rejected';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Received => 'Reçue',
            self::BeingStudied => 'À l\'étude',
            self::Framed => 'Cadrée',
            self::Converted => 'Convertie en mission',
            self::Rejected => 'Rejetée',
            self::Archived => 'Archivée',
        };
    }
}
