<?php

namespace App\Domain\Audit\Services;

use App\Domain\Audit\DTOs\CreateAuditLogDTO;
use App\Domain\Audit\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/**
 * AuditTrailService
 * 
 * Centralized audit logging for all business transactions.
 * Provides compliance and accountability for critical operations.
 * 
 * Key Responsibilities:
 * - Record all critical business actions
 * - Capture before/after state
 * - Track user actions with context
 * - Enable audit reports and compliance queries
 */
class AuditTrailService
{
    /**
     * Record an action in the audit trail
     */
    public function recordAction(
        ?string $userId = null,
        string $module = 'inventory',
        string $entityType = 'InventoryMovement',
        ?string $entityId = null,
        string $action = 'CREATE',
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $remarks = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'module' => $module,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'remarks' => $remarks,
            'ip_address' => $this->getIpAddress(),
            'user_agent' => $this->getUserAgent(),
        ]);
    }

    /**
     * Record a stock movement audit
     */
    public function recordStockMovement(
        string $movementId,
        string $materialId,
        string $warehouseId,
        float $quantity,
        string $movementType,
        ?string $batchId = null,
        ?string $userId = null,
        ?string $remarks = null
    ): AuditLog {
        return $this->recordAction(
            userId: $userId,
            module: 'inventory',
            entityType: 'InventoryMovement',
            entityId: $movementId,
            action: 'CREATE',
            newValues: [
                'material_id' => $materialId,
                'warehouse_id' => $warehouseId,
                'quantity' => $quantity,
                'movement_type' => $movementType,
                'batch_id' => $batchId,
            ],
            remarks: $remarks ?? "Stock movement recorded: {$movementType}",
        );
    }

    /**
     * Record inventory adjustment
     */
    public function recordAdjustment(

        string $materialId,
        string $warehouseId,
        float $quantityBefore,
        float $quantityAfter,
        ?string $userId = null,
        ?string $remarks = null
    ): AuditLog {
        return $this->recordAction(
            userId: $userId,
            module: 'inventory',
            entityType: 'InventoryBalance',
            entityId: "{$warehouseId}:{$materialId}",
            action: 'UPDATE',
            oldValues: ['quantity' => $quantityBefore],
            newValues: ['quantity' => $quantityAfter],
            remarks: $remarks ?? "Inventory adjusted from {$quantityBefore} to {$quantityAfter}",
        );
    }

    /**
     * Record batch lifecycle events
     */
    public function recordBatchEvent(
        string $batchId,
        string $action, // Created, Updated, Expired, Closed
        ?array $details = null,
        ?string $userId = null,
        ?string $remarks = null
    ): AuditLog {
        return $this->recordAction(
            userId: $userId,
            module: 'inventory',
            entityType: 'InventoryBatch',
            entityId: $batchId,
            action: $action,
            newValues: $details,
            remarks: $remarks,
        );
    }

    /**
     * Get audit trail for an entity
     */
    public function getEntityAuditTrail(string $entityType, string $entityId): array
    {
        return AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'desc')
            ->with(['user', 'organization'])
            ->get()
            ->toArray();
    }

    /**
     * Get audit trail in date range
     */
    public function getOrganizationAuditTrail(
        ?string $module = null,
        ?\DateTime $fromDate = null,
        ?\DateTime $toDate = null,
        int $limit = 100
    ): array {
        $query = AuditLog::query();

        if ($module) {
            $query->module($module);
        }

        if ($fromDate || $toDate) {
            $query->dateRange($fromDate, $toDate);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->with(['user', 'organization'])
            ->get()
            ->toArray();
    }

    /**
     * Get summary statistics
     */
    public function getStatistics(?\DateTime $fromDate = null): array
    {
        $query = AuditLog::query();

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        return [
            'total_actions' => $query->count(),
            'by_module' => $query->selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->pluck('count', 'module')
                ->toArray(),
            'by_action' => $query->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
            'by_user' => $query->where('user_id', '!=', null)
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->pluck('count', 'user_id')
                ->toArray(),
        ];
    }

    /**
     * Get user activity report
     */
    public function getUserActivityReport(?string $userId = null, int $days = 30): array
    {
        $query = AuditLog::whereDate('created_at', '>=', now()->subDays($days));

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->selectRaw('user_id, action, COUNT(*) as count, MAX(created_at) as last_action')
            ->groupBy('user_id', 'action')
            ->with(['user'])
            ->get()
            ->toArray();
    }

    /**
     * Get change history for entity
     */
    public function getChangeHistory(string $entityType, string $entityId): array
    {
        $logs = AuditLog::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'asc')
            ->get();

        $history = [];
        $currentState = [];

        foreach ($logs as $log) {
            $history[] = [
                'timestamp' => $log->created_at,
                'action' => $log->action,
                'user_id' => $log->user_id,
                'user_name' => $log->user?->name,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'remarks' => $log->remarks,
            ];

            if ($log->new_values) {
                $currentState = array_merge($currentState, $log->new_values);
            }
        }

        return [
            'history' => $history,
            'current_state' => $currentState,
        ];
    }

    /**
     * Get IP address
     */
    private function getIpAddress(): ?string
    {
        return Request::ip();
    }

    /**
     * Get user agent
     */
    private function getUserAgent(): ?string
    {
        return Request::header('User-Agent');
    }
}
