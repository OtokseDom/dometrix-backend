<?php

namespace App\Domain\Inventory\DTOs;

readonly class CreateInventoryMovementDTO
{
    public function __construct(
        public string $organizationId,
        public string $warehouseId,
        public string $materialId,
        public ?string $batchId,
        public string $referenceType,
        public ?string $referenceId,
        public string $movementType,
        public float|int|string $quantity,
        public string $unitOfMeasureId,
        public float|int|string|null $unitCost = null,
        public ?string $performedBy = null,
        public ?string $remarks = null,
        public ?array $metadata = null,
    ) {}
}
