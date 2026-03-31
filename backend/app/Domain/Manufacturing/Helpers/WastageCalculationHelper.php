<?php

namespace App\Domain\Manufacturing\Helpers;

class WastageCalculationHelper
{
    /**
     * Calculate quantity with wastage applied
     * @param float $quantity Base quantity
     * @param float $wastagePercent Wastage percentage (e.g., 2.5 for 2.5%)
     * @return float Quantity including wastage
     */
    public static function addWastage(
        float $quantity,
        float $wastagePercent
    ): float {
        $wastageMultiplier = 1 + ($wastagePercent / 100);
        return $quantity * $wastageMultiplier;
    }

    /**
     * Calculate wastage amount only
     */
    public static function calculateWastageAmount(
        float $quantity,
        float $wastagePercent
    ): float {
        return ($quantity * $wastagePercent) / 100;
    }

    /**
     * Calculate wastage cost
     */
    public static function calculateWastageCost(
        float $quantity,
        float $unitPrice,
        float $wastagePercent
    ): float {
        $wastageQty = self::calculateWastageAmount($quantity, $wastagePercent);
        return $wastageQty * $unitPrice;
    }
}
