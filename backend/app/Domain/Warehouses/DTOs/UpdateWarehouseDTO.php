<?php

namespace App\Domain\Warehouses\DTOs;

class UpdateWarehouseDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $type = null,
        public ?string $location = null,
        public ?bool $isActive = null,
        public ?string $managerUserId = null,
        public ?array $metadata = null,
    ) {}
}
