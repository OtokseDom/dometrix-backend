<?php

namespace App\Domain\Organization\DTOs;

class UpdateOrganizationDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $code = null,
        public ?string $timezone = null,
        public ?string $currency = null,
        public ?array $metadata = null
    ) {}
}
