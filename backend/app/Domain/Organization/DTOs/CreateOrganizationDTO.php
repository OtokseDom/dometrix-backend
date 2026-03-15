<?php

namespace App\Domain\Organization\DTOs;

class CreateOrganizationDTO
{
    public function __construct(
        public string $name,
        public string $code,
        public string $timezone = 'UTC',
        public string $currency = 'USD',
        public ?array $metadata = null
    ) {}
}
