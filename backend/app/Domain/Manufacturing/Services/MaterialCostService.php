<?php

namespace App\Domain\Manufacturing\Services;

use App\Domain\Manufacturing\DTOs\CalculateMaterialCostDTO;
use App\Domain\Manufacturing\DTOs\CostCalculationResultDTO;
use App\Domain\Manufacturing\Helpers\CostingMethodHelper;
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Manufacturing\Models\MaterialPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MaterialCostService
{
    /**
     * Calculate the cost of a given quantity of material
     * 
     * @throws \Exception If material not found or price not available
     */
    public function calculateMaterialCost(
        CalculateMaterialCostDTO $dto
    ): CostCalculationResultDTO {
        $effectiveDate = $dto->effectiveDate
            ? Carbon::parse($dto->effectiveDate)->toDateString()
            : now()->toDateString();

        $material = Material::findOrFail($dto->materialId);

        if ($material->organization_id !== $dto->organizationId) {
            throw new \Exception("Material does not belong to this organization.");
        }

        // Get the price effective on the requested date
        $price = $material->priceAtDate($effectiveDate);

        if (!$price) {
            throw new \Exception(
                "No material price found for material '{$material->code}' on date {$effectiveDate}."
            );
        }

        $unitPrice = (float) $price->price;
        $baseCost = $dto->quantity * $unitPrice;

        // For materials, the cost is straightforward (no wastage at material level initially)
        $totalCost = $baseCost;
        $wastageAmount = 0;

        // Fetch unit info for response
        $unit = DB::table('units')->where('id', $material->unit_id)->first();
        $unitCode = $unit?->code ?? 'unknown';

        return new CostCalculationResultDTO(
            type: 'material',
            itemId: $material->id,
            itemName: $material->name,
            itemCode: $material->code,
            organizationId: $dto->organizationId,
            baseCost: $baseCost,
            wastageAmount: $wastageAmount,
            totalCost: $totalCost,
            quantity: $dto->quantity,
            quantityUnit: $unitCode,
            unitCost: $unitPrice,
            metadata: ['effectiveDate' => $effectiveDate]
        );
    }

    /**
     * Get current price of a material
     * 
     * @throws \Exception If material not found or price not available
     */
    public function getCurrentMaterialPrice(
        string $organizationId,
        string $materialId
    ): float {
        $material = Material::findOrFail($materialId);

        if ($material->organization_id !== $organizationId) {
            throw new \Exception("Material does not belong to this organization.");
        }

        $price = $material->currentPrice();

        if (!$price) {
            throw new \Exception(
                "No current price available for material '{$material->code}'."
            );
        }

        return (float) $price->price;
    }

    /**
     * Get price history for a material within a date range
     */
    public function getMaterialPriceHistory(
        string $organizationId,
        string $materialId,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $material = Material::findOrFail($materialId);

        if ($material->organization_id !== $organizationId) {
            throw new \Exception("Material does not belong to this organization.");
        }

        $query = $material->prices();

        if ($fromDate) {
            $query->where('effective_date', '>=', $fromDate);
        }

        if ($toDate) {
            $query->where('effective_date', '<=', $toDate);
        }

        return $query->orderByDesc('effective_date')
            ->get()
            ->map(fn($p) => [
                'price' => (float) $p->price,
                'effectiveDate' => $p->effective_date->toDateString(),
                'createdAt' => $p->created_at->toIso8601String(),
            ])
            ->toArray();
    }
}
