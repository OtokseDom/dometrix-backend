<?php

namespace App\Domain\UnitOfMeasure\DTOs;

class UpdateUnitOfMeasureDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?array $metadata = null
    ) {}
}
