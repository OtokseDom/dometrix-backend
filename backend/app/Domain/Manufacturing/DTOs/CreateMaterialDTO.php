<?php

namespace App\Domain\Manufacturing\DTOs;

class CreateMaterialDTO
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public string $unitId,
        public ?string $categoryId = null,
        public ?array $metadata = null,
    ) {}
}
