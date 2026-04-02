<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\DTOs\AdjustInventoryDTO;
use App\Domain\Inventory\DTOs\TransferInventoryDTO;
use App\Domain\Inventory\DTOs\ConsumeFifoBatchesDTO;
use App\Domain\Inventory\DTOs\CreateInventoryMovementDTO;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Audit\Services\AuditTrailService;
use Illuminate\Support\Facades\DB;

/**
 * InventoryTransactionService
 * 
 * High-level inventory operations combining multiple services.
 * Provides business-focused transaction methods.
 * 
 * Key Responsibilities:
 * - Adjust inventory with audit trail
 * - Transfer inventory between warehouses
 * - Consume inventory using FIFO
 * - Manage batch operations
 */
class InventoryTransactionService
{
    public function __construct(
        private InventoryMovementService $movementService,
        private InventoryBalanceService $balanceService,
        private InventoryCostLayerService $costLayerService,
        private AuditTrailService $auditService,
    ) {}

    /**
     * Adjust inventory (add or remove without specific reference)
     */
    public function adjustInventory(AdjustInventoryDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            // Record the movement
            $movement = $this->movementService->recordMovement(
                new CreateInventoryMovementDTO(
                    organizationId: $dto->organizationId,
                    warehouseId: $dto->warehouseId,
                    materialId: $dto->materialId,
                    batchId: $dto->batchId,
                    referenceType: 'ADJUSTMENT',
                    referenceId: null,
                    movementType: $dto->direction === 'IN' ? 'ADJUSTMENT_IN' : 'ADJUSTMENT_OUT',
                    quantity: $dto->quantity,
                    unitOfMeasureId: $dto->unitOfMeasureId,
                    unitCost: $dto->unitCost,
                    performedBy: $dto->performedBy,
                    remarks: $dto->remarks ?? "Inventory adjustment: {$dto->adjustmentReason}",
                    metadata: $dto->metadata,
                )
            );

            return [
                'movement_id' => $movement->id,
                'quantity_adjusted' => $dto->quantity,
                'direction' => $dto->direction,
                'timestamp' => $movement->created_at,
            ];
        });
    }

    /**
     * Transfer inventory between warehouses
     */
    public function transferInventory(TransferInventoryDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            // Record TRANSFER_OUT from source warehouse
            $outMovement = $this->movementService->recordMovement(
                new CreateInventoryMovementDTO(
                    organizationId: $dto->organizationId,
                    warehouseId: $dto->fromWarehouseId,
                    materialId: $dto->materialId,
                    batchId: $dto->fromBatchId,
                    referenceType: 'TRANSFER',
                    referenceId: null,
                    movementType: 'TRANSFER_OUT',
                    quantity: $dto->quantity,
                    unitOfMeasureId: $dto->unitOfMeasureId,
                    performedBy: $dto->performedBy,
                    remarks: $dto->remarks ?? "Transfer out to warehouse: {$dto->toWarehouseId}",
                    metadata: $dto->metadata,
                )
            );

            // Consume from source using FIFO if batch-specific
            if ($dto->fromBatchId) {
                $batch = InventoryBatch::findOrFail($dto->fromBatchId);
                $batch->decrement('remaining_qty', $dto->quantity);
            }

            // Update source batch if any
            if ($dto->toBatchId) {
                $toBatch = InventoryBatch::findOrFail($dto->toBatchId);
                $toBatch->increment('remaining_qty', $dto->quantity);
            }

            // Record TRANSFER_IN to destination warehouse
            $inMovement = $this->movementService->recordMovement(
                new CreateInventoryMovementDTO(
                    organizationId: $dto->organizationId,
                    warehouseId: $dto->toWarehouseId,
                    materialId: $dto->materialId,
                    batchId: $dto->toBatchId,
                    referenceType: 'TRANSFER',
                    referenceId: null,
                    movementType: 'TRANSFER_IN',
                    quantity: $dto->quantity,
                    unitOfMeasureId: $dto->unitOfMeasureId,
                    performedBy: $dto->performedBy,
                    remarks: $dto->remarks ?? "Transfer in from warehouse: {$dto->fromWarehouseId}",
                    metadata: $dto->metadata,
                )
            );

            return [
                'out_movement_id' => $outMovement->id,
                'in_movement_id' => $inMovement->id,
                'quantity_transferred' => $dto->quantity,
                'from_warehouse' => $dto->fromWarehouseId,
                'to_warehouse' => $dto->toWarehouseId,
                'timestamp' => now(),
            ];
        });
    }

    /**
     * Consume inventory using FIFO with batch awareness
     */
    public function consumeFifoBatches(ConsumeFifoBatchesDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {
            // Get available batches in FIFO order (oldest first by received_date)
            $batches = InventoryBatch::where('organization_id', $dto->organizationId)
                ->where('warehouse_id', $dto->warehouseId)
                ->where('material_id', $dto->materialId)
                ->active()
                ->withStock()
                ->orderBy('received_date', 'asc')
                ->get();

            if ($batches->isEmpty()) {
                throw new \Exception("No available batches for material: {$dto->materialId}");
            }

            $remainingToConsume = $dto->quantityToConsume;
            $consumedBatches = [];
            $totalCost = 0;

            foreach ($batches as $batch) {
                if ($remainingToConsume <= 0) {
                    break;
                }

                $quantityFromBatch = min($remainingToConsume, (float) $batch->remaining_qty);

                // Record movement for this batch consumption
                $movement = $this->movementService->recordMovement(
                    new CreateInventoryMovementDTO(
                        organizationId: $dto->organizationId,
                        warehouseId: $dto->warehouseId,
                        materialId: $dto->materialId,
                        batchId: $batch->id,
                        referenceType: $dto->referenceType,
                        referenceId: $dto->referenceId,
                        movementType: $dto->movementType,
                        quantity: $quantityFromBatch,
                        unitOfMeasureId: $batch->material->unit_id,
                        unitCost: (float) $batch->unit_cost,
                        performedBy: $dto->performedBy,
                        remarks: $dto->remarks ?? "FIFO consumption from batch: {$batch->batch_number}",
                        metadata: $dto->metadata,
                    )
                );

                // Update batch remaining quantity
                $batch->decrement('remaining_qty', $quantityFromBatch);

                $batchCost = $quantityFromBatch * (float) $batch->unit_cost;
                $totalCost += $batchCost;

                $consumedBatches[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'quantity' => $quantityFromBatch,
                    'unit_cost' => (float) $batch->unit_cost,
                    'total_cost' => $batchCost,
                    'movement_id' => $movement->id,
                ];

                $remainingToConsume -= $quantityFromBatch;
            }

            if ($remainingToConsume > 0) {
                throw new \Exception("Could not consume full quantity. Remaining: {$remainingToConsume}");
            }

            return [
                'total_consumed' => $dto->quantityToConsume,
                'total_cost' => $totalCost,
                'average_cost' => $dto->quantityToConsume > 0 ? $totalCost / $dto->quantityToConsume : 0,
                'batches_consumed' => $consumedBatches,
                'timestamp' => now(),
            ];
        });
    }

    /**
     * Create and receive a new batch
     */
    public function receiveBatch(string $organizationId, string $materialId, string $warehouseId, string $batchNumber, array $data): InventoryBatch
    {
        return InventoryBatch::create([
            'organization_id' => $organizationId,
            'material_id' => $materialId,
            'warehouse_id' => $warehouseId,
            'batch_number' => $batchNumber,
            'manufactured_date' => $data['manufactured_date'] ?? null,
            'received_date' => $data['received_date'] ?? now()->toDateTimeString(),
            'expiry_date' => $data['expiry_date'] ?? null,
            'received_qty' => $data['received_qty'] ?? 0,
            'remaining_qty' => $data['received_qty'] ?? 0,
            'unit_cost' => $data['unit_cost'] ?? 0,
            'status' => 'ACTIVE',
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Mark batch as expired
     */
    public function expireBatch(string $batchId, ?string $userId = null): void
    {
        $batch = InventoryBatch::findOrFail($batchId);
        $batch->update(['status' => 'EXPIRED']);

        $this->auditService->recordBatchEvent(
            batchId: $batchId,
            organizationId: $batch->organization_id,
            action: 'UPDATE',
            details: ['status' => 'EXPIRED'],
            userId: $userId,
            remarks: "Batch marked as expired: {$batch->batch_number}",
        );
    }

    /**
     * Close batch (no longer available for consumption)
     */
    public function closeBatch(string $batchId, ?string $userId = null): void
    {
        $batch = InventoryBatch::findOrFail($batchId);
        $batch->update(['status' => 'CLOSED']);

        $this->auditService->recordBatchEvent(
            batchId: $batchId,
            organizationId: $batch->organization_id,
            action: 'UPDATE',
            details: ['status' => 'CLOSED'],
            userId: $userId,
            remarks: "Batch closed: {$batch->batch_number}",
        );
    }
}
