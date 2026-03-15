<?php

namespace App\Domain\UnitOfMeasure\DTOs;

class CreateUnitOfMeasureDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?array $metadata = null
    ) {}
}
