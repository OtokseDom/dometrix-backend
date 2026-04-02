<?php

namespace App\Domain\Inventory\Enums;

enum BatchStatus: string
{
    case ACTIVE = 'ACTIVE';
    case EXPIRED = 'EXPIRED';
    case CLOSED = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::EXPIRED => 'Expired',
            self::CLOSED => 'Closed',
        };
    }
}
