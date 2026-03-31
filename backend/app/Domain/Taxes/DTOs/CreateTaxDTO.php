<?php

namespace App\Domain\Taxes\DTOs;

class CreateTaxDTO
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public string $rate,
        public bool $isActive = true,
        public ?array $metadata = null,
    ) {}
}
