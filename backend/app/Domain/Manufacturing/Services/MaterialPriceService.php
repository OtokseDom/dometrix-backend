<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\DTOs\CreateMaterialPriceDTO;
use App\Domain\Manufacturing\DTOs\UpdateMaterialPriceDTO;
use App\Domain\Manufacturing\Models\MaterialPrice;

class MaterialPriceService
{
    public function getMaterialPrices(string $organizationId)
    {
        return MaterialPrice::where('organization_id', $organizationId)->paginate();
    }

    public function getMaterialPriceById(string $organizationId, string $materialPriceId): MaterialPrice
    {
        return MaterialPrice::where('organization_id', $organizationId)
            ->findOrFail($materialPriceId);
    }

    public function getMaterialPricesByMaterial(string $organizationId, string $materialId)
    {
        return MaterialPrice::where('organization_id', $organizationId)
            ->where('material_id', $materialId)
            ->orderByDesc('effective_date')
            ->paginate();
    }

    public function create(CreateMaterialPriceDTO $dto): MaterialPrice
    {
        return MaterialPrice::create([
            'organization_id' => $dto->organizationId,
            'material_id' => $dto->materialId,
            'price' => $dto->price,
            'effective_date' => $dto->effectiveDate,
            'created_by' => $dto->createdBy,
        ]);
    }

    public function update(MaterialPrice $materialPrice, UpdateMaterialPriceDTO $dto): MaterialPrice
    {
        $materialPrice->update(array_filter([
            'price' => $dto->price,
            'effective_date' => $dto->effectiveDate,
        ], fn($value) => $value !== null));

        return $materialPrice->fresh();
    }

    public function delete(MaterialPrice $materialPrice): bool
    {
        return $materialPrice->delete();
    }

    public function findOrFail(string $materialPriceId): MaterialPrice
    {
        return MaterialPrice::findOrFail($materialPriceId);
    }
}
