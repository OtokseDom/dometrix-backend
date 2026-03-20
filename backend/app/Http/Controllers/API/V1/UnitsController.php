<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Units\Services\UnitsService;
use App\Domain\Units\DTOs\CreateUnitsDTO;
use App\Domain\Units\DTOs\UpdateUnitsDTO;
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
        return ApiResponse::send(new UnitsCollection($this->service->listAll()), "Units retrieved");
    }

    public function store(StoreUnitsRequest $request)
    {
        $dto = new CreateUnitsDTO(
            code: $request->code,
            name: $request->name,
            metadata: $request->metadata ?? null
        );

        $uom = $this->service->create($dto);
        return ApiResponse::send(new UnitsResource($uom), "Units created", 201);
    }

    public function show($id)
    {
        $uom = $this->service->findById($id);
        if (!$uom) return ApiResponse::send(null, "Units not found", 404);

        return ApiResponse::send(new UnitsResource($uom), "Units retrieved");
    }

    public function update(UpdateUnitsRequest $request, $id)
    {
        $uom = $this->service->findById($id);
        if (!$uom) return ApiResponse::send(null, "Units not found", 404);

        $dto = new UpdateUnitsDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            metadata: $request->metadata ?? null
        );

        $uom = $this->service->update($uom, $dto);
        return ApiResponse::send(new UnitsResource($uom), "Units updated");
    }

    public function destroy($id)
    {
        $uom = $this->service->findById($id);
        if (!$uom) return ApiResponse::send(null, "Units not found", 404);

        $this->service->delete($uom);
        return ApiResponse::send(null, "Units deleted");
    }
}
