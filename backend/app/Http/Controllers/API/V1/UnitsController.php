<?php

namespace App\Http\Controllers\API\V1;

use App\Domain\Units\DTOs\CreateUnitsDTO;
use App\Domain\Units\DTOs\UpdateUnitsDTO;
use App\Domain\Units\Services\UnitsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnitsRequest;
use App\Http\Requests\UpdateUnitsRequest;
use App\Http\Resources\UnitsResource;
use App\Http\Resources\UnitsCollection;
use App\Helpers\ApiResponse;

class UnitsController extends Controller
{
    protected UnitsService $service;

    public function __construct(UnitsService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return ApiResponse::send(new UnitsCollection($this->service->getUnits()), "Units retrieved");
    }

    public function store(StoreUnitsRequest $request)
    {
        $dto = new CreateUnitsDTO(
            code: $request->code,
            name: $request->name,
            type: $request->type,
            metadata: $request->metadata ?? null
        );

        $unit = $this->service->create($dto);
        return ApiResponse::send(new UnitsResource($unit), "Units created", true, 201);
    }

    public function show($id)
    {
        $unit = $this->service->showUnit($id);
        $this->service->findOrFail($id);

        return ApiResponse::send(new UnitsResource($unit), "Units retrieved");
    }

    public function update(UpdateUnitsRequest $request, $id)
    {
        $unit = $this->service->showUnit($id);
        $this->service->findOrFail($id);

        $dto = new UpdateUnitsDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            type: $request->type ?? null,
            metadata: $request->metadata ?? null
        );

        $unit = $this->service->update($unit, $dto);
        return ApiResponse::send(new UnitsResource($unit), "Units updated");
    }

    public function destroy($id)
    {
        $unit = $this->service->showUnit($id);
        $this->service->findOrFail($id);

        $this->service->delete($unit);
        return ApiResponse::send(null, "Units deleted");
    }
}
