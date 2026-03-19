<?php

namespace App\Domain\Units\DTOs;

class CreateUnitsDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?array $metadata = null
    ) {}
}
