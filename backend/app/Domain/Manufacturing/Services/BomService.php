<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\DTOs\CreateBomDTO;
use App\Domain\Manufacturing\DTOs\UpdateBomDTO;
use App\Domain\Manufacturing\Models\Bom;

class BomService
{
    public function getBoms()
    {
        return Bom::paginate();
    }

    public function getBomById(string $bomId): Bom
    {
        return Bom::findOrFail($bomId);
    }

    public function getBomsByProduct(string $productId)
    {
        return Bom::where('product_id', $productId)
            ->paginate();
    }

    public function create(CreateBomDTO $dto): Bom
    {
        return Bom::create([
            'product_id' => $dto->productId,
            'version' => $dto->version,
            'is_active' => $dto->isActive,
            'metadata' => $dto->metadata,
        ]);
    }

    public function update(Bom $bom, UpdateBomDTO $dto): Bom
    {
        $bom->update(array_filter([
            'version' => $dto->version,
            'is_active' => $dto->isActive,
            'metadata' => $dto->metadata,
        ], fn($value) => $value !== null));

        return $bom->fresh();
    }

    public function delete(Bom $bom): bool
    {
        return $bom->delete();
    }

    public function findOrFail(string $bomId): Bom
    {
        return Bom::findOrFail($bomId);
    }
}
