<?php

namespace App\Domain\Manufacturing\DTOs;

class UpdateProductDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $unitId = null,
        public ?array $metadata = null,
    ) {}
}
