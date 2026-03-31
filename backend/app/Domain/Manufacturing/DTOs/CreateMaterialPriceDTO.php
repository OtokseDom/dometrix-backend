<?php

namespace App\Domain\Manufacturing\DTOs;

class CreateMaterialPriceDTO
{
    public function __construct(
        public string $organizationId,
        public string $materialId,
        public string $price,
        public string $effectiveDate,
        public ?string $createdBy = null,
    ) {}
}
