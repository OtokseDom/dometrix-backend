<?php

namespace App\Domain\Currencies\DTOs;

class UpdateCurrenciesDTO
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?array $metadata = null
    ) {
    }
}
