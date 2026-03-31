<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateMaterialCostRequest;
use App\Http\Requests\CalculateBomCostRequest;
use App\Http\Requests\CalculateProductCostRequest;
use App\Domain\Manufacturing\DTOs\CalculateMaterialCostDTO;
use App\Domain\Manufacturing\DTOs\CalculateBomCostDTO;
use App\Domain\Manufacturing\DTOs\CalculateProductCostDTO;
use App\Domain\Manufacturing\Services\MaterialCostService;
use App\Domain\Manufacturing\Services\BomCostService;
use App\Domain\Manufacturing\Services\ProductCostingService;
use App\Http\Resources\CostCalculationResource;
use App\Helpers\ApiResponse;

class ManufacturingCostController extends Controller
{
    protected MaterialCostService $materialCostService;
    protected BomCostService $bomCostService;
    protected ProductCostingService $productCostingService;

    public function __construct(
        MaterialCostService $materialCostService,
        BomCostService $bomCostService,
        ProductCostingService $productCostingService
    ) {
        $this->materialCostService = $materialCostService;
        $this->bomCostService = $bomCostService;
        $this->productCostingService = $productCostingService;
    }

    /**
     * Calculate material cost
     * POST /api/v1/manufacturing/material-cost
     */
    public function calculateMaterialCost(CalculateMaterialCostRequest $request)
    {
        try {
            $dto = new CalculateMaterialCostDTO(
                organizationId: $request->organization_id,
                materialId: $request->material_id,
                quantity: $request->quantity,
                effectiveDate: $request->effective_date,
                costingMethod: $request->costing_method,
            );

            $result = $this->materialCostService->calculateMaterialCost($dto);

            return ApiResponse::send(
                new CostCalculationResource($result),
                "Material cost calculated successfully"
            );
        } catch (\Exception $e) {
            return ApiResponse::send(
                null,
                $e->getMessage(),
                false,
                400
            );
        }
    }

    /**
     * Get material price history
     * GET /api/v1/manufacturing/materials/{id}/price-history
     */
    public function getMaterialPriceHistory($id)
    {
        try {
            $organizationId = request()->user()?->organizations()->first()?->id;

            if (!$organizationId) {
                return ApiResponse::send(null, "Organization not found", false, 401);
            }

            $fromDate = request()->query('from_date');
            $toDate = request()->query('to_date');

            $priceHistory = $this->materialCostService->getMaterialPriceHistory(
                $organizationId,
                $id,
                $fromDate,
                $toDate
            );

            return ApiResponse::send(
                ['material_id' => $id, 'prices' => $priceHistory],
                "Material price history retrieved"
            );
        } catch (\Exception $e) {
            return ApiResponse::send(null, $e->getMessage(), false, 400);
        }
    }

    /**
     * Calculate BOM cost
     * POST /api/v1/manufacturing/bom-cost
     */
    public function calculateBomCost(CalculateBomCostRequest $request)
    {
        try {
            $dto = new CalculateBomCostDTO(
                organizationId: $request->organization_id,
                bomId: $request->bom_id,
                quantity: $request->quantity ?? 1,
                effectiveDate: $request->effective_date,
                costingMethod: $request->costing_method,
                includeProductCost: $request->include_product_cost ?? false,
            );

            $result = $this->bomCostService->calculateBomCost($dto);

            return ApiResponse::send(
                new CostCalculationResource($result),
                "BOM cost calculated successfully"
            );
        } catch (\Exception $e) {
            return ApiResponse::send(
                null,
                $e->getMessage(),
                false,
                400
            );
        }
    }

    /**
     * Calculate product cost
     * POST /api/v1/manufacturing/product-cost
     */
    public function calculateProductCost(CalculateProductCostRequest $request)
    {
        try {
            $dto = new CalculateProductCostDTO(
                organizationId: $request->organization_id,
                productId: $request->product_id,
                quantity: $request->quantity ?? 1,
                effectiveDate: $request->effective_date,
                costingMethod: $request->costing_method,
                useActiveBom: $request->use_active_bom ?? true,
            );

            $result = $this->productCostingService->calculateProductCost($dto);

            return ApiResponse::send(
                new CostCalculationResource($result),
                "Product cost calculated successfully"
            );
        } catch (\Exception $e) {
            return ApiResponse::send(
                null,
                $e->getMessage(),
                false,
                400
            );
        }
    }

    /**
     * Get product cost summary
     * GET /api/v1/manufacturing/products/{id}/cost-summary
     */
    public function getProductCostSummary($id)
    {
        try {
            $organizationId = request()->user()?->organizations()->first()?->id;

            if (!$organizationId) {
                return ApiResponse::send(null, "Organization not found", false, 401);
            }

            $effectiveDate = request()->query('effective_date');

            $summary = $this->productCostingService->getProductCostSummary(
                $organizationId,
                $id,
                $effectiveDate
            );

            return ApiResponse::send(
                $summary,
                "Product cost summary retrieved"
            );
        } catch (\Exception $e) {
            return ApiResponse::send(null, $e->getMessage(), false, 400);
        }
    }
}
