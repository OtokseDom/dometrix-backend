<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\DTOs\CreateBomItemDTO;
use App\Domain\Manufacturing\DTOs\UpdateBomItemDTO;
use App\Domain\Manufacturing\Models\BomItem;

class BomItemService
{
    public function getBomItems()
    {
        return BomItem::paginate();
    }

    public function getBomItemById(string $bomItemId): BomItem
    {
        return BomItem::findOrFail($bomItemId);
    }

    public function getBomItemsByBom(string $bomId)
    {
        return BomItem::where('bom_id', $bomId)
            ->orderBy('line_no')
            ->paginate();
    }

    public function create(CreateBomItemDTO $dto): BomItem
    {
        return BomItem::create([
            'bom_id' => $dto->bomId,
            'material_id' => $dto->materialId,
            'sub_product_id' => $dto->subProductId,
            'quantity' => $dto->quantity,
            'unit_id' => $dto->unitId,
            'wastage_percent' => $dto->wastagePercent,
            'line_no' => $dto->lineNo,
            'metadata' => $dto->metadata,
        ]);
    }

    public function update(BomItem $bomItem, UpdateBomItemDTO $dto): BomItem
    {
        $bomItem->update(array_filter([
            'material_id' => $dto->materialId,
            'sub_product_id' => $dto->subProductId,
            'quantity' => $dto->quantity,
            'unit_id' => $dto->unitId,
            'wastage_percent' => $dto->wastagePercent,
            'line_no' => $dto->lineNo,
            'metadata' => $dto->metadata,
        ], fn($value) => $value !== null));

        return $bomItem->fresh();
    }

    public function delete(BomItem $bomItem): bool
    {
        return $bomItem->delete();
    }

    public function findOrFail(string $bomItemId): BomItem
    {
        return BomItem::findOrFail($bomItemId);
    }
}
