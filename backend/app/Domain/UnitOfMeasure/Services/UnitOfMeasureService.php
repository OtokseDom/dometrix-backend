<?php

namespace App\Domain\UnitOfMeasure\Services;

use App\Domain\UnitOfMeasure\Models\UnitOfMeasure;
use App\Domain\UnitOfMeasure\DTOs\CreateUnitOfMeasureDTO;
use App\Domain\UnitOfMeasure\DTOs\UpdateUnitOfMeasureDTO;
use Illuminate\Support\Str;

class UnitOfMeasureService
{
    public function create(CreateUnitOfMeasureDTO $dto): UnitOfMeasure
    {
        return UnitOfMeasure::create([
            'id' => Str::uuid(),
            'code' => $dto->code,
            'name' => $dto->name,
            'metadata' => $dto->metadata
        ]);
    }

    public function listAll()
    {
        return UnitOfMeasure::all();
    }

    public function findById(string $id): ?UnitOfMeasure
    {
        return UnitOfMeasure::find($id);
    }

    public function update(UnitOfMeasure $uom, UpdateUnitOfMeasureDTO $dto): UnitOfMeasure
    {
        $uom->update([
            'code' => $dto->code ?? $uom->code,
            'name' => $dto->name ?? $uom->name,
            'metadata' => $dto->metadata ?? $uom->metadata
        ]);

        return $uom;
    }

    public function delete(UnitOfMeasure $uom): void
    {
        $uom->delete();
    }
}
