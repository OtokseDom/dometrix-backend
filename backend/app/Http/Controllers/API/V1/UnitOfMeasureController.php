<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\UnitOfMeasure\Services\UnitOfMeasureService;
use App\Domain\UnitOfMeasure\DTOs\CreateUnitOfMeasureDTO;
use App\Domain\UnitOfMeasure\DTOs\UpdateUnitOfMeasureDTO;
use App\Http\Requests\StoreUnitOfMeasureRequest;
use App\Http\Requests\UpdateUnitOfMeasureRequest;
use App\Http\Resources\UnitOfMeasureResource;
use App\Http\Resources\UnitOfMeasureCollection;
use App\Helpers\ApiResponse;

class UnitOfMeasureController extends Controller
{
    protected UnitOfMeasureService $service;

    public function __construct(UnitOfMeasureService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return ApiResponse::send(new UnitOfMeasureCollection($this->service->listAll()), "Unit of Measures retrieved");
    }

    public function store(StoreUnitOfMeasureRequest $request)
    {
        $dto = new CreateUnitOfMeasureDTO(
            code: $request->code,
            name: $request->name,
            metadata: $request->metadata ?? null
        );

        $uom = $this->service->create($dto);
        return ApiResponse::send(new UnitOfMeasureResource($uom), "Unit of Measure created", 201);
    }

    public function show($id)
    {
        $uom = $this->service->findById($id);
        if (!$uom) return ApiResponse::send(null, "Unit of Measure not found", 404);

        return ApiResponse::send(new UnitOfMeasureResource($uom), "Unit of Measure retrieved");
    }

    public function update(UpdateUnitOfMeasureRequest $request, $id)
    {
        $uom = $this->service->findById($id);
        if (!$uom) return ApiResponse::send(null, "Unit of Measure not found", 404);

        $dto = new UpdateUnitOfMeasureDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            metadata: $request->metadata ?? null
        );

        $uom = $this->service->update($uom, $dto);
        return ApiResponse::send(new UnitOfMeasureResource($uom), "Unit of Measure updated");
    }

    public function destroy($id)
    {
        $uom = $this->service->findById($id);
        if (!$uom) return ApiResponse::send(null, "Unit of Measure not found", 404);

        $this->service->delete($uom);
        return ApiResponse::send(null, "Unit of Measure deleted");
    }
}
