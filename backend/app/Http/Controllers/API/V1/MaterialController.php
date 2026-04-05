<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Manufacturing\Services\MaterialService;
use App\Domain\Manufacturing\DTOs\CreateMaterialDTO;
use App\Domain\Manufacturing\DTOs\UpdateMaterialDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreMaterialRequest;
use App\Http\Requests\UpdateMaterialRequest;
use App\Http\Resources\MaterialCollection;
use App\Http\Resources\MaterialResource;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    protected MaterialService $service;

    public function __construct(MaterialService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return ApiResponse::send(
            new MaterialCollection($this->service->getMaterials()),
            "Materials retrieved"
        );
    }

    public function store(StoreMaterialRequest $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;

        $dto = new CreateMaterialDTO(
            organizationId: $organizationId,
            code: $request->code,
            name: $request->name,
            unitId: $request->unit_id,
            categoryId: $request->category_id,
            metadata: $request->metadata ?? null
        );

        $material = $this->service->create($dto);
        return ApiResponse::send(new MaterialResource($material), "Material created", true, 201);
    }

    public function show(Request $request, $id)
    {
        $material = $this->service->getMaterialById($id);
        return ApiResponse::send(new MaterialResource($material), "Material retrieved");
    }

    public function update(UpdateMaterialRequest $request, $id)
    {
        $material = $this->service->getMaterialById($id);

        $dto = new UpdateMaterialDTO(
            code: $request->code ?? null,
            name: $request->name ?? null,
            categoryId: $request->category_id,
            unitId: $request->unit_id ?? null,
            metadata: $request->metadata ?? null
        );

        $material = $this->service->update($material, $dto);
        return ApiResponse::send(new MaterialResource($material), "Material updated");
    }

    public function destroy(Request $request, $id)
    {
        $material = $this->service->getMaterialById($id);
        $this->service->delete($material);
        return ApiResponse::send(null, "Material deleted");
    }
}
