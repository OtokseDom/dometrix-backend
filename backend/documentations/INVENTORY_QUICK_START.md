# Inventory Module - Quick Start Guide

## 🚀 5-Minute Setup

### Step 1: Run Migration

```bash
php artisan migrate
```

### Step 2: Seed Sample Data (Optional)

```bash
php artisan db:seed --class=InventorySeeder
```

### Step 3: Resolve Services from Container

```php
use App\Domain\Inventory\Services\InventoryMovementService;
use App\Domain\Inventory\Services\InventoryTransactionService;
use App\Domain\Audit\Services\AuditTrailService;

class MyController
{
    public function __construct(
        private InventoryMovementService $movementService,
        private InventoryTransactionService $transactionService,
        private AuditTrailService $auditService
    ) {}

    public function handleMovement()
    {
        // Use services...
    }
}
```

---

## 💡 Common Tasks

### Record a Stock Receipt

```php
use App\Domain\Inventory\DTOs\CreateInventoryMovementDTO;

$movement = $this->movementService->recordMovement(
    new CreateInventoryMovementDTO(
        organizationId: auth()->user()->organization_id,
        warehouseId: 'warehouse-uuid',
        materialId: 'material-uuid',
        batchId: null,
        referenceType: 'Purchase Order',
        referenceId: 'PO-123',
        movementType: 'PURCHASE_RECEIPT',
        quantity: 1000,
        unitOfMeasureId: 'unit-kg-uuid',
        unitCost: 25.50,
        performedBy: auth()->user()->id,
        remarks: 'Receipt from supplier ABC',
    )
);

echo $movement->id; // Movement created and audited
```

### Record Production Consumption

```php
use App\Domain\Inventory\DTOs\ConsumeFifoBatchesDTO;

$result = $this->transactionService->consumeFifoBatches(
    new ConsumeFifoBatchesDTO(
        organizationId: auth()->user()->organization_id,
        warehouseId: 'warehouse-uuid',
        materialId: 'material-uuid',
        quantityToConsume: 500,
        movementType: 'PRODUCTION_CONSUMPTION',
        referenceType: 'Work Order',
        referenceId: 'WO-456',
        performedBy: auth()->user()->id,
        remarks: 'Consumption for WO-456',
    )
);

echo $result['total_cost']; // COGS calculated using FIFO
echo $result['batches_consumed']; // Which batches were used
```

### Get Current Stock Level

```php
$balance = $this->movementService->getBalance(
    organizationId: 'org-uuid',
    warehouseId: 'wh-uuid',
    materialId: 'mat-uuid'
);

echo "On-hand: {$balance->on_hand_qty}";
echo "Available: {$balance->available_qty}";
echo "Value: {$balance->on_hand_qty * $balance->average_cost}";
```

### Generate Stock Report

```php
use App\Domain\Inventory\Services\InventoryReportingService;

$reportingService = app(InventoryReportingService::class);

$stockReport = $reportingService->getStockLevelReport(
    organizationId: 'org-uuid',
    warehouseId: 'wh-uuid'
);

foreach ($stockReport as $line) {
    echo "{$line['material_name']}: {$line['on_hand_qty']} ({$line['total_value']})";
}
```

### View Audit Trail

```php
$auditLog = $this->auditService->getEntityAuditTrail(
    entityType: 'InventoryMovement',
    entityId: 'movement-uuid'
);

foreach ($auditLog as $log) {
    echo "{$log['created_at']}: {$log['action']} by {$log['user']['name']}";
}
```

---

## 🗂 Project Structure

```
backend/
├── app/Domain/Inventory/
│   ├── Models/
│   │   ├── InventoryMovement.php
│   │   ├── InventoryBatch.php
│   │   ├── InventoryBalance.php
│   │   └── InventoryCostLayer.php
│   ├── Services/
│   │   ├── InventoryMovementService.php
│   │   ├── InventoryBalanceService.php
│   │   ├── InventoryCostLayerService.php
│   │   ├── InventoryTransactionService.php
│   │   └── InventoryReportingService.php
│   ├── DTOs/
│   │   ├── CreateInventoryMovementDTO.php
│   │   ├── AdjustInventoryDTO.php
│   │   ├── TransferInventoryDTO.php
│   │   ├── ConsumeFifoBatchesDTO.php
│   │   └── CreateInventoryBatchDTO.php
│   ├── Enums/
│   │   ├── MovementType.php
│   │   ├── BatchStatus.php
│   │   └── CostingMethod.php
│   └── Providers/
│       └── InventoryServiceProvider.php
├── app/Domain/Audit/
│   ├── Models/
│   │   └── AuditLog.php
│   ├── Services/
│   │   └── AuditTrailService.php
│   ├── DTOs/
│   │   └── CreateAuditLogDTO.php
│   └── Providers/
│       └── AuditServiceProvider.php
├── database/
│   ├── migrations/
│   │   ├── 2026_04_02_000001_create_inventory_movements_table.php
│   │   ├── 2026_04_02_000002_create_inventory_batches_table.php
│   │   ├── 2026_04_02_000003_create_inventory_balances_table.php
│   │   ├── 2026_04_02_000004_create_inventory_cost_layers_table.php
│   │   └── 2026_04_02_000005_create_audit_logs_table.php
│   └── seeders/
│       └── InventorySeeder.php
└── documentations/
    ├── INVENTORY_MODULE.md
    └── INVENTORY_IMPLEMENTATION_SUMMARY.md
```

---

## ⚙️ Configuration

### Automatic Services Registration

Services are auto-registered in `bootstrap/providers.php`:

```php
use App\Domain\Inventory\Providers\InventoryServiceProvider;
use App\Domain\Audit\Providers\AuditServiceProvider;

return [
    AppServiceProvider::class,
    InventoryServiceProvider::class,  // ✓ Added
    AuditServiceProvider::class,       // ✓ Added
];
```

No additional configuration needed!

---

## 📋 Movement Types Reference

| Type                     | Direction | Use Case                    |
| ------------------------ | --------- | --------------------------- |
| `PURCHASE_RECEIPT`       | IN        | Incoming from supplier      |
| `PRODUCTION_CONSUMPTION` | OUT       | Used in production          |
| `PRODUCTION_OUTPUT`      | IN        | Produced item received      |
| `SALES_ISSUE`            | OUT       | Shipped to customer         |
| `ADJUSTMENT_IN`          | IN        | Manual add (count variance) |
| `ADJUSTMENT_OUT`         | OUT       | Manual remove (loss/damage) |
| `TRANSFER_IN`            | IN        | Received from transfer      |
| `TRANSFER_OUT`           | OUT       | Sent in transfer            |
| `RETURN_IN`              | IN        | Returned from customer      |
| `RETURN_OUT`             | OUT       | Returned to supplier        |
| `SCRAP_OUT`              | OUT       | Scrapped/waste              |

---

## 🔍 Useful Queries

### Get All Movements for Material

```php
use App\Domain\Inventory\Models\InventoryMovement;

InventoryMovement::where('organization_id', $orgId)
    ->where('material_id', $materialId)
    ->orderBy('created_at', 'desc')
    ->get();
```

### Get Expired Batches

```php
use App\Domain\Inventory\Models\InventoryBatch;

InventoryBatch::where('organization_id', $orgId)
    ->expired()
    ->get();
```

### Get All Warehouses with Stock

```php
use App\Domain\Inventory\Models\InventoryBalance;

InventoryBalance::where('organization_id', $orgId)
    ->withStock()
    ->with('warehouse', 'material')
    ->get();
```

### Get FIFO Layers for Material

```php
use App\Domain\Inventory\Models\InventoryCostLayer;

InventoryCostLayer::where('organization_id', $orgId)
    ->where('material_id', $materialId)
    ->withRemaining()
    ->fifoOrder()
    ->get();
```

### Get Activity for User

```php
use App\Domain\Audit\Models\AuditLog;

AuditLog::where('organization_id', $orgId)
    ->where('user_id', $userId)
    ->where('module', 'inventory')
    ->orderBy('created_at', 'desc')
    ->limit(100)
    ->get();
```

---

## 🧪 Testing Tips

### Test Data

The seeder creates realistic test data:

```bash
php artisan db:seed --class=InventorySeeder
```

### Manual Movement Creation

```php
// In tinker or route:
$org = \App\Domain\Organization\Models\Organization::first();
$wh = $org->warehouses()->first();
$mat = \App\Domain\Manufacturing\Models\Material::first();
$unit = \App\Domain\Units\Models\Units::first();

$movementService = app(\App\Domain\Inventory\Services\InventoryMovementService::class);
$movement = $movementService->recordMovement(
    new \App\Domain\Inventory\DTOs\CreateInventoryMovementDTO(
        organizationId: $org->id,
        warehouseId: $wh->id,
        materialId: $mat->id,
        batchId: null,
        referenceType: 'Test',
        referenceId: 'TEST-1',
        movementType: 'PURCHASE_RECEIPT',
        quantity: 100,
        unitOfMeasureId: $unit->id,
        unitCost: 50.00,
    )
);
```

---

## 📞 Need Help?

1. **For detailed API docs**: See `INVENTORY_MODULE.md`
2. **For implementation details**: See `INVENTORY_IMPLEMENTATION_SUMMARY.md`
3. **For code examples**: Check method documentation in service classes
4. **For architecture**: Review DbSchema diagrams above

---

## ✅ Checklist Before Going Live

- [ ] Run migrations: `php artisan migrate`
- [ ] Verify tables created in database
- [ ] Run seeder for test data: `php artisan db:seed --class=InventorySeeder`
- [ ] Test basic movement recording
- [ ] Test FIFO consumption
- [ ] Verify audit logs are created
- [ ] Check balance calculations
- [ ] Review warehouse health report
- [ ] Test multi-tenant isolation
- [ ] Load test with large movements
- [ ] Verify transaction rollback on errors
- [ ] Test batch expiry handling
- [ ] Set up alerts for near-expiry batches
- [ ] Create API endpoints (as needed)
- [ ] Add request validation classes (as needed)
- [ ] Deploy to production
