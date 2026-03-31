<?php

namespace App\Domain\Manufacturing\DTOs;

use Illuminate\Support\Collection;

class CostCalculationResultDTO
{
    public function __construct(
        public string $type, // 'material', 'bom', 'product'
        public string $itemId,
        public string $itemName,
        public string $itemCode,
        public string $organizationId,
        public float $baseCost,
        public float $wastageAmount,
        public float $totalCost,
        public float $quantity,
        public string $quantityUnit,
        public float $unitCost,
        public ?Collection $bomItems = null, // For BOM/Product breakdown
        public ?array $metadata = null
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'itemId' => $this->itemId,
            'itemName' => $this->itemName,
            'itemCode' => $this->itemCode,
            'organizationId' => $this->organizationId,
            'baseCost' => (float) $this->baseCost,
            'wastageAmount' => (float) $this->wastageAmount,
            'totalCost' => (float) $this->totalCost,
            'quantity' => (float) $this->quantity,
            'quantityUnit' => $this->quantityUnit,
            'unitCost' => (float) $this->unitCost,
            'bomItems' => $this->bomItems?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}