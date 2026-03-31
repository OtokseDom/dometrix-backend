<?php

namespace App\Domain\Taxes\DTOs;

class UpdateTaxDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $rate = null,
        public ?bool $isActive = null,
        public ?array $metadata = null,
    ) {}
}
