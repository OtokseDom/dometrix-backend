<?php

namespace App\Domain\Manufacturing\DTOs;

class BomItemCostDTO
{
    public function __construct(
        public string $bomItemId,
        public string $lineNo,
        public string $itemType, // 'material' or 'sub_product'
        public string $itemId,
        public string $itemName,
        public string $itemCode,
        public float $quantity,
        public string $quantityUnit,
        public float $wastagePercent,
        public float $quantityWithWastage,
        public float $unitPrice,
        public float $baseCost,
        public float $wastageAmount,
        public float $totalCost,
        public ?string $subProductCost = null // For recursive BOM items
    ) {}

    public function toArray(): array
    {
        return [
            'bomItemId' => $this->bomItemId,
            'lineNo' => $this->lineNo,
            'itemType' => $this->itemType,
            'itemId' => $this->itemId,
            'itemName' => $this->itemName,
            'itemCode' => $this->itemCode,
            'quantity' => (float) $this->quantity,
            'quantityUnit' => $this->quantityUnit,
            'wastagePercent' => (float) $this->wastagePercent,
            'quantityWithWastage' => (float) $this->quantityWithWastage,
            'unitPrice' => (float) $this->unitPrice,
            'baseCost' => (float) $this->baseCost,
            'wastageAmount' => (float) $this->wastageAmount,
            'totalCost' => (float) $this->totalCost,
            'subProductCost' => $this->subProductCost,
        ];
    }
}
