<?php

namespace App\Domain\Units\Services;

use App\Domain\Units\Models\Units;
use App\Domain\Units\DTOs\CreateUnitsDTO;
use App\Domain\Units\DTOs\UpdateUnitsDTO;
use Illuminate\Support\Str;

class UnitsService
{
    public function create(CreateUnitsDTO $dto): Units
    {
        return Units::create([
            'code' => $dto->code,
            'name' => $dto->name,
            'metadata' => $dto->metadata
        ]);
    }

    public function listAll()
    {
        return Units::all();
    }

    public function findById(string $id): ?Units
    {
        return Units::find($id);
    }

    public function update(Units $uom, UpdateUnitsDTO $dto): Units
    {
        $uom->update([
            'code' => $dto->code ?? $uom->code,
            'name' => $dto->name ?? $uom->name,
            'metadata' => $dto->metadata ?? $uom->metadata
        ]);

        return $uom;
    }

    public function delete(Units $uom): void
    {
        $uom->delete();
    }
}
