<?php

namespace App\Domain\Manufacturing\DTOs;

class UpdateMaterialDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $categoryId = null,
        public ?string $unitId = null,
        public ?array $metadata = null,
    ) {}
}
