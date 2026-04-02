<?php

namespace App\Domain\Inventory\DTOs;

readonly class ConsumeFifoBatchesDTO
{
    public function __construct(
        public string $organizationId,
        public string $warehouseId,
        public string $materialId,
        public float $quantityToConsume,
        public string $movementType,
        public string $referenceType,
        public ?string $referenceId = null,
        public ?string $performedBy = null,
        public ?string $remarks = null,
        public ?array $metadata = null,
    ) {}
}
