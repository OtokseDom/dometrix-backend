<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Warehouses\Services\WarehouseService;
use App\Domain\Warehouses\DTOs\CreateWarehouseDTO;
use App\Domain\Warehouses\DTOs\UpdateWarehouseDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseCollection;
use App\Http\Resources\WarehouseResource;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    protected WarehouseService $service;

    public function __construct(WarehouseService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return ApiResponse::send(
            new WarehouseCollection($this->service->getWarehouses()),
            "Warehouses retrieved"
        );
    }

    public function store(StoreWarehouseRequest $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;

        $dto = new CreateWarehouseDTO(
            organizationId: $organizationId,
            code: $request->code,
            name: $request->name,
            type: $request->type ?? 'general',
            location: $request->location ?? null,
            isActive: $request->is_active ?? true,
            managerUserId: $request->manager_user_id ?? null,
            metadata: $request->metadata ?? null
        );

        $warehouse = $this->service->create($dto);
        return ApiResponse::send(new WarehouseResource($warehouse), "Warehouse created", true, 201);
    }

    public function show(Request $request, $id)
    {
        $warehouse = $this->service->getWarehouseById($id);
        return ApiResponse::send(new WarehouseResource($warehouse), "Warehouse retrieved");
    }

    public function update(UpdateWarehouseRequest $request, $id)
    {
        $warehouse = $this->service->getWarehouseById($id);

        $dto = new UpdateWarehouseDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            type: $request->type ?? null,
            location: $request->location,
            isActive: $request->is_active,
            managerUserId: $request->manager_user_id,
            metadata: $request->metadata ?? null
        );

        $warehouse = $this->service->update($warehouse, $dto);
        return ApiResponse::send(new WarehouseResource($warehouse), "Warehouse updated");
    }

    public function destroy(Request $request, $id)
    {
        $warehouse = $this->service->getWarehouseById($id);
        $this->service->delete($warehouse);
        return ApiResponse::send(null, "Warehouse deleted");
    }
}
