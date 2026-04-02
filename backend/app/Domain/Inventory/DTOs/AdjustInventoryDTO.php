<?php

namespace App\Domain\Inventory\DTOs;

readonly class AdjustInventoryDTO
{
    public function __construct(
        public string $organizationId,
        public string $warehouseId,
        public string $materialId,
        public ?string $batchId,
        public float $quantity,
        public string $direction, // IN or OUT
        public string $unitOfMeasureId,
        public ?float $unitCost = null,
        public string $adjustmentReason = 'MANUAL_ADJUSTMENT',
        public ?string $performedBy = null,
        public ?string $remarks = null,
        public ?array $metadata = null,
    ) {}
}
