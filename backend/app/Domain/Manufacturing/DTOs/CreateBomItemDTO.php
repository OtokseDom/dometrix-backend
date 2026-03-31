<?php

namespace App\Domain\Manufacturing\DTOs;

class CreateBomItemDTO
{
    public function __construct(
        public string $organizationId,
        public string $bomId,
        public string $quantity,
        public string $unitId,
        public int $lineNo,
        public ?string $materialId = null,
        public ?string $subProductId = null,
        public string $wastagePercent = '0',
        public ?array $metadata = null,
    ) {}
}
