<?php

namespace App\Domain\Warehouses\DTOs;

class CreateWarehouseDTO
{
    public function __construct(
        public string $organizationId,
        public string $code,
        public string $name,
        public string $type = 'general',
        public ?string $location = null,
        public bool $isActive = true,
        public ?string $managerUserId = null,
        public ?array $metadata = null,
    ) {}
}
