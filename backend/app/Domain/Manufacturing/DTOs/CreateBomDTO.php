<?php

namespace App\Domain\Manufacturing\DTOs;

class CreateBomDTO
{
    public function __construct(
        public string $organizationId,
        public string $productId,
        public string $version,
        public bool $isActive = false,
        public ?array $metadata = null,
    ) {}
}
