<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Manufacturing\Services\BomService;
use App\Domain\Manufacturing\DTOs\CreateBomDTO;
use App\Domain\Manufacturing\DTOs\UpdateBomDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreBomRequest;
use App\Http\Requests\UpdateBomRequest;
use App\Http\Resources\BomCollection;
use App\Http\Resources\BomResource;
use Illuminate\Http\Request;

class BomController extends Controller
{
    protected BomService $service;

    public function __construct(BomService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        return ApiResponse::send(
            new BomCollection($this->service->getBoms($organizationId)),
            "BOMs retrieved"
        );
    }

    public function store(StoreBomRequest $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;

        $dto = new CreateBomDTO(
            organizationId: $organizationId,
            productId: $request->product_id,
            version: $request->version,
            isActive: $request->is_active ?? false,
            metadata: $request->metadata ?? null
        );

        $bom = $this->service->create($dto);
        return ApiResponse::send(new BomResource($bom), "BOM created", true, 201);
    }

    public function show(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $bom = $this->service->getBomById($organizationId, $id);
        return ApiResponse::send(new BomResource($bom), "BOM retrieved");
    }

    public function update(UpdateBomRequest $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $bom = $this->service->getBomById($organizationId, $id);

        $dto = new UpdateBomDTO(
            version: $request->version ?? null,
            isActive: $request->is_active,
            metadata: $request->metadata ?? null
        );

        $bom = $this->service->update($bom, $dto);
        return ApiResponse::send(new BomResource($bom), "BOM updated");
    }

    public function destroy(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $bom = $this->service->getBomById($organizationId, $id);
        $this->service->delete($bom);
        return ApiResponse::send(null, "BOM deleted");
    }
}
