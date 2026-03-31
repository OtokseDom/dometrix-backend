<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\DTOs\CreateMaterialDTO;
use App\Domain\Manufacturing\DTOs\UpdateMaterialDTO;
use App\Domain\Manufacturing\Models\Material;

class MaterialService
{
    public function getMaterials(string $organizationId)
    {
        return Material::where('organization_id', $organizationId)->paginate();
    }

    public function getMaterialById(string $organizationId, string $materialId): Material
    {
        return Material::where('organization_id', $organizationId)
            ->findOrFail($materialId);
    }

    public function create(CreateMaterialDTO $dto): Material
    {
        return Material::create([
            'organization_id' => $dto->organizationId,
            'code' => $dto->code,
            'name' => $dto->name,
            'category_id' => $dto->categoryId,
            'unit_id' => $dto->unitId,
            'metadata' => $dto->metadata,
        ]);
    }

    public function update(Material $material, UpdateMaterialDTO $dto): Material
    {
        $material->update(array_filter([
            'code' => $dto->code,
            'name' => $dto->name,
            'category_id' => $dto->categoryId,
            'unit_id' => $dto->unitId,
            'metadata' => $dto->metadata,
        ], fn($value) => $value !== null));

        return $material->fresh();
    }

    public function delete(Material $material): bool
    {
        return $material->delete();
    }

    public function findOrFail(string $materialId): Material
    {
        return Material::findOrFail($materialId);
    }
}
