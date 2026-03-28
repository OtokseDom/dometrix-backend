<?php

namespace App\Domain\Units\Services;

use App\Domain\Units\DTOs\CreateUnitsDTO;
use App\Domain\Units\DTOs\UpdateUnitsDTO;
use App\Domain\Units\Models\Units;
use App\Http\Resources\UnitsResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class UnitsService
{
    public function create(CreateUnitsDTO $dto): UnitsResource
    {
        return Units::create([
            'code' => $dto->code,
            'name' => $dto->name,
            'type' => $dto->type,
            'metadata' => $dto->metadata
        ]);
    }

    public function getUnits(): ?Collection
    {
        return Units::all();
    }

    public function update(Units $unit, UpdateUnitsDTO $dto): Units
    {
        $unit->update([
            'code' => $dto->code ?? $unit->code,
            'name' => $dto->name ?? $unit->name,
            'type' => $dto->type ?? $unit->type,
            'metadata' => $dto->metadata ?? $unit->metadata
        ]);

        return $unit;
    }

    public function delete(Units $unit): void
    {
        $unit->delete();
    }

    public function findOrFail(string $id): Units
    {
        $unit = $this->showUnit($id);
        if (!$unit) {
            throw new ModelNotFoundException("User not found");
        }
        return $unit;
    }

    public function showUnit(string $id): ?Units
    {
        return Units::find($id);
    }
}
