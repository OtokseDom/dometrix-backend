# Manufacturing Cost Engine - Integration Checklist & Quick Start

## Pre-Integration Checklist

Before using the Manufacturing Cost Engine, ensure:

- [ ] Laravel 11+ with PHP 8.1+
- [ ] PostgreSQL database with all migrations applied
- [ ] Sanctum authentication configured
- [ ] Existing tables populated:
    - `organizations`
    - `units` (with codes: g, kg, ml, l, pcs, dozen)
    - `currencies`
    - `categories`
    - `materials` (with organization_id)
    - `material_prices` (with price and effective_date)
    - `products` (with organization_id)
    - `boms` (with version and is_active)
    - `bom_items` (with quantity, wastage_percent)
    - `settings` (with costing_method and inventory_method)

## File Structure Created

```
backend/
├── app/
│   ├── Domain/
│   │   └── Manufacturing/
│   │       ├── Models/
│   │       │   ├── Material.php
│   │       │   ├── MaterialPrice.php
│   │       │   ├── Product.php
│   │       │   ├── Bom.php
│   │       │   └── BomItem.php
│   │       ├── Services/
│   │       │   ├── MaterialCostService.php
│   │       │   ├── BomCostService.php
│   │       │   └── ProductCostingService.php
│   │       ├── DTOs/
│   │       │   ├── CalculateMaterialCostDTO.php
│   │       │   ├── CalculateBomCostDTO.php
│   │       │   ├── CalculateProductCostDTO.php
│   │       │   ├── CostCalculationResultDTO.php
│   │       │   └── BomItemCostDTO.php
│   │       └── Helpers/
│   │           ├── UnitConversionHelper.php
│   │           ├── WastageCalculationHelper.php
│   │           └── CostingMethodHelper.php
│   ├── Http/
│   │   ├── Controllers/API/V1/
│   │   │   └── ManufacturingCostController.php
│   │   ├── Requests/
│   │   │   ├── CalculateMaterialCostRequest.php
│   │   │   ├── CalculateBomCostRequest.php
│   │   │   └── CalculateProductCostRequest.php
│   │   └── Resources/
│   │       ├── CostCalculationResource.php
│   │       └── CostCalculationCollection.php
├── routes/
│   └── api_v1.php (UPDATED with manufacturing routes)
└── documentations/
    ├── MANUFACTURING_COST_ENGINE.md (this file's parent)
    └── MANUFACTURING_COST_ENGINE_QUICK_START.md (this file)
```

## Quick Start

### 1. Basic API Call - Calculate Material Cost

**Request**:

```bash
curl -X POST http://localhost:8000/api/v1/manufacturing/material-cost \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "organization_id": "org-uuid",
    "material_id": "material-uuid",
    "quantity": 100,
    "effective_date": "2026-03-31"
  }'
```

**Response**:

```json
{
    "success": true,
    "message": "Material cost calculated successfully",
    "data": {
        "type": "material",
        "itemId": "material-uuid",
        "itemName": "Flour",
        "itemCode": "MAT_FLOUR",
        "quantity": 100,
        "quantityUnit": "g",
        "costs": {
            "baseCost": 50,
            "wastageAmount": 0,
            "totalCost": 50
        },
        "unitCost": 0.5
    }
}
```

### 2. Calculate BOM Cost

**Request**:

```bash
curl -X POST http://localhost:8000/api/v1/manufacturing/bom-cost \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "organization_id": "org-uuid",
    "bom_id": "bom-uuid",
    "quantity": 1,
    "effective_date": "2026-03-31"
  }'
```

### 3. Calculate Product Cost

**Request**:

```bash
curl -X POST http://localhost:8000/api/v1/manufacturing/product-cost \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "organization_id": "org-uuid",
    "product_id": "product-uuid",
    "quantity": 100,
    "use_active_bom": true
  }'
```

### 4. Get Product Cost Summary

**Request**:

```bash
curl http://localhost:8000/api/v1/manufacturing/products/PRODUCT_UUID/cost-summary \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Get Material Price History

**Request**:

```bash
curl "http://localhost:8000/api/v1/manufacturing/materials/MATERIAL_UUID/price-history?from_date=2026-01-01&to_date=2026-03-31" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Using Services in Code

### Example 1: Direct Service Usage

```php
use App\Domain\Manufacturing\Services\MaterialCostService;
use App\Domain\Manufacturing\DTOs\CalculateMaterialCostDTO;

// Inject service via constructor or use app()
$service = app(MaterialCostService::class);

$dto = new CalculateMaterialCostDTO(
    organizationId: 'org-uuid',
    materialId: 'material-uuid',
    quantity: 50,
    effectiveDate: '2026-03-31'
);

$result = $service->calculateMaterialCost($dto);

echo "Unit Cost: " . $result->unitCost;
echo "Total: " . $result->totalCost;
```

### Example 2: Complex BOM with Sub-Products

```php
use App\Domain\Manufacturing\Services\BomCostService;
use App\Domain\Manufacturing\DTOs\CalculateBomCostDTO;

$service = app(BomCostService::class);

$dto = new CalculateBomCostDTO(
    organizationId: 'org-uuid',
    bomId: 'bom-uuid',
    quantity: 10,
    effectiveDate: '2026-03-31'
);

$result = $service->calculateBomCost($dto);

// Access breakdown
foreach ($result->bomItems as $item) {
    if ($item->itemType === 'material') {
        echo "{$item->itemName}: {$item->quantity} {$item->quantityUnit} @ {$item->unitPrice} = {$item->totalCost}\n";
    } elseif ($item->itemType === 'sub_product') {
        echo "{$item->itemName} (assembly): {$item->quantity} x {$item->unitPrice} = {$item->totalCost}\n";
        // Sub-products have $item->subProductCost as JSON for nested detail
    }
}

echo "Total BOM Cost: {$result->totalCost}";
```

### Example 3: Product Costing for Quotes

```php
use App\Domain\Manufacturing\Services\ProductCostingService;
use App\Domain\Manufacturing\DTOs\CalculateProductCostDTO;

$service = app(ProductCostingService::class);

$dto = new CalculateProductCostDTO(
    organizationId: 'org-uuid',
    productId: 'product-uuid',
    quantity: 100,  // Need cost for 100 units
    useActiveBom: true
);

$result = $service->calculateProductCost($dto);

// Unit cost
$unitCost = $result->unitCost;

// Total for order
$orderTotal = $result->totalCost;

// Margin calculation (example, 30% markup)
$sellingPrice = $unitCost * 1.3;
$orderSelling = $orderTotal * 1.3;
```

## Data Model Examples

### Setting Up a Product with Costing

```php
use App\Domain\Manufacturing\Models\Product;
use App\Domain\Manufacturing\Models\Bom;
use App\Domain\Manufacturing\Models\BomItem;

// 1. Create Product
$product = Product::create([
    'organization_id' => 'org-uuid',
    'code' => 'PROD_BREAD',
    'name' => 'Bread Loaf',
    'unit_id' => 'unit-g-uuid',
]);

// 2. Create BOM version
$bom = Bom::create([
    'organization_id' => 'org-uuid',
    'product_id' => $product->id,
    'version' => '1.0',
    'is_active' => true,  // This is the active BOM
]);

// 3. Add BOM Items (Material)
BomItem::create([
    'organization_id' => 'org-uuid',
    'bom_id' => $bom->id,
    'material_id' => 'flour-uuid',
    'quantity' => 400,
    'unit_id' => 'unit-g-uuid',
    'wastage_percent' => 2.5,
    'line_no' => 1,
]);

// 4. Add BOM Items (Sub-Product)
BomItem::create([
    'organization_id' => 'org-uuid',
    'bom_id' => $bom->id,
    'sub_product_id' => 'sub-product-uuid',  // Null material_id
    'quantity' => 1,
    'unit_id' => 'unit-pcs-uuid',
    'wastage_percent' => 0,
    'line_no' => 2,
]);

// Cost is now calculable
```

## Error Messages & Solutions

| Error                                      | Cause                                  | Solution                                                             |
| ------------------------------------------ | -------------------------------------- | -------------------------------------------------------------------- |
| "No material price found..."               | Price not set or not effective on date | Add price to `material_prices` with `effective_date <= request_date` |
| "Product has no active BOM"                | No BOM with `is_active = true`         | Set one BOM version as active                                        |
| "Sub-product has no active BOM"            | Referenced product has no active BOM   | Ensure sub-products also have active BOMs                            |
| "Material does not belong to organization" | Wrong org_id in request                | Verify material's `organization_id`                                  |
| "Unit conversion not supported"            | Unit pair not in converter             | Use supported units or add custom mapping                            |
| "Material not found" / "Product not found" | Invalid UUID                           | Verify IDs exist in database                                         |

## Testing & Validation

### Manual Test Data

Create test data for development:

```php
use App\Domain\Manufacturing\Models\Material;
use App\Domain\Manufacturing\Models\MaterialPrice;

// 1. Create material
$flour = Material::create([
    'organization_id' => 'org-uuid',
    'code' => 'MAT_FLOUR_TEST',
    'name' => 'Test Flour',
    'unit_id' => 'unit-kg-uuid',
]);

// 2. Set price
MaterialPrice::create([
    'organization_id' => 'org-uuid',
    'material_id' => $flour->id,
    'price' => 2.50,
    'effective_date' => '2026-03-31',
]);

// 3. Calculate
$result = app(\App\Domain\Manufacturing\Services\MaterialCostService::class)
    ->calculateMaterialCost(
        new \App\Domain\Manufacturing\DTOs\CalculateMaterialCostDTO(
            organizationId: 'org-uuid',
            materialId: $flour->id,
            quantity: 100,
        )
    );

// Should show: 250 cost for 100 kg @ 2.50/kg
```

## Performance Tips

1. **Batch Operations**: For calculating many products, cache results

    ```php
    cache()->rememberForever("cost:org:product", fn() => $service->calculate(...));
    ```

2. **Eager Load Relations**:

    ```php
    $bom = Bom::with('items', 'product')->find($bomId);
    ```

3. **Index Database Queries**:

    ```sql
    CREATE INDEX idx_material_prices_date ON material_prices(organization_id, material_id, effective_date DESC);
    ```

4. **Avoid Deep Nesting**: Limit BOM sub-product depth to 3-4 levels to avoid recursion overhead

## Next Steps

1. **Verify Database**: Ensure all required tables are populated with test data
2. **Test API Endpoints**: Use the examples above with your actual UUIDs
3. **Integrate with Quote System**: Use `calculateProductCost()` to generate pricing
4. **Set Up Pricing Imports**: Populate `material_prices` from vendor price lists
5. **Monitor Performance**: Track query times for large BOMs

## Support Resources

- Full API Documentation: `MANUFACTURING_COST_ENGINE.md`
- Code Structure: Files in `app/Domain/Manufacturing/`
- Database Schema: Migration files in `database/migrations/`
- Example Seeders: `database/seeders/`

---

**Last Updated**: 2026-03-31
**Version**: 1.0
**Compatibility**: Laravel 11+, PHP 8.1+, PostgreSQL
