<?php

namespace App\Domain\Categories\DTOs;

class UpdateCategoryDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $parentId = null,
        public ?array $metadata = null,
    ) {}
}
