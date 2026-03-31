<?php

namespace App\Domain\Manufacturing\DTOs;

class CalculateProductCostDTO
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public float $quantity = 1,
        public ?string $effectiveDate = null,
        public ?string $costingMethod = null,
        public bool $useActiveBom = true, // If true, use active BOM; if false, show all BOM versions
    ) {}
}
