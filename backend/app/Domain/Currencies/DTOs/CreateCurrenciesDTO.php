<?php

namespace App\Domain\Currencies\DTOs;

class CreateCurrenciesDTO
{
    public function __construct(
        public string $code,
        public string $name,
        public ?array $metadata = null
    ) {
    }
}
