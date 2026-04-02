<?php

namespace App\Domain\Inventory\DTOs;

readonly class CreateInventoryBatchDTO
{
    public function __construct(
        public string $organizationId,
        public string $materialId,
        public string $warehouseId,
        public string $batchNumber,
        public ?\DateTime $manufacturingDate = null,
        public ?\DateTime $receivedDate = null,
        public ?\DateTime $expiryDate = null,
        public float|int|string $receivedQty = 0,
        public float|int|string $unitCost = 0,
        public ?array $metadata = null,
    ) {}
}
