<?php

namespace App\Domain\Manufacturing\DTOs;

class UpdateMaterialPriceDTO
{
    public function __construct(
        public ?string $price = null,
        public ?string $effectiveDate = null,
    ) {}
}
