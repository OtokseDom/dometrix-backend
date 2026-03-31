# Manufacturing Cost Engine - Implementation Guide

## Overview

The Manufacturing Cost Engine is a domain-driven implementation for calculating manufacturing costs in the Dometrix ERP system. It handles:

- **Material Costing**: Current effective price of materials
- **BOM (Bill of Materials) Costing**: Aggregated cost from materials and sub-products
- **Wastage Handling**: Percentage-based wastage in BOM items
- **Unit Conversions**: Automatic conversion between different units (kg ↔ g, l ↔ ml, etc.)
- **Product Costing**: Total manufacturing cost using active or specified BOM
- **Multi-tenant Safety**: All calculations respect organization_id
- **Flexible Costing Methods**: Support for weighted average, FIFO, LIFO, and standard costing (framework in place)

## Architecture

### Domain Structure

```
App/Domain/Manufacturing/
├── Models/              # Eloquent Models for core entities
│   ├── Material.php
│   ├── MaterialPrice.php
│   ├── Product.php
│   ├── Bom.php
│   └── BomItem.php
├── Services/            # Core business logic
│   ├── MaterialCostService.php
│   ├── BomCostService.php
│   └── ProductCostingService.php
├── DTOs/                # Data Transfer Objects
│   ├── CalculateMaterialCostDTO.php
│   ├── CalculateBomCostDTO.php
│   ├── CalculateProductCostDTO.php
│   ├── CostCalculationResultDTO.php
│   └── BomItemCostDTO.php
└── Helpers/             # Supporting utilities
    ├── UnitConversionHelper.php
    ├── WastageCalculationHelper.php
    └── CostingMethodHelper.php
```

### API Routes

All routes are under `/api/v1/manufacturing` and require authentication (`auth:sanctum`).

#### Material Cost Calculation

**POST** `/api/v1/manufacturing/material-cost`

Calculate the cost of a given quantity of material.

```json
{
    "organization_id": "uuid",
    "material_id": "uuid",
    "quantity": 100.5,
    "effective_date": "2026-03-31",
    "costing_method": "weighted_average"
}
```

Response:

```json
{
    "success": true,
    "message": "Material cost calculated successfully",
    "data": {
        "type": "material",
        "itemId": "uuid",
        "itemName": "Flour",
        "itemCode": "MAT_FLOUR",
        "organizationId": "uuid",
        "quantity": 100.5,
        "quantityUnit": "g",
        "costs": {
            "baseCost": 50.25,
            "wastageAmount": 0,
            "totalCost": 50.25
        },
        "unitCost": 0.5,
        "metadata": {
            "effectiveDate": "2026-03-31"
        }
    }
}
```

#### Material Price History

**GET** `/api/v1/manufacturing/materials/{id}/price-history?from_date=2026-01-01&to_date=2026-03-31`

Retrieve price history for a material within a date range.

Response:

```json
{
    "success": true,
    "data": {
        "material_id": "uuid",
        "prices": [
            {
                "price": 0.55,
                "effectiveDate": "2026-03-15",
                "createdAt": "2026-03-15T10:30:00Z"
            },
            {
                "price": 0.5,
                "effectiveDate": "2026-02-01",
                "createdAt": "2026-02-01T09:00:00Z"
            }
        ]
    }
}
```

#### BOM Cost Calculation

**POST** `/api/v1/manufacturing/bom-cost`

Calculate the total cost of a BOM with optional breakdown.

```json
{
    "organization_id": "uuid",
    "bom_id": "uuid",
    "quantity": 1,
    "effective_date": "2026-03-31",
    "costing_method": "weighted_average",
    "include_product_cost": false
}
```

Response:

```json
{
    "success": true,
    "message": "BOM cost calculated successfully",
    "data": {
        "type": "bom",
        "itemId": "bom-uuid",
        "itemName": "Bread Loaf",
        "itemCode": "PROD_BREAD",
        "organizationId": "uuid",
        "quantity": 1,
        "quantityUnit": "g",
        "costs": {
            "baseCost": 125.5,
            "wastageAmount": 3.15,
            "totalCost": 128.65
        },
        "unitCost": 128.65,
        "bomItems": [
            {
                "lineNo": "1",
                "bomItemId": "uuid",
                "itemType": "material",
                "itemId": "uuid",
                "itemName": "Flour",
                "itemCode": "MAT_FLOUR",
                "quantity": 400,
                "quantityUnit": "g",
                "wastagePercent": 2.5,
                "quantityWithWastage": 410,
                "unitPrice": 0.5,
                "costs": {
                    "baseCost": 200,
                    "wastageAmount": 5,
                    "totalCost": 205
                }
            }
        ],
        "metadata": {
            "bomVersion": "1.0",
            "bomIsActive": true,
            "itemCount": 8,
            "effectiveDate": "2026-03-31"
        }
    }
}
```

#### Product Cost Calculation

**POST** `/api/v1/manufacturing/product-cost`

Calculate the manufacturing cost of a product using its active BOM.

```json
{
    "organization_id": "uuid",
    "product_id": "uuid",
    "quantity": 100,
    "effective_date": "2026-03-31",
    "costing_method": "weighted_average",
    "use_active_bom": true
}
```

Response: Same structure as BOM cost response.

#### Product Cost Summary

**GET** `/api/v1/manufacturing/products/{id}/cost-summary?effective_date=2026-03-31`

Get a quick summary of product costing without detailed breakdown.

Response:

```json
{
    "success": true,
    "data": {
        "productId": "uuid",
        "productName": "Bread Loaf",
        "productCode": "PROD_BREAD",
        "unitCost": 128.65,
        "baseCost": 125.5,
        "wastageAmount": 3.15,
        "totalCost": 128.65,
        "itemCount": 8,
        "metadata": {
            "bomVersion": "1.0",
            "bomIsActive": true
        }
    }
}
```

## Key Features

### 1. Multi-Tenant Safety

All queries filter by `organization_id`. The cost calculation respects the organization context:

```php
$dto = new CalculateMaterialCostDTO(
    organizationId: $request->organization_id,  // Enforced throughout
    materialId: $request->material_id,
    quantity: $request->quantity,
);
```

### 2. Wastage Handling

Wastage is applied at the BOM item level. For a BOM item with 100 units and 2.5% wastage:

- **Base Quantity**: 100
- **Wastage Amount**: 2.5 (100 × 2.5%)
- **Quantity with Wastage**: 102.5
- **Base Cost**: 100 × unit_price
- **Wastage Cost**: 2.5 × unit_price
- **Total Cost**: Base Cost + Wastage Cost

```php
use App\Domain\Manufacturing\Helpers\WastageCalculationHelper;

// Add wastage
$qtyWithWastage = WastageCalculationHelper::addWastage($quantity, $wastagePercent);

// Calculate wastage cost
$wastageCost = WastageCalculationHelper::calculateWastageCost(
    $quantity,
    $unitPrice,
    $wastagePercent
);
```

### 3. Unit Conversion

Supported conversions use standard metric relationships:

```php
use App\Domain\Manufacturing\Helpers\UnitConversionHelper;

// Convert 1 kg to grams
$grams = UnitConversionHelper::convert(1, 'kg', 'g', $organizationId);
// Result: 1000
```

Supported units:

- **Weight**: g, kg, mg
- **Volume**: ml, l
- **Count**: pcs, dozen

### 4. Recursive BOM Support

Sub-products (assemblies) are automatically costed by calculating their own BOMs:

```php
// If a BOM item references a sub-product instead of a material:
// 1. The sub-product's active BOM is loaded
// 2. Its BOM cost is calculated recursively
// 3. The result becomes the unit price for the parent BOM item
```

Example: A Cake assembly might include a Sponge Cake sub-product, which itself has a BOM.

### 5. Organization Settings Integration

Costing methods are stored in the `settings` table:

```php
use App\Domain\Manufacturing\Helpers\CostingMethodHelper;

$method = CostingMethodHelper::getOrgCostingMethod($organizationId);
// Returns: 'weighted_average', 'fifo', 'lifo', or 'standard'
```

Currently, all methods use the current effective price. Framework is in place for future FIFO/LIFO implementations.

### 6. Error Handling

All services throw descriptive exceptions:

```php
try {
    $result = $service->calculateBomCost($dto);
} catch (\Exception $e) {
    // "No material price found for material 'MAT_FLOUR' on date 2026-03-31."
    // "Product 'PROD_BREAD' has no active BOM defined."
    // "Sub-product 'SUBPROD_X' has no active BOM."
}
```

## Database Schema

### Materials Table

```sql
CREATE TABLE materials (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    code VARCHAR(255) UNIQUE(organization_id, code),
    name VARCHAR(255),
    category_id UUID,
    unit_id UUID NOT NULL,
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

### Material Prices Table

```sql
CREATE TABLE material_prices (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    material_id UUID NOT NULL,
    price DECIMAL(20, 4),
    effective_date DATE,
    created_by UUID,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    UNIQUE(organization_id, material_id, effective_date),
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (material_id) REFERENCES materials(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Products Table

```sql
CREATE TABLE products (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    code VARCHAR(255) UNIQUE(organization_id, code),
    name VARCHAR(255),
    description TEXT,
    unit_id UUID NOT NULL,
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
```

### BOM Table

```sql
CREATE TABLE boms (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    product_id UUID NOT NULL,
    version VARCHAR(255),
    is_active BOOLEAN DEFAULT FALSE,
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    UNIQUE(organization_id, product_id, version),
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### BOM Items Table

```sql
CREATE TABLE bom_items (
    id UUID PRIMARY KEY,
    organization_id UUID NOT NULL,
    bom_id UUID NOT NULL,
    material_id UUID,
    sub_product_id UUID,
    quantity DECIMAL(20, 4),
    unit_id UUID NOT NULL,
    wastage_percent DECIMAL(8, 4) DEFAULT 0,
    line_no INT,
    metadata JSONB,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    FOREIGN KEY (organization_id) REFERENCES organizations(id),
    FOREIGN KEY (bom_id) REFERENCES boms(id),
    FOREIGN KEY (material_id) REFERENCES materials(id),
    FOREIGN KEY (sub_product_id) REFERENCES products(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);
```

## Usage Examples

### Example 1: Calculate Material Cost

```php
use App\Domain\Manufacturing\DTOs\CalculateMaterialCostDTO;
use App\Domain\Manufacturing\Services\MaterialCostService;

$service = app(MaterialCostService::class);

$dto = new CalculateMaterialCostDTO(
    organizationId: 'org-uuid',
    materialId: 'material-uuid',
    quantity: 100,
    effectiveDate: '2026-03-31'
);

$result = $service->calculateMaterialCost($dto);

echo "Unit Cost: " . $result->unitCost;      // 0.50
echo "Total Cost: " . $result->totalCost;    // 50.00
```

### Example 2: Calculate BOM Cost with Breakdown

```php
use App\Domain\Manufacturing\DTOs\CalculateBomCostDTO;
use App\Domain\Manufacturing\Services\BomCostService;

$service = app(BomCostService::class);

$dto = new CalculateBomCostDTO(
    organizationId: 'org-uuid',
    bomId: 'bom-uuid',
    quantity: 10,  // Cost for 10 units
    effectiveDate: '2026-03-31'
);

$result = $service->calculateBomCost($dto);

foreach ($result->bomItems as $item) {
    echo "Line {$item->lineNo}: {$item->itemName} x {$item->quantity}";
    echo " @ {$item->unitPrice} = {$item->totalCost}\n";
}

echo "\nTotal BOM Cost for 10 units: {$result->totalCost}\n";
echo "Cost per unit: {$result->unitCost}\n";
```

### Example 3: Calculate Product Cost

```php
use App\Domain\Manufacturing\DTOs\CalculateProductCostDTO;
use App\Domain\Manufacturing\Services\ProductCostingService;

$service = app(ProductCostingService::class);

$dto = new CalculateProductCostDTO(
    organizationId: 'org-uuid',
    productId: 'product-uuid',
    quantity: 100,
    useActiveBom: true  // Use the active BOM
);

$result = $service->calculateProductCost($dto);

// Quick summary
$summary = $service->getProductCostSummary(
    'org-uuid',
    'product-uuid'
);

echo "Product: {$summary['productName']}\n";
echo "Unit Cost: {$summary['unitCost']}\n";
echo "Items in BOM: {$summary['itemCount']}\n";
```

## Performance Considerations

### Large BOMs

For BOMs with many items, queries are optimized with:

- **Eager Loading**: BOM relationships are loaded with `load()` to avoid N+1 queries
- **Indexing**: Database indexes on `(bom_id, material_id)` and `(bom_id, sub_product_id)`
- **Index on Effective Date**: `material_prices` indexed on `(organization_id, material_id, effective_date)` for efficient price lookups

### Recursive BOMs

Recursive calculations (sub-products) may hit the stack for deeply nested assemblies. Current implementation supports reasonable nesting (3-4 levels typical).

### Caching Opportunities

For frequently calculated costs, consider caching:

```php
$cacheKey = "product_cost:{$organizationId}:{$productId}";
$result = cache()->remember($cacheKey, 3600, function() use ($service, $dto) {
    return $service->calculateProductCost($dto);
});
```

## Future Enhancements

### 1. Advanced Costing Methods

Currently, all costing methods use current effective prices. Future implementations:

- **FIFO**: Use prices in chronological order of material receipt
- **LIFO**: Use prices in reverse order
- **Standard Costing**: Use predetermined standard costs from a table

### 2. Margin & Markup Calculations

Add pricing rules and margin calculations for quote generation.

### 3. Cost Variance Analysis

Track actual vs. standard costs for production orders.

### 4. Multi-Level Costing

Support cost roll-up from raw materials through semi-finished to finished products.

### 5. Batch Costing

Optimize calculations for bulk cost analysis (e.g., "cost all products").

## Troubleshooting

### No Price Found

**Error**: "No material price found for material 'MAT_FLOUR' on date 2026-03-31."

**Solution**: Ensure `material_prices` has an entry for the material with `effective_date <= requested_date`.

### No Active BOM

**Error**: "Product 'PROD_BREAD' has no active BOM defined."

**Solution**: Set `is_active = true` on one BOM version for the product.

### Unit Conversion Not Supported

**Error**: "Unit conversion not supported: xyz to abc"

**Solution**: Add the conversion pair to `UnitConversionHelper::convert()` or use compatible units from the supported list.

### Multi-Tenant Mismatch

**Error**: "Material does not belong to this organization."

**Solution**: Verify the material's `organization_id` matches the request's `organization_id`.

## Testing

Example tests (PHP Unit):

```php
public function test_material_cost_calculation()
{
    $org = Organization::factory()->create();
    $material = Material::factory()->create(['organization_id' => $org->id]);
    MaterialPrice::create([
        'organization_id' => $org->id,
        'material_id' => $material->id,
        'price' => 100,
        'effective_date' => now()->toDateString(),
    ]);

    $service = app(MaterialCostService::class);
    $dto = new CalculateMaterialCostDTO(
        organizationId: $org->id,
        materialId: $material->id,
        quantity: 10,
    );

    $result = $service->calculateMaterialCost($dto);
    $this->assertEquals(1000, $result->totalCost);
}
```

## Support & Maintenance

For issues, debugging, or enhancements:

1. Check error messages for guidance
2. Verify database data consistency
3. Review logs in `storage/logs/laravel.log`
4. Consult the architecture diagrams in documentation folder
