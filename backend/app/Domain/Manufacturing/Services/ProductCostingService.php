<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\DTOs\CalculateProductCostDTO;
use App\Domain\Manufacturing\DTOs\CostCalculationResultDTO;
use App\Domain\Manufacturing\Models\Product;

class ProductCostingService
{
    protected BomCostService $bomCostService;

    public function __construct(BomCostService $bomCostService)
    {
        $this->bomCostService = $bomCostService;
    }

    /**
     * Calculate the total manufacturing cost of a product
     * Uses the active BOM if useActiveBom is true, otherwise returns cost for all BOMs
     * 
     * @throws \Exception If product not found or no active BOM available
     */
    public function calculateProductCost(
        CalculateProductCostDTO $dto
    ): CostCalculationResultDTO {
        $product = Product::with('boms')->findOrFail($dto->productId);

        if ($product->organization_id !== $dto->organizationId) {
            throw new \Exception("Product does not belong to this organization.");
        }

        if ($dto->useActiveBom) {
            $activeBom = $product->activeBom();

            if (!$activeBom) {
                throw new \Exception(
                    "Product '{$product->code}' has no active BOM defined."
                );
            }

            // Create DTO for BOM cost calculation
            $bomCostDTO = new \App\Domain\Manufacturing\DTOs\CalculateBomCostDTO(
                organizationId: $dto->organizationId,
                bomId: $activeBom->id,
                quantity: $dto->quantity,
                effectiveDate: $dto->effectiveDate,
                costingMethod: $dto->costingMethod,
                includeProductCost: false
            );

            $bomCostResult = $this->bomCostService->calculateBomCost($bomCostDTO);

            // Return as product cost (same as BOM but with product type)
            return new CostCalculationResultDTO(
                type: 'product',
                itemId: $product->id,
                itemName: $product->name,
                itemCode: $product->code,
                organizationId: $dto->organizationId,
                baseCost: $bomCostResult->baseCost,
                wastageAmount: $bomCostResult->wastageAmount,
                totalCost: $bomCostResult->totalCost,
                quantity: $dto->quantity,
                quantityUnit: $bomCostResult->quantityUnit,
                unitCost: $bomCostResult->unitCost,
                bomItems: $bomCostResult->bomItems,
                metadata: array_merge(
                    $bomCostResult->metadata ?? [],
                    ['productId' => $product->id, 'bomId' => $activeBom->id]
                )
            );
        } else {
            // Return breakdown for all BOMs of the product
            $bomCosts = [];

            foreach ($product->boms as $bom) {
                $bomCostDTO = new \App\Domain\Manufacturing\DTOs\CalculateBomCostDTO(
                    organizationId: $dto->organizationId,
                    bomId: $bom->id,
                    quantity: $dto->quantity,
                    effectiveDate: $dto->effectiveDate,
                    costingMethod: $dto->costingMethod,
                );

                $bomCostResult = $this->bomCostService->calculateBomCost($bomCostDTO);
                $bomCosts[] = $bomCostResult->toArray();
            }

            // Return aggregated product cost (for reference, use first/active BOM)
            $activeBom = $product->activeBom();
            $primaryCost = $activeBom
                ? $this->bomCostService->calculateBomCost(
                    new \App\Domain\Manufacturing\DTOs\CalculateBomCostDTO(
                        organizationId: $dto->organizationId,
                        bomId: $activeBom->id,
                        quantity: $dto->quantity,
                        effectiveDate: $dto->effectiveDate,
                    )
                )
                : null;

            return new CostCalculationResultDTO(
                type: 'product',
                itemId: $product->id,
                itemName: $product->name,
                itemCode: $product->code,
                organizationId: $dto->organizationId,
                baseCost: $primaryCost?->baseCost ?? 0,
                wastageAmount: $primaryCost?->wastageAmount ?? 0,
                totalCost: $primaryCost?->totalCost ?? 0,
                quantity: $dto->quantity,
                quantityUnit: $primaryCost?->quantityUnit ?? 'unknown',
                unitCost: $primaryCost?->unitCost ?? 0,
                bomItems: collect($bomCosts),
                metadata: [
                    'productId' => $product->id,
                    'bomCount' => $product->boms->count(),
                    'allBomCosts' => $bomCosts,
                ]
            );
        }
    }

    /**
     * Get cost breakdown summary for a product
     */
    public function getProductCostSummary(
        string $organizationId,
        string $productId,
        ?string $effectiveDate = null
    ): array {
        $dto = new CalculateProductCostDTO(
            organizationId: $organizationId,
            productId: $productId,
            quantity: 1,
            effectiveDate: $effectiveDate,
            useActiveBom: true
        );

        $costResult = $this->calculateProductCost($dto);

        return [
            'productId' => $costResult->itemId,
            'productName' => $costResult->itemName,
            'productCode' => $costResult->itemCode,
            'unitCost' => $costResult->unitCost,
            'baseCost' => $costResult->baseCost,
            'wastageAmount' => $costResult->wastageAmount,
            'totalCost' => $costResult->totalCost,
            'itemCount' => $costResult->bomItems?->count() ?? 0,
            'metadata' => $costResult->metadata,
        ];
    }
}
