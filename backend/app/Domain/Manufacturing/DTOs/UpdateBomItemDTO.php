<?php

namespace App\Domain\Manufacturing\DTOs;

class UpdateBomItemDTO
{
    public function __construct(
        public ?string $materialId = null,
        public ?string $subProductId = null,
        public ?string $quantity = null,
        public ?string $unitId = null,
        public ?string $wastagePercent = null,
        public ?int $lineNo = null,
        public ?array $metadata = null,
    ) {}
}
