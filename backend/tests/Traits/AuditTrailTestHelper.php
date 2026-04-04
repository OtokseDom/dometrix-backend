<?php

namespace Tests\Traits;

use App\Domain\Audit\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

trait AuditTrailTestHelper
{
    /**
     * Assert an audit log entry was created
     */
    protected function assertAuditLogCreated(
        string $entityType,
        string $action,
        ?string $organizationId = null
    ): AuditLog {
        if ($organizationId === null) {
            /** @var \App\Domain\User\Models\User $user */
            $user = Auth::user();

            return $user->organizations()->firstOrFail()->id;
        }

        $this->assertDatabaseHas('audit_logs', [
            'organization_id' => $organizationId,
            'entity_type' => $entityType,
            'action' => $action,
        ]);

        return AuditLog::where('organization_id', $organizationId)
            ->where('entity_type', $entityType)
            ->where('action', $action)
            ->latest()
            ->first();
    }

    /**
     * Assert audit log has correct old and new values
     */
    protected function assertAuditLogValues(
        AuditLog $auditLog,
        ?array $expectedOldValues = null,
        ?array $expectedNewValues = null
    ): void {
        if ($expectedOldValues) {
            foreach ($expectedOldValues as $key => $value) {
                $this->assertArrayHasKey($key, $auditLog->old_values ?? []);
                $this->assertEquals($value, $auditLog->old_values[$key]);
            }
        }

        if ($expectedNewValues) {
            foreach ($expectedNewValues as $key => $value) {
                $this->assertArrayHasKey($key, $auditLog->new_values);
                $this->assertEquals($value, $auditLog->new_values[$key]);
            }
        }
    }

    /**
     * Get all audit logs for an entity
     */
    protected function getAuditLogsForEntity(string $entityId): array
    {
        return AuditLog::where('entity_id', $entityId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }
}
