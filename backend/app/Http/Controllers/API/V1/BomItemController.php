<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Manufacturing\Services\BomItemService;
use App\Domain\Manufacturing\DTOs\CreateBomItemDTO;
use App\Domain\Manufacturing\DTOs\UpdateBomItemDTO;
use App\Helpers\ApiResponse;
use App\Http\Requests\StoreBomItemRequest;
use App\Http\Requests\UpdateBomItemRequest;
use App\Http\Resources\BomItemCollection;
use App\Http\Resources\BomItemResource;
use Illuminate\Http\Request;

class BomItemController extends Controller
{
    protected BomItemService $service;

    public function __construct(BomItemService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        return ApiResponse::send(
            new BomItemCollection($this->service->getBomItems($organizationId)),
            "BOM items retrieved"
        );
    }

    public function store(StoreBomItemRequest $request)
    {
        $organizationId = $request->user()->organizations()->first()->id;

        $dto = new CreateBomItemDTO(
            organizationId: $organizationId,
            bomId: $request->bom_id,
            materialId: $request->material_id ?? null,
            subProductId: $request->sub_product_id ?? null,
            quantity: $request->quantity,
            unitId: $request->unit_id,
            lineNo: $request->line_no,
            wastagePercent: $request->wastage_percent ?? '0',
            metadata: $request->metadata ?? null
        );

        $bomItem = $this->service->create($dto);
        return ApiResponse::send(new BomItemResource($bomItem), "BOM item created", true, 201);
    }

    public function show(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $bomItem = $this->service->getBomItemById($organizationId, $id);
        return ApiResponse::send(new BomItemResource($bomItem), "BOM item retrieved");
    }

    public function update(UpdateBomItemRequest $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $bomItem = $this->service->getBomItemById($organizationId, $id);

        $dto = new UpdateBomItemDTO(
            materialId: $request->material_id,
            subProductId: $request->sub_product_id,
            quantity: $request->quantity ?? null,
            unitId: $request->unit_id ?? null,
            wastagePercent: $request->wastage_percent ?? null,
            lineNo: $request->line_no ?? null,
            metadata: $request->metadata ?? null
        );

        $bomItem = $this->service->update($bomItem, $dto);
        return ApiResponse::send(new BomItemResource($bomItem), "BOM item updated");
    }

    public function destroy(Request $request, $id)
    {
        $organizationId = $request->user()->organizations()->first()->id;
        $bomItem = $this->service->getBomItemById($organizationId, $id);
        $this->service->delete($bomItem);
        return ApiResponse::send(null, "BOM item deleted");
    }
}
