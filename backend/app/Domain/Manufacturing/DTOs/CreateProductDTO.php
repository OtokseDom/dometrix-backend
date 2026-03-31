<?php

namespace App\Domain\Manufacturing\DTOs;

class CreateProductDTO
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public string $unitId,
        public ?string $description = null,
        public ?array $metadata = null,
    ) {}
}
