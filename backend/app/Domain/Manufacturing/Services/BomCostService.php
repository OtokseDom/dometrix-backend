<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\DTOs\CalculateBomCostDTO;
use App\Domain\Manufacturing\DTOs\BomItemCostDTO;
use App\Domain\Manufacturing\DTOs\CostCalculationResultDTO;
use App\Domain\Manufacturing\Helpers\WastageCalculationHelper;
use App\Domain\Manufacturing\Helpers\UnitConversionHelper;
use App\Domain\Manufacturing\Models\Bom;
use App\Domain\Manufacturing\Models\BomItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BomCostService
{
    protected MaterialCostService $materialCostService;
    protected ProductCostingService $productCostingService;

    public function __construct(
        MaterialCostService $materialCostService,
        ProductCostingService $productCostingService
    ) {
        $this->materialCostService = $materialCostService;
        $this->productCostingService = $productCostingService;
    }

    /**
     * Calculate the total cost of a BOM
     * 
     * @throws \Exception If BOM not found or items cannot be costed
     */
    public function calculateBomCost(
        CalculateBomCostDTO $dto
    ): CostCalculationResultDTO {
        $bom = Bom::with('product', 'items')->findOrFail($dto->bomId);

        if ($bom->organization_id !== $dto->organizationId) {
            throw new \Exception("BOM does not belong to this organization.");
        }

        $product = $bom->product;
        $bomItems = $bom->items;

        if ($bomItems->isEmpty()) {
            throw new \Exception("BOM has no items to cost.");
        }

        $bomItemCosts = new Collection();
        $totalBaseCost = 0;
        $totalWastageAmount = 0;

        foreach ($bomItems as $bomItem) {
            $itemCost = $this->calculateBomItemCost(
                $bomItem,
                $dto->organizationId,
                $dto->effectiveDate,
                $dto->quantity
            );

            $bomItemCosts->push($itemCost);
            $totalBaseCost += $itemCost->baseCost;
            $totalWastageAmount += $itemCost->wastageAmount;
        }

        $totalCost = $totalBaseCost + $totalWastageAmount;

        // Get product unit for response
        $productUnit = DB::table('units')->where('id', $product->unit_id)->first();
        $productUnitCode = $productUnit?->code ?? 'unknown';

        return new CostCalculationResultDTO(
            type: 'bom',
            itemId: $bom->id,
            itemName: $product->name,
            itemCode: $product->code,
            organizationId: $dto->organizationId,
            baseCost: $totalBaseCost,
            wastageAmount: $totalWastageAmount,
            totalCost: $totalCost,
            quantity: $dto->quantity,
            quantityUnit: $productUnitCode,
            unitCost: $dto->quantity > 0 ? $totalCost / $dto->quantity : 0,
            bomItems: $bomItemCosts,
            metadata: [
                'bomVersion' => $bom->version,
                'bomIsActive' => $bom->is_active,
                'itemCount' => $bomItems->count(),
                'effectiveDate' => $dto->effectiveDate ?? now()->toDateString(),
            ]
        );
    }

    /**
     * Calculate cost of a single BOM item (internal use)
     */
    protected function calculateBomItemCost(
        BomItem $bomItem,
        string $organizationId,
        ?string $effectiveDate,
        float $bomQuantity = 1
    ): BomItemCostDTO {
        $quantity = (float) $bomItem->quantity * $bomQuantity;
        $wastagePercent = (float) $bomItem->wastage_percent;

        // Get unit information
        $unit = DB::table('units')->where('id', $bomItem->unit_id)->first();
        $unitCode = $unit?->code ?? 'unknown';

        if ($bomItem->material_id) {
            // Handle material item
            return $this->costMaterialBomItem(
                $bomItem,
                $quantity,
                $wastagePercent,
                $unitCode,
                $organizationId,
                $effectiveDate
            );
        } elseif ($bomItem->sub_product_id) {
            // Handle sub-product (recursive BOM)
            return $this->costSubProductBomItem(
                $bomItem,
                $quantity,
                $wastagePercent,
                $unitCode,
                $organizationId,
                $effectiveDate
            );
        } else {
            throw new \Exception(
                "BOM item {$bomItem->id} has neither material_id nor sub_product_id."
            );
        }
    }

    /**
     * Cost a material-based BOM item
     */
    protected function costMaterialBomItem(
        BomItem $bomItem,
        decimal|float $quantity,
        decimal|float $wastagePercent,
        string $unitCode,
        string $organizationId,
        ?string $effectiveDate
    ): BomItemCostDTO {
        $material = $bomItem->material;
        $unitPrice = $this->materialCostService->getCurrentMaterialPrice(
            $organizationId,
            $bomItem->material_id
        );

        $quantityWithWastage = WastageCalculationHelper::addWastage($quantity, $wastagePercent);
        $baseCost = $quantity * $unitPrice;
        $wastageAmount = WastageCalculationHelper::calculateWastageCost(
            $quantity,
            $unitPrice,
            $wastagePercent
        );
        $totalCost = $baseCost + $wastageAmount;

        return new BomItemCostDTO(
            bomItemId: $bomItem->id,
            lineNo: (string) $bomItem->line_no,
            itemType: 'material',
            itemId: $material->id,
            itemName: $material->name,
            itemCode: $material->code,
            quantity: $quantity,
            quantityUnit: $unitCode,
            wastagePercent: $wastagePercent,
            quantityWithWastage: $quantityWithWastage,
            unitPrice: $unitPrice,
            baseCost: $baseCost,
            wastageAmount: $wastageAmount,
            totalCost: $totalCost
        );
    }

    /**
     * Cost a sub-product (recursive BOM) item
     */
    protected function costSubProductBomItem(
        BomItem $bomItem,
        decimal|float $quantity,
        decimal|float $wastagePercent,
        string $unitCode,
        string $organizationId,
        ?string $effectiveDate
    ): BomItemCostDTO {
        $subProduct = $bomItem->subProduct;

        // Get the active BOM for the sub-product
        $subBom = $subProduct->activeBom();

        if (!$subBom) {
            throw new \Exception(
                "Sub-product '{$subProduct->code}' has no active BOM."
            );
        }

        // Recursively calculate the sub-product's BOM cost
        $subBomDTO = new CalculateBomCostDTO(
            organizationId: $organizationId,
            bomId: $subBom->id,
            quantity: 1,
            effectiveDate: $effectiveDate,
        );

        $subCostResult = $this->calculateBomCost($subBomDTO);
        $unitPrice = (float) $subCostResult->unitCost;

        $quantityWithWastage = WastageCalculationHelper::addWastage($quantity, $wastagePercent);
        $baseCost = $quantity * $unitPrice;
        $wastageAmount = WastageCalculationHelper::calculateWastageCost(
            $quantity,
            $unitPrice,
            $wastagePercent
        );
        $totalCost = $baseCost + $wastageAmount;

        return new BomItemCostDTO(
            bomItemId: $bomItem->id,
            lineNo: (string) $bomItem->line_no,
            itemType: 'sub_product',
            itemId: $subProduct->id,
            itemName: $subProduct->name,
            itemCode: $subProduct->code,
            quantity: $quantity,
            quantityUnit: $unitCode,
            wastagePercent: $wastagePercent,
            quantityWithWastage: $quantityWithWastage,
            unitPrice: $unitPrice,
            baseCost: $baseCost,
            wastageAmount: $wastageAmount,
            totalCost: $totalCost,
            subProductCost: json_encode($subCostResult->toArray())
        );
    }
}
