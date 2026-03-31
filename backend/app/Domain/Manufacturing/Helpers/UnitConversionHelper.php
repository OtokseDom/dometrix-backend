<?php

namespace App\Domain\Manufacturing\Helpers;

use Illuminate\Support\Facades\DB;

class UnitConversionHelper
{
    /**
     * Conversion factors: base_unit => array of conversions
     * kg => g: 1000, kg => mg: 1000000, etc.
     */
    public static function convert(
        float $quantity,
        string $fromUnitCode,
        string $toUnitCode,
        string $organizationId
    ): float {
        if ($fromUnitCode === $toUnitCode) {
            return $quantity;
        }

        // Standard metric conversions
        $conversions = [
            'g' => ['kg' => 0.001, 'g' => 1, 'mg' => 1000],
            'kg' => ['g' => 1000, 'kg' => 1, 'mg' => 1000000],
            'l' => ['ml' => 1000, 'l' => 1],
            'ml' => ['l' => 0.001, 'ml' => 1],
            'pcs' => ['pcs' => 1, 'dozen' => 1 / 12],
            'dozen' => ['pcs' => 12, 'dozen' => 1],
        ];

        if (!isset($conversions[$fromUnitCode][$toUnitCode])) {
            throw new \Exception(
                "Unit conversion not supported: $fromUnitCode to $toUnitCode"
            );
        }

        return $quantity * $conversions[$fromUnitCode][$toUnitCode];
    }

    /**
     * Get the base unit for a unit code (e.g., 'g' and 'kg' both use 'weight' as base)
     */
    public static function getBaseUnit(string $unitCode): string
    {
        $bases = [
            'g' => 'g',
            'kg' => 'g',
            'mg' => 'g',
            'l' => 'ml',
            'ml' => 'ml',
            'pcs' => 'pcs',
            'dozen' => 'pcs',
        ];

        return $bases[$unitCode] ?? $unitCode;
    }
}
