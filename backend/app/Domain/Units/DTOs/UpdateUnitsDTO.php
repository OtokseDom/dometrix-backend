<?php

namespace App\Domain\Units\DTOs;

class UpdateUnitsDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?array $metadata = null
    ) {}
}
