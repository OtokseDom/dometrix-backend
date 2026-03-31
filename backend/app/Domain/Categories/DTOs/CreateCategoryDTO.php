<?php

namespace App\Domain\Categories\DTOs;

class CreateCategoryDTO
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public string $type = 'other',
        public ?string $parentId = null,
        public ?array $metadata = null,
    ) {}
}
