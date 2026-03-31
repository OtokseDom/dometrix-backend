<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Manufacturing\Services\MaterialPriceService;
use App\Domain\Manufacturing\DTOs\CreateMaterialPriceDTO;
use App\Domain\Manufacturing\DTOs\UpdateMaterialPriceDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreMaterialPriceRequest;
use App\Http\Requests\UpdateMaterialPriceRequest;
use App\Http\Resources\MaterialPriceCollection;
use App\Http\Resources\MaterialPriceResource;
use Illuminate\Http\Request;

class MaterialPriceController extends Controller
{
    protected MaterialPriceService $service;

    public function __construct(MaterialPriceService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        return ApiResponse::send(
            new MaterialPriceCollection($this->service->getMaterialPrices($organizationId)),
            "Material prices retrieved"
        );
    }

    public function store(StoreMaterialPriceRequest $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;

        $dto = new CreateMaterialPriceDTO(
            organizationId: $organizationId,
            materialId: $request->material_id,
            price: $request->price,
            effectiveDate: $request->effective_date,
            createdBy: $request->user()->id
        );

        $materialPrice = $this->service->create($dto);
        return ApiResponse::send(new MaterialPriceResource($materialPrice), "Material price created", true, 201);
    }

    public function show(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $materialPrice = $this->service->getMaterialPriceById($organizationId, $id);
        return ApiResponse::send(new MaterialPriceResource($materialPrice), "Material price retrieved");
    }

    public function update(UpdateMaterialPriceRequest $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $materialPrice = $this->service->getMaterialPriceById($organizationId, $id);

        $dto = new UpdateMaterialPriceDTO(
            price: $request->price ?? null,
            effectiveDate: $request->effective_date ?? null
        );

        $materialPrice = $this->service->update($materialPrice, $dto);
        return ApiResponse::send(new MaterialPriceResource($materialPrice), "Material price updated");
    }

    public function destroy(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $materialPrice = $this->service->getMaterialPriceById($organizationId, $id);
        $this->service->delete($materialPrice);
        return ApiResponse::send(null, "Material price deleted");
    }
}
