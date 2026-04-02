<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Models\InventoryMovement;
use App\Domain\Inventory\Models\InventoryBalance;
use App\Domain\Inventory\Models\InventoryBatch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * InventoryReportingService
 * 
 * Provides analytics and reporting capabilities
 * for inventory management and COGS analysis.
 */
class InventoryReportingService
{
    /**
     * Get stock level report for organization
     */
    public function getStockLevelReport(string $organizationId, ?string $warehouseId = null): array
    {
        $query = InventoryBalance::where('organization_id', $organizationId)
            ->where('on_hand_qty', '>', 0)
            ->with(['warehouse', 'material']);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()
            ->map(function ($balance) {
                return [
                    'material_id' => $balance->material_id,
                    'material_code' => $balance->material->code,
                    'material_name' => $balance->material->name,
                    'warehouse_id' => $balance->warehouse_id,
                    'warehouse_name' => $balance->warehouse->name,
                    'on_hand_qty' => (float) $balance->on_hand_qty,
                    'reserved_qty' => (float) $balance->reserved_qty,
                    'available_qty' => (float) $balance->available_qty,
                    'average_cost' => (float) ($balance->average_cost ?? 0),
                    'total_value' => (float) $balance->on_hand_qty * (float) ($balance->average_cost ?? 0),
                ];
            })
            ->toArray();
    }

    /**
     * Get movement history for a material
     */
    public function getMaterialMovementHistory(
        string $organizationId,
        string $materialId,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null
    ): array {
        $query = InventoryMovement::where('organization_id', $organizationId)
            ->where('material_id', $materialId);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        return $query->orderBy('created_at', 'asc')
            ->with(['warehouse', 'batch', 'performer'])
            ->get()
            ->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'date' => $movement->created_at,
                    'type' => $movement->movement_type,
                    'warehouse' => $movement->warehouse->name,
                    'batch' => $movement->batch?->batch_number,
                    'quantity' => (float) $movement->quantity,
                    'unit_cost' => (float) ($movement->unit_cost ?? 0),
                    'total_cost' => (float) ($movement->total_cost ?? 0),
                    'balance' => (float) $movement->running_balance,
                    'reference' => $movement->reference_type,
                    'remarks' => $movement->remarks,
                ];
            })
            ->toArray();
    }

    /**
     * Get COGS analysis by period
     */
    public function getCogsAnalysis(
        string $organizationId,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null
    ): array {
        $query = InventoryMovement::where('organization_id', $organizationId)
            ->where('direction', 'OUT');

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $movements = $query->with('material')->get();

        $analysis = [
            'total_cogs' => 0,
            'by_material' => [],
            'by_type' => [],
            'by_warehouse' => [],
        ];

        foreach ($movements as $movement) {
            $cost = (float) ($movement->total_cost ?? 0);
            $analysis['total_cogs'] += $cost;

            // By material
            $materialKey = $movement->material->code;
            if (!isset($analysis['by_material'][$materialKey])) {
                $analysis['by_material'][$materialKey] = [
                    'material' => $movement->material->name,
                    'quantity' => 0,
                    'cost' => 0,
                ];
            }
            $analysis['by_material'][$materialKey]['quantity'] += (float) $movement->quantity;
            $analysis['by_material'][$materialKey]['cost'] += $cost;

            // By type
            $type = $movement->movement_type;
            if (!isset($analysis['by_type'][$type])) {
                $analysis['by_type'][$type] = ['quantity' => 0, 'cost' => 0];
            }
            $analysis['by_type'][$type]['quantity'] += (float) $movement->quantity;
            $analysis['by_type'][$type]['cost'] += $cost;

            // By warehouse
            $whKey = $movement->warehouse_id;
            if (!isset($analysis['by_warehouse'][$whKey])) {
                $analysis['by_warehouse'][$whKey] = [
                    'warehouse' => $movement->warehouse->name,
                    'quantity' => 0,
                    'cost' => 0,
                ];
            }
            $analysis['by_warehouse'][$whKey]['quantity'] += (float) $movement->quantity;
            $analysis['by_warehouse'][$whKey]['cost'] += $cost;
        }

        return $analysis;
    }

    /**
     * Get slow/fast moving materials
     */
    public function getMovingMaterialsAnalysis(
        string $organizationId,
        int $daysToAnalyze = 90,
        int $minTurnovers = 12
    ): array {
        $fromDate = now()->subDays($daysToAnalyze);

        $results = DB::table('inventory_movements as im')
            ->join('materials as m', 'im.material_id', '=', 'm.id')
            ->where('im.organization_id', $organizationId)
            ->where('im.direction', 'IN')
            ->whereDate('im.created_at', '>=', $fromDate)
            ->selectRaw('
                im.material_id,
                m.code,
                m.name,
                COUNT(*) as movement_count,
                SUM(im.quantity) as total_quantity,
                AVG(im.quantity) as avg_quantity,
                SUM(im.total_cost) as total_cost
            ')
            ->groupBy('im.material_id', 'm.code', 'm.name')
            ->orderByDesc('movement_count')
            ->get();

        return $results->map(function ($row) use ($minTurnovers) {
            return [
                'material_id' => $row->material_id,
                'code' => $row->code,
                'name' => $row->name,
                'turnovers' => (int) $row->movement_count,
                'velocity' => $row->movement_count >= $minTurnovers ? 'fast' : 'slow',
                'total_quantity' => (float) $row->total_quantity,
                'avg_transaction' => (float) $row->avg_quantity,
                'total_cost' => (float) $row->total_cost,
            ];
        })
            ->toArray();
    }

    /**
     * Get batch aging report
     */
    public function getBatchAgingReport(string $organizationId, ?string $warehouseId = null): array
    {
        $query = InventoryBatch::where('organization_id', $organizationId)
            ->where('remaining_qty', '>', 0)
            ->active()
            ->with('material', 'warehouse');

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->get()
            ->map(function ($batch) {
                $daysOld = $batch->received_date->diffInDays(now());
                $isExpiringSoon = $batch->expiry_date && $batch->expiry_date->diffInDays(now()) <= 30;

                return [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'material' => $batch->material->code . ' - ' . $batch->material->name,
                    'warehouse' => $batch->warehouse->name,
                    'received_date' => $batch->received_date,
                    'expiry_date' => $batch->expiry_date,
                    'days_in_stock' => $daysOld,
                    'remaining_qty' => (float) $batch->remaining_qty,
                    'unit_cost' => (float) $batch->unit_cost,
                    'total_value' => (float) $batch->remaining_qty * (float) $batch->unit_cost,
                    'expiring_soon' => $isExpiringSoon,
                    'status' => $batch->status,
                ];
            })
            ->sortByDesc('days_in_stock')
            ->toArray();
    }

    /**
     * Get inventory variance report (physical vs system)
     */
    public function getInventoryVarianceReport(string $organizationId): array
    {
        $balances = InventoryBalance::where('organization_id', $organizationId)
            ->where('on_hand_qty', '>', 0)
            ->with('material', 'warehouse')
            ->get();

        return $balances->map(function ($balance) use ($organizationId) {
            $movements = InventoryMovement::where('organization_id', $organizationId)
                ->where('warehouse_id', $balance->warehouse_id)
                ->where('material_id', $balance->material_id)
                ->sum('quantity');

            return [
                'material_id' => $balance->material_id,
                'material' => $balance->material->code . ' - ' . $balance->material->name,
                'warehouse' => $balance->warehouse->name,
                'system_qty' => (float) $balance->on_hand_qty,
                'total_movements' => (float) $movements,
                'value' => (float) $balance->on_hand_qty * (float) ($balance->average_cost ?? 0),
            ];
        })
            ->toArray();
    }

    /**
     * Get warehouse health snapshot
     */
    public function getWarehouseHealth(string $organizationId): array
    {
        return DB::table('inventory_balances')
            ->where('organization_id', $organizationId)
            ->join('warehouses', 'inventory_balances.warehouse_id', '=', 'warehouses.id')
            ->selectRaw('
                warehouses.id,
                warehouses.name,
                COUNT(DISTINCT inventory_balances.material_id) as material_count,
                COUNT(DISTINCT inventory_balances.batch_id) as batch_count,
                SUM(inventory_balances.on_hand_qty) as total_qty,
                SUM(inventory_balances.on_hand_qty * inventory_balances.average_cost) as total_value,
                SUM(inventory_balances.reserved_qty) as total_reserved
            ')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->get()
            ->map(function ($row) {
                return [
                    'warehouse_id' => $row->id,
                    'warehouse' => $row->name,
                    'material_count' => (int) $row->material_count,
                    'batch_count' => (int) $row->batch_count,
                    'total_qty' => (float) $row->total_qty,
                    'total_value' => (float) ($row->total_value ?? 0),
                    'reserved_qty' => (float) ($row->total_reserved ?? 0),
                ];
            })
            ->toArray();
    }
}
