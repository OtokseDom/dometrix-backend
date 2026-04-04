# Backend Developer Guide - How It Actually Works

> Personal study notes - not formal documentation. Read this to understand your backend without the jargon.

---

## 1. HIGH LEVEL FLOW

Every request follows this simple path:

```
request
  → Controller (validates & grabs organization from user)
  → Service Layer (handles business logic)
  → Models (interact with database)
  → Resources (format response)
  → return JSON
```

**Example: Create a Product**

```
POST /api/v1/manufacturing/products
  → ProductController
    → extracts organizationId from user
    → creates CreateProductDTO (data object)
  → ProductService
    → saves to database
    → returns Product model
  → ProductResource (formats for API)
  → responds with JSON
```

**Where multi-tenancy is enforced:**

- User has organizations via relationship
- We grab the first org and filter everything by that org_id
- This keeps data separated - one user can't see another org's data

---

## 2. COMPLEX LOGIC EXPLAINED SIMPLY

### **A. THE BOM (Bill of Materials) - Like a Recipe**

**Think of it this way:**

- A BOM is just a recipe card
- When you make 1 Product, you need X materials
- The BOM lists everything required, including wastage

**What happens:**

```
Product (e.g., "Widget")
└── Active BOM (version 1)
    ├── BOM Item 1: 2 units of Material A (with 5% wastage)
    ├── BOM Item 2: 1 unit of Material B (with 2% wastage)
    └── BOM Item 3: Sub-Product (which has its own BOM - recursive!)
```

**Key insight: BOM can have sub-products**

- You can have nested BOMs
- Material A could be made from other materials
- When costing, it recursively calculates down to raw materials

**Wastage is baked in:**

- When calculating cost for 100 units with 5% wastage
- You actually need 105 units of material
- The extra 5 units cost money but don't end up in the final product

```php
// In BomCostService
$quantityWithWastage = 100 * (1 + 5/100) = 105 units needed
$baseCost = 100 * $unitPrice  // only 100 charged to product
$wastageAmount = 5 * $unitPrice  // added to total cost
$totalCost = $baseCost + $wastageAmount
```

### **B. INVENTORY SYSTEM - Think of It as a Ledger**

**The core concept:**

- Every time stock moves, we write it down (like a bank ledger)
- We never delete - we add new lines
- This creates an audit trail automatically

**Three models work together:**

| Model                  | What it does                       | Real example                             |
| ---------------------- | ---------------------------------- | ---------------------------------------- |
| **InventoryMovement**  | The ledger entry                   | "Received 100 units on 2024-04-04"       |
| **InventoryBatch**     | Groups of stock with same cost     | "Batch #LOT-001 received 2024-04-01"     |
| **InventoryCostLayer** | Tracks which cost with which units | "50 units from this batch cost $10/unit" |

**How it tracks quantity:**

```
Warehouse Layout:
├── Material: Aluminum Sheet
│   ├── Batch #LOT-001 (received 2024-01-01)
│   │   └── 100 units @ $10/unit
│   ├── Batch #LOT-002 (received 2024-02-01)
│   │   └── 80 units @ $12/unit
│   └── Balance: 180 on-hand
```

**3 different quantities tracked:**

- `on_hand_qty`: What we physically have
- `reserved_qty`: Promised to customers (not yet shipped)
- `available_qty`: on_hand - reserved (what we can actually use)

### **C. FIFO (First In, First Out) - Consumption Order**

**Simple analogy:**

- Like a grocery store rotating milk
- Old stock is used before new stock
- Tracks exact cost of consumed units

**How it works:**

```
All movements also create "cost layers"

When we RECEIVE:
  InventoryMovement created → creates InventoryCostLayer

When we ISSUE (consume):
  InventoryMovement created
  Cost layers consumed from oldest first (FIFO)

Result: We know EXACT cost of goods sold
```

**Example:**

```
Receive 100 units @ $10/unit (Layer 1)
Receive 50 units @ $12/unit (Layer 2)
Issue 120 units

FIFO says:
- Take all 100 from Layer 1: 100 * $10 = $1000
- Take 20 from Layer 2: 20 * $12 = $240
- Total COGS: $1240
- Layer 2 has 30 units remaining @ $12
```

### **D. MOVEMENT TYPES - Everything is a Movement**

There are 11 types of stock movements. Every one is recorded as a transaction:

```
PURCHASE_RECEIPT       = Stock came in from supplier
PRODUCTION_OUTPUT      = We made something and added to stock
SALES_ISSUE            = We sold something, stock goes out
PRODUCTION_CONSUMPTION = Materials consumed in manufacturing
ADJUSTMENT_IN/OUT      = Manual correction
TRANSFER_IN/OUT        = Moved between warehouses
RETURN_IN/OUT          = Customer returned or we returned to supplier
SCRAP_OUT              = Damaged goods removed
```

**Why this matters:**

- Each type can have different rules
- Some can go negative (adjustments), some can't (sales)
- Audit trail shows exactly what type of movement happened

### **E. COSTING METHODS - Accounting Way to Value Inventory**

Your system supports multiple methods (configured per organization):

```php
'weighted_average'  // Popular: smooths out price changes
'fifo'             // What we use: oldest cost first
'lifo'             // Newer cost first (less common)
'standard'         // Fixed cost regardless of actual price
```

The CostingMethodHelper looks up org's chosen method, then:

- **Material cost calculation** uses current/historical prices
- **BOM cost calculation** recursively calculates all materials
- **Product cost calculation** uses its active BOM cost

---

## 3. REAL REQUEST WALKTHROUGH

### **Scenario: Calculate Manufacturing Cost of a Product**

```
POST /api/v1/manufacturing/product-cost
{
  "product_id": "uuid-123",
  "organization_id": "org-456",
  "quantity": 10,
  "effective_date": "2024-04-04",
  "use_active_bom": true
}
```

**Step-by-step execution:**

```
1. ProductCostingService receives the request
   ├─ Find Product model
   ├─ Get its active BOM
   └─ Convert to CalculateBomCostDTO

2. BomCostService.calculateBomCost() called
   ├─ Load BOM with all items
   ├─ For EACH item in BOM:
   │  ├─ If it's a material:
   │  │   ├─ Get material's current price (via MaterialCostService)
   │  │   ├─ Apply wastage: 1 unit needed = 1 + wastage%
   │  │   ├─ Calculate: quantity * unitPrice = baseCost
   │  │   ├─ Calculate: wastageQty * unitPrice = wastageCost
   │  │   └─ Create BomItemCostDTO
   │  │
   │  └─ If it's a sub-product:
   │      ├─ Recursively call calculateBomCost()
   │      ├─ Get its active BOM
   │      └─ Calculate its cost (which calculates ITS materials)
   │
   ├─ Sum all items: totalBaseCost + totalWastageAmount
   └─ Calculate unit cost: totalCost / quantity

3. Return CostCalculationResultDTO
   {
     type: 'product',
     itemName: 'Widget A',
     baseCost: 150.00,
     wastageAmount: 7.50,
     totalCost: 157.50,
     quantity: 10,
     unitCost: 15.75,
     bomItems: [
       { lineNo: 1, itemName: 'Steel', baseCost: 100, wastageAmount: 5 },
       { lineNo: 2, itemName: 'Paint', baseCost: 30, wastageAmount: 1.50 },
       { lineNo: 3, itemName: 'Assembly', baseCost: 20, wastageAmount: 1 }
     ]
   }

4. ManufacturingCostController formats with CostCalculationResource
5. Responds with 200 OK
```

**Key complexity here:**

- Recursive: sub-products need their own BOM costs calculated
- Price historical: what was material cost on the effective_date?
- Wastage cascades: each level applies its own wastage

---

### **Scenario: Record An Inventory Movement (Stock Receipt)**

```
POST /api/v1/manufacturing/warehouse/receive
{
  "material_id": "mat-789",
  "warehouse_id": "wh-001",
  "batch_number": "LOT-2024-001",
  "quantity": 500,
  "unit_cost": 12.50,
  "received_date": "2024-04-04"
}
```

**Step-by-step execution:**

```
1. InventoryTransactionService.recordMovement()

2. DB::transaction() wrapper - atomic operation
   {
     // Step A: Validate references
     ├─ Material exists
     ├─ Warehouse exists
     └─ Organization owns both

     // Step B: Get current balance
     ├─ InventoryBalanceService.getBalance()
     └─ Current on_hand_qty = 100

     // Step C: Calculate new balance
     ├─ Movement is INBOUND (PURCHASE_RECEIPT)
     ├─ New balance = 100 + 500 = 600
     └─ Check: Can we have this quantity? YES

     // Step D: Create movement record
     ├─ INSERT INTO inventory_movements
     │  {
     │    organization_id, warehouse_id, material_id, batch_id,
     │    movement_type: 'PURCHASE_RECEIPT',
     │    quantity: 500,
     │    unit_cost: 12.50,
     │    total_cost: 6250,
     │    running_balance: 600,
     │    direction: 'IN',
     │    performed_by: user_id
     │  }
     └─ Return movement ID: mov-123

     // Step E: Create cost layer (only for inbound)
     ├─ InventoryCostLayerService.createLayer()
     ├─ INSERT INTO inventory_cost_layers
     │  {
     │    batch_id, material_id, warehouse_id,
     │    source_movement_id: 'mov-123',
     │    original_qty: 500,
     │    remaining_qty: 500,
     │    unit_cost: 12.50,
     │    received_at: now()
     │  }
     └─ This layer used for FIFO when consuming

     // Step F: Update batch quantity
     ├─ InventoryBatch.increment('remaining_qty', 500)
     └─ Batch now tracks total received vs consumed

     // Step G: Update balance snapshot
     ├─ InventoryBalance.updateQuantities()
     ├─ UPDATE inventory_balances
     │  {
     │    on_hand_qty: 600,
     │    available_qty: 600,
     │    average_cost: (100*oldCost + 500*12.50) / 600
     │  }
     └─ This is the "fast lookup" table

     // Step H: Record audit
     ├─ AuditTrailService.recordAction()
     ├─ INSERT INTO audit_logs
     │  {
     │    organization_id, user_id, action: 'CREATE',
     │    entity_type: 'InventoryMovement',
     │    new_values: { entire movement record }
     │  }
     └─ Permanent record for compliance
   }

3. Return success with movement ID
```

**Why this is complex:**

- Multiple tables updated atomically (DB::transaction)
- Cost layers created for FIFO tracking
- Balance snapshot kept for fast lookups
- Audit trail captured for every step

---

## 4. WHY THIS PART IS COMPLEX

### **Problem #1: FIFO Tracking**

**What problem it solves:**

- Accounting requires knowing exact cost of goods sold
- With multiple purchases at different prices, how do you know cost of 50 units?
- Invoice says 50 units, but which ones?

**Solution implemented:**

- Every inbound movement creates a "cost layer"
- Each layer is a chunk of inventory with one price
- When consuming, grab oldest layer first
- Exact COGS is calculable

**What breaks if removed:**

- Can't meet auditing/accounting standards
- Don't know true profit (can't calculate COGS)
- Tax liability unclear

---

### **Problem #2: Recursive BOMs**

**What problem it solves:**

- Real manufacturing has components that are themselves products
- Example: Assembly requires "Sub-Assembly" which requires "Bolt"
- Need to know final cost of raw materials in everything

**Solution implemented:**

- BomItem can reference either a Material OR a Sub-Product
- When it's a sub-product, recursively calculate its BOM cost
- Each level applies its own wastage

**What breaks if removed:**

- Can't cost complex assemblies
- Assembly work (labor, overhead) not captured
- Can't validate pricing for multi-level products

---

### **Problem #3: Atomicity of Inventory**

**What problem it solves:**

- If you receive stock, you need:
    - Movement record created ✓
    - Cost layer created ✓
    - Balance updated ✓
    - Batch updated ✓
    - Audit logged ✓
- If ANY of these step fails, ALL must roll back
- Partial inventory states are corrupted states

**Solution implemented:**

- DB::transaction() wrapper around entire flow
- If exception thrown, all changes rolled back
- Either all 5 steps succeed or zero steps succeed

**What breaks if removed:**

- Inventory balance doesn't match movements
- Missing cost layers means FIFO fails
- Audit trail incomplete - inconsistent compliance

---

### **Problem #4: Costing Methods Flexibility**

**What problem it solves:**

- Different orgs have different accounting standards
- Some use FIFO, some use Weighted Average
- Can't hardcode one method globally

**Solution implemented:**

- CostingMethodHelper reads org's setting
- MaterialCostService applies appropriate method
- BomCostService calls material service (never hardcodes method)

**What breaks if removed:**

- Only one costing method works
- Some organizations can't use your system
- Compliance issues for different regions

---

## 5. THINGS I SHOULD UNDERSTAND BEFORE BUILDING MORE MODULES

### **Pattern #1: Standard Layer Cake**

Every feature follows this:

```
Controller (API endpoint)
└── Service (business logic)
    └── Models (database)
```

**For new modules:**

- Controller handles request/response
- Service contains IF statements and logic
- Models just save/fetch

**Don't put business logic in:**

- Controllers (they get fat)
- Workers/Jobs (async code)
- Models (they get complex)

---

### **Pattern #2: DTOs for Data Flow**

Every service receives a DTO:

```php
public function calculateBomCost(CalculateBomCostDTO $dto)
```

**Why:**

- Clear contract: "here's what I need"
- Easier to test: mock the DTO
- Easier to extend: add field to DTO, not change signature
- Type-safe: DTO constructor validates

**For new features:**

- Create a CreateXxxDTO for inserts
- Create an UpdateXxxDTO for updates
- Create a CalculateXxxDTO for complex logic

---

### **Pattern #3: Multi-Tenancy via Organization Filter**

Every query filters by organization:

```php
$product = Product::where('organization_id', $organizationId)->find($id);
```

**How it works:**

1. Extract org from authenticated user
2. Pass org to service
3. Service filters all queries by that org

**For new modules:**

- Every table needs `organization_id` column
- Every service method takes `$organizationId` parameter
- Filter all queries by this org

**What this prevents:**

- User A seeing User B's data
- Accidental cross-tenant leaks

---

### **Pattern #4: Transactions Everywhere Critical**

Any operation that touches multiple tables uses DB::transaction:

```php
return DB::transaction(function () {
    // Multiple updates that must all succeed or all fail
});
```

**When to use:**

- Inventory movements (multiple tables)
- Any calculation that updates multiple records
- Financial transactions

**When not needed:**

- Simple GET requests
- Single table inserts
- Calculations with no updates

---

### **Pattern #5: Audit Trail for Everything**

Every important action gets logged:

```php
$this->auditService->recordAction(
    organizationId: $dto->organizationId,
    userId: $dto->performedBy,
    action: 'CREATE',
    newValues: $movement->toArray(),
);
```

**Why:**

- Compliance requirement
- Can answer "who changed what?"
- Historical context for debugging

**For new features:**

- Call `AuditTrailService->recordAction()` for creates/updates
- Pass array of what changed (oldValues + newValues)
- Include who did it (userId)

---

### **Pattern #6: Enums for Constants**

Status and type values are enums, not strings:

```php
enum MovementType: string {
    case PURCHASE_RECEIPT = 'PURCHASE_RECEIPT';
    case SALES_ISSUE = 'SALES_ISSUE';
}
```

**Why:**

- Type-safe: IDE autocomplete
- Prevents typos: can't pass wrong value
- Methods available: `$type->isInbound()`

**For new features:**

- Create enums in `app/Domain/XXX/Enums/`
- Use in models and services
- Add methods for common checks

---

### **Pattern #7: Relationships vs Data Duplication**

Models have relationships, but services don't always use them:

```php
// Model has relationship
public function bom(): BelongsTo { return $this->belongsTo(Bom::class); }

// But service might load explicitly
$bom = Bom::with('items')->find($bomId);

// Why? Performance control
// Can eager load related data intentionally
// Prevents N+1 queries
```

**For new features:**

- Define relationships in models
- Use `with()` in services for explicit loading
- Think about performance upfront

---

### **Pattern #8: Scopes for Filtering**

Models have scopes for common filters:

```php
public function scopeOrganization($query, string $organizationId) {
    return $query->where('organization_id', $organizationId);
}

// Usage:
InventoryMovement::organization($orgId)->get();
```

**For new models:**

- Add scopes for common filters
- Add scopes for status values
- Makes queries cleaner

---

### **Pattern #9: Metadata Column for Flexibility**

Most tables have `metadata` column (JSON):

```php
protected $casts = ['metadata' => 'array'];

// Usage:
$product->metadata = ['color' => 'red', 'size' => 'large'];
```

**Why:**

- Don't need migration to add custom fields
- Different orgs can track different info
- Flexible schema without chaos

**For new features:**

- Add metadata columns to new tables
- Document what you store there (README)
- Don't abuse it - only for optional custom data

---

### **Pattern #10: Resources for API Formatting**

Controllers don't return models directly, they use Resources:

```php
return ApiResponse::send(
    new ProductResource($product),
    "Product created"
);
```

**Why:**

- Consistent API format
- Can hide sensitive fields
- Can transform data for frontend

**For new APIs:**

- Create XXXResource in `app/Http/Resources/`
- Define what fields to include
- Controllers always use resources

---

## 6. KEY FILES TO UNDERSTAND

If you want to dig deeper:

| What               | Where                                                    | Why Important                          |
| ------------------ | -------------------------------------------------------- | -------------------------------------- |
| All routes         | `routes/api_v1.php`                                      | Start here to see what endpoints exist |
| Auth               | `Auth/Services`                                          | How users are tied to orgs             |
| Inventory models   | `Domain/Inventory/Models/`                               | Core of stock tracking                 |
| Manufacturing cost | `Domain/Manufacturing/Services/BomCostService.php`       | Heart of the complexity                |
| Movements          | `Domain/Inventory/Services/InventoryMovementService.php` | How stock transactions work            |
| Transactions       | Any Service with `DB::transaction`                       | Pattern for atomicity                  |
| Audit              | `Domain/Audit/Services/AuditTrailService.php`            | What gets logged                       |

---

## 7. DEBUGGING TIPS

**If inventory balance is wrong:**

- Check `inventory_movements` table - was movement recorded?
- Check `inventory_cost_layers` - was cost layer created for inbound?
- Check `inventory_balances` - does it match sum of movements?

**If costing is wrong:**

- Check material prices at that effective_date
- Check BOM items - do all have prices?
- Check wastage percentages - are they applied?

**If audit trail missing:**

- Check `audit_logs` table
- AuditTrailService is called in transactions
- If transaction rolled back, audit also rolled back

**If data is leaking between orgs:**

- Search for queries without `where('organization_id')`
- Add org filter to any new queries
- Check scope methods are being used

---

## 8. ADDING A NEW MODULE (Quick Reference)

When you add something like Payroll or Shipping:

```
1. Create migration file
   └─ Add organization_id to every table

2. Create models in Domain/XXX/Models/
   ├─ Use UsesUuid trait
   ├─ Add organization()relationship
   └─ Add scopes for common filters

3. Create DTOs in Domain/XXX/DTOs/
   └─ Readonly classes for data flow

4. Create services in Domain/XXX/Services/
   ├─ Accept DTOs
   ├─ Filter by organization
   └─ Use DB::transaction if multi-table

5. Create request validation
   └─ App/Http/Requests/StoreXxxRequest.php

6. Create resources for API response
   └─ App/Http/Resources/XxxResource.php

7. Create controller
   ├─ Inject service
   └─ Use resources in responses

8. Add route to api_v1.php
   └─ Inside auth middleware group

9. Add audit logging
   └─ Call AuditTrailService in service

10. Add tests
    └─ Tests/Feature/ & Tests/Unit/
```

---
