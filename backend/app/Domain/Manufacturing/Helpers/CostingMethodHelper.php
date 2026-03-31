<?php

namespace App\Domain\Manufacturing\Helpers;

use Illuminate\Support\Facades\DB;

class CostingMethodHelper
{
    /**
     * Get the costing method for an organization
     */
    public static function getOrgCostingMethod(string $organizationId): string
    {
        $settings = DB::table('settings')
            ->where('organization_id', $organizationId)
            ->first();

        return $settings?->costing_method ?? 'weighted_average';
    }

    /**
     * Get the inventory method for an organization
     */
    public static function getOrgInventoryMethod(string $organizationId): string
    {
        $settings = DB::table('settings')
            ->where('organization_id', $organizationId)
            ->first();

        return $settings?->inventory_method ?? 'fifo';
    }

    /**
     * Validate costing method
     */
    public static function isValidCostingMethod(string $method): bool
    {
        return in_array($method, ['weighted_average', 'fifo', 'lifo', 'standard']);
    }

    /**
     * Supported costing methods with descriptions
     */
    public static function getSupportedMethods(): array
    {
        return [
            'weighted_average' => 'Weighted Average Cost',
            'fifo' => 'First In First Out',
            'lifo' => 'Last In First Out',
            'standard' => 'Standard Costing',
        ];
    }
}
