<?php

namespace App\Domain\Currencies\Services;

use App\Domain\Currencies\Models\Currencies;
use App\Domain\Currencies\DTOs\CreateCurrenciesDTO;
use App\Domain\Currencies\DTOs\UpdateCurrenciesDTO;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class CurrenciesService
{
    public function create(CreateCurrenciesDTO $dto): Currencies
    {
        return Currencies::create([
            'code' => $dto->code,
            'name' => $dto->name,
            'metadata' => $dto->metadata
        ]);
    }

    public function getCurrencies(): ?Collection
    {
        return Currencies::all();
    }

    public function update(Currencies $currency, UpdateCurrenciesDTO $dto): Currencies
    {
        $currency->update([
            'code' => $dto->code ?? $currency->code,
            'name' => $dto->name ?? $currency->name,
            'metadata' => $dto->metadata ?? $currency->metadata
        ]);

        return $currency;
    }

    public function delete(Currencies $uom): void
    {
        $uom->delete();
    }

    public function findOrFail(string $id): Currencies
    {
        $currency = $this->showCurrency($id);
        if (!$currency) {
            throw new ModelNotFoundException("User not found");
        }
        return $currency;
    }

    public function showCurrency(string $id): ?Currencies
    {
        return Currencies::find($id);
    }
}
