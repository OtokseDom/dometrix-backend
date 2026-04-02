<?php

namespace App\Domain\Inventory\Enums;

enum CostingMethod: string
{
    case FIFO = 'FIFO';
    case WEIGHTED_AVERAGE = 'WEIGHTED_AVERAGE';

    public function label(): string
    {
        return match ($this) {
            self::FIFO => 'First-In, First-Out',
            self::WEIGHTED_AVERAGE => 'Weighted Average',
        };
    }
}
