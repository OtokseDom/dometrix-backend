<?php

namespace App\Domain\Audit\DTOs;

readonly class CreateAuditLogDTO
{
    public function __construct(
        public string $organizationId,
        public ?string $userId = null,
        public string $module = 'inventory',
        public string $entityType = 'InventoryMovement',
        public ?string $entityId = null,
        public string $action = 'CREATE',
        public ?array $oldValues = null,
        public ?array $newValues = null,
        public ?string $remarks = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}
