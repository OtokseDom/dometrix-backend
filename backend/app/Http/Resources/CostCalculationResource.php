<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CostCalculationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'type' => $this->type,
            'itemId' => $this->itemId,
            'itemName' => $this->itemName,
            'itemCode' => $this->itemCode,
            'organizationId' => $this->organizationId,
            'quantity' => (float) $this->quantity,
            'quantityUnit' => $this->quantityUnit,
            'costs' => [
                'baseCost' => (float) $this->baseCost,
                'wastageAmount' => (float) $this->wastageAmount,
                'totalCost' => (float) $this->totalCost,
            ],
            'unitCost' => (float) $this->unitCost,
            'bomItems' => $this->formatBomItems($this->bomItems),
            'metadata' => $this->metadata,
        ];
    }

    private function formatBomItems($bomItems)
    {
        if (!$bomItems) {
            return null;
        }

        return $bomItems->map(function ($item) {
            $data = [
                'lineNo' => $item->lineNo,
                'bomItemId' => $item->bomItemId,
                'itemType' => $item->itemType,
                'itemId' => $item->itemId,
                'itemName' => $item->itemName,
                'itemCode' => $item->itemCode,
                'quantity' => (float) $item->quantity,
                'quantityUnit' => $item->quantityUnit,
                'wastagePercent' => (float) $item->wastagePercent,
                'quantityWithWastage' => (float) $item->quantityWithWastage,
                'unitPrice' => (float) $item->unitPrice,
                'costs' => [
                    'baseCost' => (float) $item->baseCost,
                    'wastageAmount' => (float) $item->wastageAmount,
                    'totalCost' => (float) $item->totalCost,
                ],
            ];

            if ($item->itemType === 'sub_product' && $item->subProductCost) {
                $data['subProductCost'] = json_decode($item->subProductCost, true);
            }

            return $data;
        })->toArray();
    }
}
