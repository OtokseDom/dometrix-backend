<?php

namespace App\Domain\Units\DTOs;

class UpdateUnitsDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public string $type,
        public ?array $metadata = null
    ) {
    }
}
