<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\DTOs\CreateInventoryMovementDTO;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Inventory\Models\InventoryBalance;
use App\Domain\Inventory\Models\InventoryCostLayer;
use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Audit\Services\AuditTrailService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * InventoryMovementService
 * 
 * Manages all stock movements within the system.
 * Ensures transactional integrity and ledger-based tracking.
 * 
 * Key Responsibilities:
 * - Record inbound movements (PO, production, adjustments)
 * - Record outbound movements (sales, consumption, adjustments)
 * - Create cost layers for FIFO processing
 * - Update running balances
 * - Generate audit trails
 */
class InventoryMovementService
{
    public function __construct(
        private InventoryBalanceService $balanceService,
        private InventoryCostLayerService $costLayerService,
        private AuditTrailService $auditService,
    ) {}

    /**
     * Record a stock movement transaction
     * 
     * @throws Throwable
     */
    public function recordMovement(CreateInventoryMovementDTO $dto): InventoryMovement
    {
        return DB::transaction(function () use ($dto) {
            // Validate references exist
            $this->validateReferences($dto);

            // Get current balance before movement
            $currentBalance = $this->balanceService->getBalance(
                $dto->organizationId,
                $dto->warehouseId,
                $dto->materialId,
                $dto->batchId
            );

            $quantity = (float) $dto->quantity;
            $direction = MovementType::from($dto->movementType)->isInbound() ? 'IN' : 'OUT';

            // Calculate new balance
            $quantityAdjustment = $direction === 'IN' ? $quantity : -$quantity;
            $newBalance = ($currentBalance?->on_hand_qty ?? 0) + $quantityAdjustment;

            // Validate non-negative inventory (unless explicitly allowed)
            if ($newBalance < 0 && !$this->isNegativeAllowed($dto->movementType)) {
                throw new \Exception("Insufficient inventory. Available: " . ($currentBalance?->on_hand_qty ?? 0) . " Required: {$quantity}");
            }

            // Create the movement record
            $movement = InventoryMovement::create([
                'organization_id' => $dto->organizationId,
                'warehouse_id' => $dto->warehouseId,
                'material_id' => $dto->materialId,
                'batch_id' => $dto->batchId,
                'reference_type' => $dto->referenceType,
                'reference_id' => $dto->referenceId,
                'movement_type' => $dto->movementType,
                'quantity' => $quantity,
                'unit_of_measure_id' => $dto->unitOfMeasureId,
                'unit_cost' => $dto->unitCost,
                'total_cost' => $quantity * ($dto->unitCost ?? 0),
                'running_balance' => $newBalance,
                'direction' => $direction,
                'performed_by' => $dto->performedBy,
                'remarks' => $dto->remarks,
                'metadata' => $dto->metadata,
            ]);

            // For inbound movements, create cost layers
            if ($direction === 'IN') {
                $this->costLayerService->createLayer($movement, $dto->unitCost ?? 0);

                // Update batch if applicable
                if ($dto->batchId) {
                    $this->updateBatchQuantity($dto->batchId, $quantity);
                }
            }

            // Update balance snapshot
            $this->balanceService->updateBalance(
                $dto->organizationId,
                $dto->warehouseId,
                $dto->materialId,
                $dto->batchId,
                $newBalance,
                0, // reserved_qty - will be managed separately
                $dto->unitCost
            );

            // Record audit trail
            $this->auditService->recordAction(
                organizationId: $dto->organizationId,
                userId: $dto->performedBy,
                module: 'inventory',
                entityType: 'InventoryMovement',
                entityId: $movement->id,
                action: 'CREATE',
                newValues: $movement->toArray(),
                remarks: $dto->remarks ?? "Stock movement recorded: {$dto->movementType}",
            );

            return $movement;
        });
    }

    /**
     * Get current stock balance for a material
     */
    public function getBalance(string $organizationId, string $warehouseId, string $materialId, ?string $batchId = null): ?InventoryBalance
    {
        return $this->balanceService->getBalance($organizationId, $warehouseId, $materialId, $batchId);
    }

    /**
     * Get all movements for a reference
     */
    public function getMovementsByReference(string $referenceType, string $referenceId): array
    {
        return InventoryMovement::reference($referenceType, $referenceId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Get detailed balance including all cost layers
     */
    public function getDetailedBalance(string $organizationId, string $warehouseId, string $materialId): array
    {
        $balance = $this->balanceService->getBalance($organizationId, $warehouseId, $materialId);
        $costLayers = $this->costLayerService->getCostLayers($organizationId, $warehouseId, $materialId);

        return [
            'balance' => $balance,
            'cost_layers' => $costLayers,
            'total_value' => (float) ($balance?->on_hand_qty ?? 0) * (float) ($balance?->average_cost ?? 0),
            'layers_value' => collect($costLayers)->sum(fn($layer) => (float) $layer['remaining_qty'] * (float) $layer['unit_cost']),
        ];
    }

    /**
     * Validate that all foreign key references exist
     */
    private function validateReferences(CreateInventoryMovementDTO $dto): void
    {
        $warehouse = DB::table('warehouses')->where('id', $dto->warehouseId)->first();
        if (!$warehouse) {
            throw new ModelNotFoundException("Warehouse not found: {$dto->warehouseId}");
        }

        $material = DB::table('materials')->where('id', $dto->materialId)->first();
        if (!$material) {
            throw new ModelNotFoundException("Material not found: {$dto->materialId}");
        }

        $unit = DB::table('units')->where('id', $dto->unitOfMeasureId)->first();
        if (!$unit) {
            throw new ModelNotFoundException("Unit not found: {$dto->unitOfMeasureId}");
        }

        if ($dto->batchId) {
            $batch = DB::table('inventory_batches')->where('id', $dto->batchId)->first();
            if (!$batch) {
                throw new ModelNotFoundException("Batch not found: {$dto->batchId}");
            }
        }
    }

    /**
     * Check if negative inventory is allowed for this movement type
     */
    private function isNegativeAllowed(string $movementType): bool
    {
        // Only ADJUSTMENT_OUT and SCRAP_OUT allow potential negatives initially
        // This can be configured per organization
        return in_array($movementType, [
            'ADJUSTMENT_OUT',
            'SCRAP_OUT',
        ]);
    }

    /**
     * Update batch remaining quantity
     */
    private function updateBatchQuantity(string $batchId, float $quantity): void
    {
        $batch = InventoryBatch::findOrFail($batchId);
        $batch->increment('remaining_qty', $quantity);
    }
}
