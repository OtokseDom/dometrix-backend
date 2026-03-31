<?php

namespace App\Domain\Manufacturing\DTOs;

class CalculateBomCostDTO
{
    public function __construct(
        public string $organizationId,
        public string $bomId,
        public float $quantity = 1,
        public ?string $effectiveDate = null,
        public ?string $costingMethod = null,
        public bool $includeProductCost = false, // If true, also fetch product info
    ) {}
}
