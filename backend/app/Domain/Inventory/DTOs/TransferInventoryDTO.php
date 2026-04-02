<?php

namespace App\Domain\Inventory\DTOs;

readonly class TransferInventoryDTO
{
    public function __construct(
        public string $organizationId,
        public string $fromWarehouseId,
        public string $toWarehouseId,
        public string $materialId,
        public ?string $fromBatchId,
        public ?string $toBatchId,
        public float $quantity,
        public string $unitOfMeasureId,
        public ?string $performedBy = null,
        public ?string $remarks = null,
        public ?array $metadata = null,
    ) {}
}
