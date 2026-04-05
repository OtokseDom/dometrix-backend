<?php

namespace App\Domain\Warehouses\Services;

use App\Domain\Warehouses\DTOs\CreateWarehouseDTO;
use App\Domain\Warehouses\DTOs\UpdateWarehouseDTO;
use App\Domain\Warehouses\Models\Warehouse;

class WarehouseService
{
    public function getWarehouses()
    {
        return Warehouse::paginate();
    }

    public function getWarehouseById(string $warehouseId): Warehouse
    {
        return Warehouse::findOrFail($warehouseId);
    }

    public function create(CreateWarehouseDTO $dto): Warehouse
    {
        return Warehouse::create([
            'code' => $dto->code,
            'name' => $dto->name,
            'type' => $dto->type,
            'location' => $dto->location,
            'is_active' => $dto->isActive,
            'manager_user_id' => $dto->managerUserId,
            'metadata' => $dto->metadata,
        ]);
    }

    public function update(Warehouse $warehouse, UpdateWarehouseDTO $dto): Warehouse
    {
        $warehouse->update(array_filter([
            'code' => $dto->code,
            'name' => $dto->name,
            'type' => $dto->type,
            'location' => $dto->location,
            'is_active' => $dto->isActive,
            'manager_user_id' => $dto->managerUserId,
            'metadata' => $dto->metadata,
        ], fn($value) => $value !== null));

        return $warehouse->fresh();
    }

    public function delete(Warehouse $warehouse): bool
    {
        return $warehouse->delete();
    }

    public function findOrFail(string $warehouseId): Warehouse
    {
        return Warehouse::findOrFail($warehouseId);
    }
}
