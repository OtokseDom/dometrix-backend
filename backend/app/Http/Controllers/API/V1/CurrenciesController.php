<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Currencies\Services\CurrenciesService;
use App\Domain\Currencies\DTOs\CreateCurrenciesDTO;
use App\Domain\Currencies\DTOs\UpdateCurrenciesDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreCurrenciesRequest;
use App\Http\Requests\UpdateCurrenciesRequest;
use App\Http\Resources\CurrenciesCollection;
use App\Http\Resources\CurrenciesResource;

class CurrenciesController extends Controller
{
    protected CurrenciesService $service;

    public function __construct(CurrenciesService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return ApiResponse::send(new CurrenciesCollection($this->service->getCurrencies()), "Currencies retrieved");
    }

    public function store(StoreCurrenciesRequest $request)
    {
        $dto = new CreateCurrenciesDTO(
            code: $request->code,
            name: $request->name,
            metadata: $request->metadata ?? null
        );

        $uom = $this->service->create($dto);
        return ApiResponse::send(new CurrenciesResource($uom), "Currencies created", true, 201);
    }

    public function show($id)
    {
        $currency = $this->service->showCurrency($id);
        $this->service->findOrFail($id);
        return ApiResponse::send(new CurrenciesResource($currency), "Currency retrieved");
    }

    public function update(UpdateCurrenciesRequest $request, $id)
    {
        $currency = $this->service->showCurrency($id);
        $this->service->findOrFail($id);

        $dto = new UpdateCurrenciesDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            metadata: $request->metadata ?? null
        );

        $currency = $this->service->update($currency, $dto);
        return ApiResponse::send(new CurrenciesResource($currency), "Currency updated");
    }

    public function destroy($id)
    {
        $currency = $this->service->showCurrency($id);
        $this->service->findOrFail($id);
        $this->service->delete($currency);
        return ApiResponse::send(null, "Currency deleted");
    }
}
