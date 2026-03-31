<?php

namespace App\Domain\Taxes\Services;

use App\Domain\Taxes\DTOs\CreateTaxDTO;
use App\Domain\Taxes\DTOs\UpdateTaxDTO;
use App\Domain\Taxes\Models\Tax;

class TaxService
{
    public function getTaxes(string $organizationId)
    {
        return Tax::where('organization_id', $organizationId)->paginate();
    }

    public function getTaxById(string $organizationId, string $taxId): Tax
    {
        return Tax::where('organization_id', $organizationId)->findOrFail($taxId);
    }

    public function create(CreateTaxDTO $dto): Tax
    {
        return Tax::create([
            'organization_id' => $dto->organizationId,
            'code' => $dto->code,
            'name' => $dto->name,
            'rate' => $dto->rate,
            'is_active' => $dto->isActive,
            'metadata' => $dto->metadata,
        ]);
    }

    public function update(Tax $tax, UpdateTaxDTO $dto): Tax
    {
        $tax->update(array_filter([
            'code' => $dto->code,
            'name' => $dto->name,
            'rate' => $dto->rate,
            'is_active' => $dto->isActive,
            'metadata' => $dto->metadata,
        ], fn($value) => $value !== null));

        return $tax->fresh();
    }

    public function delete(Tax $tax): bool
    {
        return $tax->delete();
    }

    public function findOrFail(string $taxId): Tax
    {
        return Tax::findOrFail($taxId);
    }
}
