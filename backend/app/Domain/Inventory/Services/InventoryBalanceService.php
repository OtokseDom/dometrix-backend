<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\InventoryBalance;
use App\Domain\Inventory\Models\InventoryCostLayer;
use Illuminate\Support\Facades\DB;

/**
 * InventoryBalanceService
 * 
 * Manages inventory balance snapshots.
 * Provides fast stock lookups and caching for performance.
 * 
 * Key Responsibilities:
 * - Calculate and cache current stock levels
 * - Track reserved quantities
 * - Compute available inventory
 * - Manage balance updates
 */
class InventoryBalanceService
{
    /**
     * Get or create balance record
     */
    public function getBalance(string $organizationId, string $warehouseId, string $materialId, ?string $batchId = null): ?InventoryBalance
    {
        return InventoryBalance::where('organization_id', $organizationId)
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->where('batch_id', $batchId)
            ->first();
    }

    /**
     * Update or create balance snapshot
     */
    public function updateBalance(
        string $organizationId,
        string $warehouseId,
        string $materialId,
        ?string $batchId = null,
        float $onHandQty = 0,
        float $reservedQty = 0,
        ?float $unitCost = null
    ): InventoryBalance {
        $balance = InventoryBalance::firstOrCreate(
            [
                'organization_id' => $organizationId,
                'warehouse_id' => $warehouseId,
                'material_id' => $materialId,
                'batch_id' => $batchId,
            ],
            [
                'on_hand_qty' => $onHandQty,
                'reserved_qty' => $reservedQty,
                'available_qty' => $onHandQty - $reservedQty,
                'average_cost' => $unitCost,
                'updated_at' => now(),
            ]
        );

        // If it already existed, update it
        if (!$balance->wasRecentlyCreated) {
            $availableQty = $onHandQty - $reservedQty;

            // Calculate average cost if we have incoming movements
            $avgCost = $unitCost;
            if (!$avgCost && $onHandQty > 0) {
                $avgCost = $this->calculateAverageCost($organizationId, $warehouseId, $materialId, $batchId);
            }

            $balance->update([
                'on_hand_qty' => $onHandQty,
                'reserved_qty' => $reservedQty,
                'available_qty' => $availableQty,
                'average_cost' => $avgCost,
                'updated_at' => now(),
            ]);
        }

        return $balance->fresh();
    }

    /**
     * Reserve inventory for an order/production
     */
    public function reserve(string $organizationId, string $warehouseId, string $materialId, float $quantity, ?string $batchId = null): void
    {
        $balance = $this->getBalance($organizationId, $warehouseId, $materialId, $batchId);

        if (!$balance) {
            throw new \Exception("No stock to reserve for material: {$materialId}");
        }

        if ($balance->available_qty < $quantity) {
            throw new \Exception("Insufficient available qty to reserve. Available: {$balance->available_qty}, Required: {$quantity}");
        }

        $balance->update([
            'reserved_qty' => $balance->reserved_qty + $quantity,
            'available_qty' => $balance->available_qty - $quantity,
            'updated_at' => now(),
        ]);
    }

    /**
     * Release reserved inventory
     */
    public function releaseReserve(string $organizationId, string $warehouseId, string $materialId, float $quantity, ?string $batchId = null): void
    {
        $balance = $this->getBalance($organizationId, $warehouseId, $materialId, $batchId);

        if (!$balance) {
            return; // Nothing to release
        }

        $balance->update([
            'reserved_qty' => max(0, $balance->reserved_qty - $quantity),
            'available_qty' => $balance->available_qty + $quantity,
            'updated_at' => now(),
        ]);
    }

    /**
     * Get all balances for a warehouse
     */
    public function getWarehouseBalance(string $organizationId, string $warehouseId, bool $onlyWithStock = true): array
    {
        $query = InventoryBalance::where('organization_id', $organizationId)
            ->where('warehouse_id', $warehouseId);

        if ($onlyWithStock) {
            $query->where('on_hand_qty', '>', 0);
        }

        return $query->with(['material', 'batch'])->get()->toArray();
    }

    /**
     * Calculate FIFO average cost for a material
     */
    private function calculateAverageCost(string $organizationId, string $warehouseId, string $materialId, ?string $batchId): ?float
    {
        $totalValue = DB::table('inventory_cost_layers')
            ->where('organization_id', $organizationId)
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->when($batchId, fn($q) => $q->where('batch_id', $batchId))
            ->where('remaining_qty', '>', 0)
            ->selectRaw('SUM(remaining_qty * unit_cost) as total_value')
            ->first()
            ?->total_value;

        $totalQty = DB::table('inventory_cost_layers')
            ->where('organization_id', $organizationId)
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->when($batchId, fn($q) => $q->where('batch_id', $batchId))
            ->where('remaining_qty', '>', 0)
            ->sum('remaining_qty');

        if ($totalQty <= 0 || !$totalValue) {
            return null;
        }

        return (float) ($totalValue / $totalQty);
    }

    /**
     * Get total stock value for organization
     */
    public function getOrganizationInventoryValue(string $organizationId): float
    {
        return (float) DB::table('inventory_balances')
            ->where('organization_id', $organizationId)
            ->selectRaw('SUM(on_hand_qty * average_cost) as total_value')
            ->first()
            ?->total_value ?? 0;
    }

    /**
     * Get warehouse inventory value
     */
    public function getWarehouseInventoryValue(string $organizationId, string $warehouseId): float
    {
        return (float) DB::table('inventory_balances')
            ->where('organization_id', $organizationId)
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('SUM(on_hand_qty * average_cost) as total_value')
            ->first()
            ?->total_value ?? 0;
    }
}
