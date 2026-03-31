<?php

namespace App\Domain\Manufacturing\DTOs;

class UpdateBomDTO
{
    public function __construct(
        public ?string $version = null,
        public ?bool $isActive = null,
        public ?array $metadata = null,
    ) {}
}
