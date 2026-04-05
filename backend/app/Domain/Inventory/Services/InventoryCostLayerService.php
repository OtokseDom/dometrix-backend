<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Enums\CostingMethod;
use App\Domain\Inventory\Models\InventoryCostLayer;
use App\Domain\Inventory\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

/**
 * InventoryCostLayerService
 * 
 * Manages FIFO cost layers for COGS calculation.
 * Maintains a queue of cost layers for consumption.
 * 
 * Key Responsibilities:
 * - Create cost layers from inbound movements
 * - Consume layers in FIFO order
 * - Calculate COGS for outbound movements
 * - Support multiple costing methods (FIFO, Weighted Average)
 */
class InventoryCostLayerService
{
    /**
     * Create a cost layer from an inbound movement
     */
    public function createLayer(InventoryMovement $movement, float $unitCost): InventoryCostLayer
    {
        return InventoryCostLayer::create([
            'organization_id' => $movement->organization_id,
            'warehouse_id' => $movement->warehouse_id,
            'material_id' => $movement->material_id,
            'batch_id' => $movement->batch_id,
            'source_movement_id' => $movement->id,
            'original_qty' => (float) $movement->quantity,
            'remaining_qty' => (float) $movement->quantity,
            'unit_cost' => $unitCost,
            'received_at' => $movement->created_at,
        ]);
    }

    /**
     * Consume quantity from cost layers using FIFO
     */
    public function consumeFifo(
        string $warehouseId,
        string $materialId,
        float $quantityToConsume,
        ?string $batchId = null
    ): array {
        $consumedLayers = [];
        $remainingQty = $quantityToConsume;

        // Get available layers in FIFO order
        $layers = $this->getAvailableLayers($warehouseId, $materialId, $batchId);

        foreach ($layers as $layer) {
            if ($remainingQty <= 0) {
                break;
            }

            $layerQtyToConsume = min($remainingQty, (float) $layer->remaining_qty);
            $layerCost = $layerQtyToConsume * (float) $layer->unit_cost;

            // Update layer
            $layer->decrement('remaining_qty', $layerQtyToConsume);

            $consumedLayers[] = [
                'layer_id' => $layer->id,
                'source_movement_id' => $layer->source_movement_id,
                'quantity' => $layerQtyToConsume,
                'unit_cost' => (float) $layer->unit_cost,
                'total_cost' => $layerCost,
            ];

            $remainingQty -= $layerQtyToConsume;
        }

        // Check if we could consume all requested quantity
        if ($remainingQty > 0) {
            throw new \Exception("Insufficient layers available. Remaining unmatched: {$remainingQty}");
        }

        return $consumedLayers;
    }

    /**
     * Calculate COGS for a material using specified method
     */
    public function calculateCogs(
        string $warehouseId,
        string $materialId,
        float $quantityConsumed,
        CostingMethod $method = CostingMethod::FIFO
    ): float {
        return match ($method) {
            CostingMethod::FIFO => $this->calculateFifoCogs($warehouseId, $materialId),
            CostingMethod::WEIGHTED_AVERAGE => $this->calculateWeightedAverageCogs($warehouseId, $materialId),
        };
    }

    /**
     * Get cost layers for a material
     */
    public function getCostLayers(string $warehouseId, string $materialId, ?string $batchId = null): array
    {
        return InventoryCostLayer::where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->when($batchId, fn($q) => $q->where('batch_id', $batchId))
            ->fifoOrder()
            ->get()
            ->toArray();
    }

    /**
     * Get available cost layers (with remaining qty > 0)
     */
    public function getAvailableLayers(string $warehouseId, string $materialId, ?string $batchId = null): array
    {
        return InventoryCostLayer::where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->when($batchId, fn($q) => $q->where('batch_id', $batchId))
            ->withRemaining()
            ->fifoOrder()
            ->get()
            ->all();
    }

    /**
     * Calculate COGS using FIFO method from consumed layers
     */
    private function calculateFifoCogs(string $warehouseId, string $materialId): float
    {
        $layers = InventoryCostLayer::where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->get();

        return (float) $layers->sum(function ($layer) {
            return $layer->getConsumedValue();
        });
    }

    /**
     * Calculate COGS using weighted average cost
     */
    private function calculateWeightedAverageCogs(string $warehouseId, string $materialId): float
    {
        $totalCost = DB::table('inventory_movements')
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->where('direction', 'OUT')
            ->sum('total_cost');

        return (float) $totalCost;
    }

    /**
     * Get total COGS for a warehouse
     */
    public function getWarehouseCogs(string $warehouseId): float
    {
        return (float) DB::table('inventory_cost_layers')
            ->where('warehouse_id', $warehouseId)
            ->selectRaw('SUM(original_qty - remaining_qty) * unit_cost as cogs')
            ->first()
            ?->cogs ?? 0;
    }

    /**
     * Cleanup closed layers (optional maintenance task)
     */
    public function cleanupClosedLayers(int $daysOld = 90): int
    {
        return InventoryCostLayer::where('remaining_qty', 0)
            ->whereDate('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
