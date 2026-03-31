<?php

namespace App\Domain\Manufacturing\DTOs;

class CalculateMaterialCostDTO
{
    public function __construct(
        public string $organizationId,
        public string $materialId,
        public float $quantity,
        public ?string $effectiveDate = null, // Defaults to today
        public ?string $costingMethod = null, // Defaults from org settings
    ) {}
}
