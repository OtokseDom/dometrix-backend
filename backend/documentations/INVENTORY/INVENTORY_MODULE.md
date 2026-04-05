# Inventory Control & Audit Trail Module - Implementation Guide

## Module Overview

This is a **production-grade, multi-tenant inventory management system** with complete audit trail capabilities. It implements a ledger-based architecture for full stock traceability and FIFO-ready financial costing.

## Key Architecture Decisions

### 1. Ledger-Based Design

- **InventoryMovements** table = source of truth
- Every stock change is recorded as an immutable transaction
- Supports complete audit trail and historical analysis
- Running balance snapshot for query performance

### 2. Cost Layer System (FIFO)

- **InventoryCostLayers** maintain incoming inventory batches
- Each inbound movement creates a cost layer
- Outbound movements consume layers in FIFO order
- Supports future expansion to WAC, LIFO, or custom methods

### 3. Batch Traceability

- **InventoryBatches** track lot/serial numbers
- Links to manufacturing/expiry dates
- Automatic expiry tracking and alerts
- Supports product recalls and quality holds

### 4. Balance Snapshots

- **InventoryBalances** cache current stock levels
- Fast lookup without aggregating millions of movements
- Tracks on-hand, reserved, and available quantities
- Updated atomically with each movement

### 5. Comprehensive Auditing

- **AuditLogs** record all critical operations
- Captures before/after state
- Tracks user, IP, timestamp, and business context
- Enables compliance reporting and analytics

---

## Database Schema

### InventoryMovements

Records every stock transaction (inbound, outbound, transfers).

```
- id (UUID PK)
- organization_id → organizations
- warehouse_id → warehouses
- material_id → materials
- batch_id → inventory_batches (nullable)
- reference_type (Order, WO, Adjustment, Transfer)
- reference_id (PO/WO number)
- movement_type (PURCHASE_RECEIPT, PRODUCTION_CONSUMPTION, etc.)
- quantity (decimal)
- unit_of_measure_id → units
- unit_cost (cost per unit at time of movement)
- total_cost (quantity × unit_cost)
- running_balance (snapshot after this movement)
- direction (IN/OUT)
- performed_by → users (nullable)
- remarks
- metadata (JSON - extensible)
- created_at, updated_at, deleted_at
```

### InventoryBatches

Groups inventory into lots for traceability and expiry management.

```
- id (UUID PK)
- organization_id → organizations
- material_id → materials
- warehouse_id → warehouses
- batch_number (lot identifier)
- manufactured_date (when produced)
- received_date (when arrived)
- expiry_date (when expires - nullable)
- received_qty (initial quantity)
- remaining_qty (current available)
- unit_cost (cost per unit)
- status (ACTIVE, EXPIRED, CLOSED)
- metadata (JSON)
- created_at, updated_at, deleted_at
```

### InventoryBalances

Fast lookup snapshot of current inventory.

```
- id (UUID PK)
- organization_id → organizations
- warehouse_id → warehouses
- material_id → materials
- batch_id → inventory_batches (nullable)
- on_hand_qty (physical stock)
- reserved_qty (qty reserved for orders)
- available_qty (on_hand - reserved)
- average_cost (weighted average cost)
- updated_at
```

Unique constraint: (organization_id, warehouse_id, material_id, batch_id)

### InventoryCostLayers

FIFO cost layer queue for COGS calculation.

```
- id (UUID PK)
- organization_id → organizations
- warehouse_id → warehouses
- material_id → materials
- batch_id → inventory_batches (nullable)
- source_movement_id → inventory_movements
- original_qty (qty when created)
- remaining_qty (qty still available)
- unit_cost (cost per unit)
- received_at (timestamp - used for FIFO ordering)
- created_at, updated_at
```

### AuditLogs

Complete audit trail of all business actions.

```
- id (UUID PK)
- organization_id → organizations
- user_id → users (nullable)
- module (inventory, manufacturing, purchase)
- entity_type (Material, Movement, Batch)
- entity_id (UUID of affected entity)
- action (CREATE, UPDATE, DELETE, APPROVE, VOID, etc.)
- old_values (JSON - state before)
- new_values (JSON - state after)
- remarks (business reason)
- ip_address
- user_agent
- created_at
```

---

## Service Architecture

### InventoryMovementService

**Core transaction recording**

- `recordMovement(CreateInventoryMovementDTO)` - Record any stock movement
- `getBalance()` - Get current balance
- `getMovementsByReference()` - Get all movements for a reference
- `getDetailedBalance()` - Get balance with all cost layers

### InventoryBalanceService

**Stock level management**

- `getBalance()` - Retrieve or create balance
- `updateBalance()` - Update snapshot after movement
- `reserve()` - Reserve qty for orders
- `releaseReserve()` - Release reservation
- `getWarehouseBalance()` - All materials in warehouse
- `getOrganizationInventoryValue()` - Total org inventory value

### InventoryCostLayerService

**FIFO costing logic**

- `createLayer()` - Create layer from inbound movement
- `consumeFifo()` - Consume qty in FIFO order
- `calculateCogs()` - Calculate COGS for a material
- `getAvailableLayers()` - Get unconsumed layers
- `cleanupClosedLayers()` - Maintenance (delete old fully-consumed layers)

### InventoryTransactionService

**High-level business operations**

- `adjustInventory()` - Manual stock adjustment
- `transferInventory()` - Move stock between warehouses
- `consumeFifoBatches()` - Consume using batch-aware FIFO
- `receiveBatch()` - Create new batch
- `expireBatch()` - Mark batch expired
- `closeBatch()` - Close batch for consumption

### AuditTrailService

**System-wide audit logging**

- `recordAction()` - Generic audit recording
- `recordStockMovement()` - Specific for movements
- `recordAdjustment()` - Specific for adjustments
- `recordBatchEvent()` - Specific for batch changes
- `getEntityAuditTrail()` - Get history of an entity
- `getOrganizationAuditTrail()` - Org-wide audit report
- `getUserActivityReport()` - User-specific activity

### InventoryReportingService

**Analytics and reporting**

- `getStockLevelReport()` - Current stock levels
- `getMaterialMovementHistory()` - Transaction history by material
- `getCogsAnalysis()` - COGS breakdown by period/material/type
- `getMovingMaterialsAnalysis()` - Fast/slow movers
- `getBatchAgingReport()` - Batch age and expiry risk
- `getInventoryVarianceReport()` - System vs. physical variance
- `getWarehouseHealth()` - Warehouse inventory snapshot

---

## Usage Examples

### 1. Record a Purchase Receipt

```php
use App\Domain\Inventory\DTOs\CreateInventoryMovementDTO;
use App\Domain\Inventory\Services\InventoryMovementService;

$movementService = app(InventoryMovementService::class);

$dto = new CreateInventoryMovementDTO(
    organizationId: auth()->user()->organization_id,
    warehouseId: 'wh-raw-materials-uuid',
    materialId: 'mat-steel-sheet-uuid',
    batchId: 'batch-qc123-uuid', // optional, for batch tracking
    referenceType: 'Purchase Order',
    referenceId: 'PO-2025-001',
    movementType: 'PURCHASE_RECEIPT',
    quantity: 1000,
    unitOfMeasureId: 'unit-kg-uuid',
    unitCost: 25.50,
    performedBy: auth()->user()->id,
    remarks: 'Received steel sheets per PO-2025-001',
    metadata: [
        'invoice_number' => 'INV-2025-001',
        'supplier_id' => 'sup-123',
        'lot_number' => 'LOT-2025-03-15',
    ]
);

$movement = $movementService->recordMovement($dto);
// Movement recorded, balance updated, audit logged automatically
```

### 2. Record Production Consumption

```php
$dto = new CreateInventoryMovementDTO(
    organizationId: auth()->user()->organization_id,
    warehouseId: 'wh-raw-materials-uuid',
    materialId: 'mat-plastic-pellets-uuid',
    batchId: 'batch-exp-2026-05-uuid',
    referenceType: 'Work Order',
    referenceId: 'WO-2025-1042',
    movementType: 'PRODUCTION_CONSUMPTION',
    quantity: 250,
    unitOfMeasureId: 'unit-kg-uuid',
    unitCost: 18.75, // Cost from cost layer
    performedBy: auth()->user()->id,
    remarks: 'Consumed for production of Product X',
);

$movement = $movementService->recordMovement($dto);
// Cost layer is consumed in FIFO order
// COGS is calculated automatically
```

### 3. Inventory Adjustment

```php
use App\Domain\Inventory\DTOs\AdjustInventoryDTO;
use App\Domain\Inventory\Services\InventoryTransactionService;

$transactionService = app(InventoryTransactionService::class);

$adjustment = new AdjustInventoryDTO(
    organizationId: auth()->user()->organization_id,
    warehouseId: 'wh-finished-goods-uuid',
    materialId: 'mat-product-xyz-uuid',
    batchId: null, // optional
    quantity: 50,
    direction: 'OUT',
    unitOfMeasureId: 'unit-pcs-uuid',
    unitCost: null,
    adjustmentReason: 'PHYSICAL_COUNT_VARIANCE',
    performedBy: auth()->user()->id,
    remarks: 'Physical count variance: system 1000, actual 950',
);

$result = $transactionService->adjustInventory($adjustment);
// Result: ['movement_id' => '...', 'quantity_adjusted' => 50, ...]
```

### 4. Transfer Between Warehouses

```php
use App\Domain\Inventory\DTOs\TransferInventoryDTO;

$transfer = new TransferInventoryDTO(
    organizationId: auth()->user()->organization_id,
    fromWarehouseId: 'wh-raw-materials-uuid',
    toWarehouseId: 'wh-production-line-uuid',
    materialId: 'mat-steel-sheet-uuid',
    fromBatchId: 'batch-qc123-uuid',
    toBatchId: 'batch-qc123-uuid', // same batch
    quantity: 100,
    unitOfMeasureId: 'unit-kg-uuid',
    performedBy: auth()->user()->id,
    remarks: 'Transferred to production line for WO-2025-1042',
);

$result = $transactionService->transferInventory($transfer);
// Result: ['out_movement_id' => '...', 'in_movement_id' => '...', ...]
```

### 5. FIFO Batch Consumption

```php
use App\Domain\Inventory\DTOs\ConsumeFifoBatchesDTO;

$consumption = new ConsumeFifoBatchesDTO(
    organizationId: auth()->user()->organization_id,
    warehouseId: 'wh-raw-materials-uuid',
    materialId: 'mat-paint-uuid',
    quantityToConsume: 500,
    movementType: 'PRODUCTION_CONSUMPTION',
    referenceType: 'Work Order',
    referenceId: 'WO-2025-1050',
    performedBy: auth()->user()->id,
    remarks: 'Consumption using FIFO batch selection',
);

$result = $transactionService->consumeFifoBatches($consumption);
// Result: [
//   'total_consumed' => 500,
//   'total_cost' => 12500,
//   'average_cost' => 25.00,
//   'batches_consumed' => [
//     ['batch_id' => '...', 'batch_number' => 'LOT-001', 'quantity' => 300, 'total_cost' => 7500],
//     ['batch_id' => '...', 'batch_number' => 'LOT-002', 'quantity' => 200, 'total_cost' => 5000],
//   ]
// ]
```

### 6. Get Current Stock Balance

```php
$balance = $movementService->getBalance(
    organizationId: 'org-uuid',
    warehouseId: 'wh-uuid',
    materialId: 'mat-uuid',
    batchId: null // null for combined all batches
);

echo "On-hand: " . $balance->on_hand_qty;
echo "Reserved: " . $balance->reserved_qty;
echo "Available: " . $balance->available_qty;
echo "Avg Cost: " . $balance->average_cost;
echo "Total Value: " . ($balance->on_hand_qty * $balance->average_cost);
```

### 7. Get Detailed Balance with Cost Layers

```php
$detailed = $movementService->getDetailedBalance(
    organizationId: 'org-uuid',
    warehouseId: 'wh-uuid',
    materialId: 'mat-uuid'
);

// Returns:
// [
//   'balance' => { ... InventoryBalance model },
//   'cost_layers' => [ ... array of cost layers ],
//   'total_value' => 12500.00,
//   'layers_value' => 12500.00,
// ]
```

### 8. Generate Reports

```php
use App\Domain\Inventory\Services\InventoryReportingService;

$reportingService = app(InventoryReportingService::class);

// Stock level report
$stockReport = $reportingService->getStockLevelReport(
    organizationId: 'org-uuid',
    warehouseId: 'wh-uuid' // optional
);

// COGS analysis
$cogsAnalysis = $reportingService->getCogsAnalysis(
    organizationId: 'org-uuid',
    fromDate: now()->subMonth(),
    toDate: now()
);

// Batch aging
$batchAging = $reportingService->getBatchAgingReport(
    organizationId: 'org-uuid',
    warehouseId: 'wh-uuid'
);

// Warehouse health
$warehouseHealth = $reportingService->getWarehouseHealth('org-uuid');

// Inventory value
$totalValue = $balanceService->getOrganizationInventoryValue('org-uuid');
```

### 9. Audit Trail Queries

```php
use App\Domain\Audit\Services\AuditTrailService;

$auditService = app(AuditTrailService::class);

// Get history of a movement
$history = $auditService->getEntityAuditTrail(
    entityType: 'InventoryMovement',
    entityId: 'movement-uuid',
    organizationId: 'org-uuid'
);

// Get organization audit trail
$logs = $auditService->getOrganizationAuditTrail(
    organizationId: 'org-uuid',
    module: 'inventory',
    fromDate: now()->subWeek(),
    toDate: now()
);

// User activity report
$userActivity = $auditService->getUserActivityReport(
    organizationId: 'org-uuid',
    userId: 'user-uuid', // optional
    days: 30
);

// Statistics
$stats = $auditService->getStatistics(
    organizationId: 'org-uuid',
    fromDate: now()->subMonth()
);
```

### 10. Reserve and Release Inventory

```php
use App\Domain\Inventory\Services\InventoryBalanceService;

$balanceService = app(InventoryBalanceService::class);

// Reserve for an order
$balanceService->reserve(
    organizationId: 'org-uuid',
    warehouseId: 'wh-uuid',
    materialId: 'mat-uuid',
    quantity: 100,
    batchId: null
);

// Get updated balance
$balance = $balanceService->getBalance('org-uuid', 'wh-uuid', 'mat-uuid');
// on_hand_qty: 1000
// reserved_qty: 100 (what we just reserved)
// available_qty: 900 (1000 - 100)

// Later, release the reservation
$balanceService->releaseReserve(
    organizationId: 'org-uuid',
    warehouseId: 'wh-uuid',
    materialId: 'mat-uuid',
    quantity: 100
);
```

---

## Enums

### MovementType

```
PURCHASE_RECEIPT - Incoming from supplier
PRODUCTION_CONSUMPTION - Outgoing to production
PRODUCTION_OUTPUT - Incoming from production
SALES_ISSUE - Outgoing to customer
ADJUSTMENT_IN - Manual add
ADJUSTMENT_OUT - Manual remove
TRANSFER_IN - Transfer from another wh
TRANSFER_OUT - Transfer to another wh
RETURN_IN - Return from customer
RETURN_OUT - Return to supplier
SCRAP_OUT - Scrap/waste
```

Each type has methods:

- `isInbound()` - bool
- `isOutbound()` - bool
- `label()` - friendly name

### BatchStatus

```
ACTIVE - Available for consumption
EXPIRED - Expiry date passed
CLOSED - Manually closed
```

### CostingMethod

```
FIFO - First In, First Out (default)
WEIGHTED_AVERAGE - Weighted average cost (extensible)
```

---

## Multi-Tenant Isolation

All operations are scoped by `organization_id`:

```php
// Automatic scoping in all queries
InventoryBalance::where('organization_id', $orgId)->get();

// Tenant auth middleware should verify organization in requests
$orgId = auth()->user()->organization_id;
```

---

## FIFO Implementation Details

### How FIFO Works

1. **Inbound Movement** → Creates a CostLayer
    - Records qty and unit_cost
    - Timestamp (`received_at`) determines FIFO order

2. **Outbound Movement** → Consumes CostLayers
    - Queries layers with `withRemaining()` scope
    - Orders by `received_at ASC` (oldest first)
    - Consumes from oldest until quantity satisfied

3. **COGS Calculation**
    - Sum of (consumed_qty × unit_cost) for each layer
    - Accurate per-material COGS

### Example FIFO Scenario

```
Layer 1: 100 units @ $10 (received 2025-01-01)
Layer 2: 200 units @ $12 (received 2025-01-15)
Layer 3: 150 units @ $15 (received 2025-02-01)

Consumption: 250 units

FIFO Consumption:
- Layer 1: 100 units @ $10 = $1,000
- Layer 2: 150 units @ $12 = $1,800
- Total COGS: $2,800
- Average Cost: $2,800 / 250 = $11.20

Remaining:
- Layer 1: 0 units (closed)
- Layer 2: 50 units @ $12
- Layer 3: 150 units @ $15
```

---

## Transaction Safety

All critical operations use database transactions:

```php
DB::transaction(function () {
    // Create movement
    // Update balance
    // Create cost layer
    // Record audit log
    // Either all succeed or all rollback
});
```

This ensures:

- No partial updates
- Consistency between ledger and snapshots
- ACID compliance
- Data integrity

---

## Future Extensions

The module is designed for extensibility:

1. **Additional Costing Methods**
    - WAC (Weighted Average Cost)
    - LIFO - Easily add via `InventoryCostLayerService::calculateCogs()`

2. **Advanced Features**
    - Multi-location transfers
    - Quality inspection holds
    - ABC analysis
    - Reorder point calculations
    - Safety stock management
    - Cycle counting
    - Serial number tracking (via metadata)

3. **Integration Points**
    - Manufacturing (work orders consume batches)
    - Purchasing (POs create movements)
    - Sales (orders reserve inventory)
    - Accounting (COGS feeds GL)

4. **Performance Optimizations**
    - Materialized views for large reports
    - Archival of old movements beyond 1 year
    - Indexed aggregations by date/warehouse

---

## Key Business Rules Enforced

1. **No Negative Inventory** (except ADJUSTMENT_OUT/SCRAP_OUT)
2. **Batch Expiry Awareness** - Consumes non-expired first
3. **Multi-Tenant Isolation** - Cross-org data inaccessible
4. **Immutable Ledger** - Movements logged as SoftDeletes, not updated
5. **Cost Layer FIFO** - Oldest costs consumed first
6. **Transaction Atomicity** - All-or-nothing updates

---

## Running Migrations

```bash
php artisan migrate

# This creates:
# - inventory_movements
# - inventory_batches
# - inventory_balances
# - inventory_cost_layers
# - audit_logs
```

---

## Testing Examples

See `database/seeders/InventorySeeder.php` for comprehensive seeding examples.
