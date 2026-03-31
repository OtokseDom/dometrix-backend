<?php

namespace App\Domain\Manufacturing\Helpers;

class WastageCalculationHelper
{
    /**
     * Calculate quantity with wastage applied
     * @param decimal|float $quantity Base quantity
     * @param decimal|float $wastagePercent Wastage percentage (e.g., 2.5 for 2.5%)
     * @return decimal|float Quantity including wastage
     */
    public static function addWastage(
        decimal|float $quantity,
        decimal|float $wastagePercent
    ): decimal|float {
        $wastageMultiplier = 1 + ($wastagePercent / 100);
        return $quantity * $wastageMultiplier;
    }

    /**
     * Calculate wastage amount only
     */
    public static function calculateWastageAmount(
        decimal|float $quantity,
        decimal|float $wastagePercent
    ): decimal|float {
        return ($quantity * $wastagePercent) / 100;
    }

    /**
     * Calculate wastage cost
     */
    public static function calculateWastageCost(
        decimal|float $quantity,
        decimal|float $unitPrice,
        decimal|float $wastagePercent
    ): decimal|float {
        $wastageQty = self::calculateWastageAmount($quantity, $wastagePercent);
        return $wastageQty * $unitPrice;
    }
}
