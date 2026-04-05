<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Taxes\Services\TaxService;
use App\Domain\Taxes\DTOs\CreateTaxDTO;
use App\Domain\Taxes\DTOs\UpdateTaxDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreTaxRequest;
use App\Http\Requests\UpdateTaxRequest;
use App\Http\Resources\TaxCollection;
use App\Http\Resources\TaxResource;
use Illuminate\Http\Request;

class TaxController extends Controller
{
    protected TaxService $service;

    public function __construct(TaxService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return ApiResponse::send(
            new TaxCollection($this->service->getTaxes()),
            "Taxes retrieved"
        );
    }

    public function store(StoreTaxRequest $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;

        $dto = new CreateTaxDTO(
            organizationId: $organizationId,
            code: $request->code,
            name: $request->name,
            rate: $request->rate,
            isActive: $request->is_active ?? true,
            metadata: $request->metadata ?? null
        );

        $tax = $this->service->create($dto);
        return ApiResponse::send(new TaxResource($tax), "Tax created", true, 201);
    }

    public function show(Request $request, $id)
    {
        $tax = $this->service->getTaxById($id);
        return ApiResponse::send(new TaxResource($tax), "Tax retrieved");
    }

    public function update(UpdateTaxRequest $request, $id)
    {
        $tax = $this->service->getTaxById($id);

        $dto = new UpdateTaxDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            rate: $request->rate ?? null,
            isActive: $request->is_active,
            metadata: $request->metadata ?? null
        );

        $tax = $this->service->update($tax, $dto);
        return ApiResponse::send(new TaxResource($tax), "Tax updated");
    }

    public function destroy(Request $request, $id)
    {
        $tax = $this->service->getTaxById($id);
        $this->service->delete($tax);
        return ApiResponse::send(null, "Tax deleted");
    }
}
