# Manufacturing Cost Engine - Postman Collection Guide

## Overview

This Postman collection provides 5 endpoints for calculating manufacturing costs including material costs, BOM costs, and product costs with support for historical pricing and unit conversions.

## Endpoints

### 1. Calculate Material Cost

**POST** `/api/v1/manufacturing/material-cost`

Calculate the cost of a single material for a given quantity.

**Request Parameters:**

- `organization_id` (string, UUID): Organization identifier
- `material_id` (string, UUID): Material identifier
- `quantity` (number): Quantity to calculate (minimum 0.001)
- `effective_date` (string, optional): Date in YYYY-MM-DD format for price lookup
- `costing_method` (string, optional): One of `weighted_average`, `fifo`, `lifo`, `standard`

**Response Example:**

```json
{
	"data": {
		"type": "material",
		"itemId": "material-uuid",
		"itemName": "Steel Sheet Grade A",
		"itemCode": "SS-001",
		"quantity": 10.5,
		"quantityUnit": "kg",
		"costs": {
			"baseCost": 525.0,
			"wastageAmount": 0.0,
			"totalCost": 525.0
		},
		"unitCost": 50.0,
		"bomItems": null,
		"metadata": {}
	},
	"message": "Material cost calculated successfully"
}
```

**Setup Instructions:**

1. Set `{{organization_id}}` in collection variables
2. Set `{{material_id}}` to a valid material ID
3. Adjust quantity and effective_date as needed
4. Send request

---

### 2. Get Material Price History

**GET** `/api/v1/manufacturing/materials/{material_id}/price-history`

Retrieve historical pricing data for a material within a date range.

**Query Parameters:**

- `from_date` (string, optional): Start date in YYYY-MM-DD format
- `to_date` (string, optional): End date in YYYY-MM-DD format

**Response Example:**

```json
{
	"data": {
		"material_id": "material-uuid",
		"prices": [
			{
				"effective_date": "2026-01-01",
				"price": 48.0,
				"currency": "USD"
			},
			{
				"effective_date": "2026-02-15",
				"price": 49.5,
				"currency": "USD"
			},
			{
				"effective_date": "2026-03-31",
				"price": 50.0,
				"currency": "USD"
			}
		]
	},
	"message": "Material price history retrieved"
}
```

**Setup Instructions:**

1. Set `{{material_id}}` in collection variables
2. Adjust date range query parameters
3. Send request

---

### 3. Calculate BOM Cost

**POST** `/api/v1/manufacturing/bom-cost`

Calculate the total cost of a Bill of Materials with breakdown by line items.

**Request Parameters:**

- `organization_id` (string, UUID): Organization identifier
- `bom_id` (string, UUID): BOM identifier
- `quantity` (number, optional): Default 1. Quantity to calculate
- `effective_date` (string, optional): Date in YYYY-MM-DD format
- `costing_method` (string, optional): One of `weighted_average`, `fifo`, `lifo`, `standard`
- `include_product_cost` (boolean, optional): Default false. Include parent product cost

**Response Example:**

```json
{
	"data": {
		"type": "bom",
		"itemId": "bom-uuid",
		"itemName": "Assembly BOM-001",
		"itemCode": "BOM-001",
		"quantity": 1,
		"quantityUnit": "pcs",
		"costs": {
			"baseCost": 1250.0,
			"wastageAmount": 25.0,
			"totalCost": 1275.0
		},
		"unitCost": 1275.0,
		"bomItems": [
			{
				"lineNo": 1,
				"bomItemId": "item-uuid",
				"itemType": "material",
				"itemId": "material-uuid",
				"itemName": "Steel Sheet",
				"itemCode": "SS-001",
				"quantity": 5,
				"quantityUnit": "kg",
				"wastagePercent": 2.0,
				"quantityWithWastage": 5.1,
				"unitPrice": 50.0,
				"costs": {
					"baseCost": 250.0,
					"wastageAmount": 5.0,
					"totalCost": 255.0
				}
			},
			{
				"lineNo": 2,
				"bomItemId": "item-uuid",
				"itemType": "material",
				"itemId": "material-uuid",
				"itemName": "Bolts M10",
				"itemCode": "BOLT-M10",
				"quantity": 100,
				"quantityUnit": "pcs",
				"wastagePercent": 1.0,
				"quantityWithWastage": 101,
				"unitPrice": 10.0,
				"costs": {
					"baseCost": 1000.0,
					"wastageAmount": 10.0,
					"totalCost": 1010.0
				}
			}
		],
		"metadata": {}
	},
	"message": "BOM cost calculated successfully"
}
```

**Setup Instructions:**

1. Set `{{organization_id}}` in collection variables
2. Set `{{bom_id}}` to a valid BOM ID
3. Adjust quantity and costing parameters
4. Send request
5. Captured variables: `bom_total_cost`, `bom_unit_cost`

---

### 4. Calculate Product Cost

**POST** `/api/v1/manufacturing/product-cost`

Calculate the total cost of a product including its active BOM with all materials and sub-assemblies.

**Request Parameters:**

- `organization_id` (string, UUID): Organization identifier
- `product_id` (string, UUID): Product identifier
- `quantity` (number, optional): Default 1. Quantity to calculate
- `effective_date` (string, optional): Date in YYYY-MM-DD format
- `costing_method` (string, optional): One of `weighted_average`, `fifo`, `lifo`, `standard`
- `use_active_bom` (boolean, optional): Default true. Use active BOM version

**Response Example:**

```json
{
	"data": {
		"type": "product",
		"itemId": "product-uuid",
		"itemName": "Assembled Widget A",
		"itemCode": "WIDGET-A",
		"quantity": 1,
		"quantityUnit": "pcs",
		"costs": {
			"baseCost": 1250.0,
			"wastageAmount": 25.0,
			"totalCost": 1275.0
		},
		"unitCost": 1275.0,
		"bomItems": [
			{
				"lineNo": 1,
				"bomItemId": "item-uuid",
				"itemType": "material",
				"itemId": "material-uuid",
				"itemName": "Steel Sheet",
				"itemCode": "SS-001",
				"quantity": 5,
				"quantityUnit": "kg",
				"wastagePercent": 2.0,
				"quantityWithWastage": 5.1,
				"unitPrice": 50.0,
				"costs": {
					"baseCost": 250.0,
					"wastageAmount": 5.0,
					"totalCost": 255.0
				}
			},
			{
				"lineNo": 2,
				"bomItemId": "item-uuid",
				"itemType": "sub_product",
				"itemId": "product-uuid",
				"itemName": "Sub-Assembly B",
				"itemCode": "SUB-B",
				"quantity": 2,
				"quantityUnit": "pcs",
				"wastagePercent": 0.5,
				"quantityWithWastage": 2.01,
				"unitPrice": 300.0,
				"costs": {
					"baseCost": 600.0,
					"wastageAmount": 3.0,
					"totalCost": 603.0
				},
				"subProductCost": {
					"type": "product",
					"costs": {
						"baseCost": 300.0,
						"wastageAmount": 1.5,
						"totalCost": 301.5
					}
				}
			}
		],
		"metadata": {}
	},
	"message": "Product cost calculated successfully"
}
```

**Setup Instructions:**

1. Set `{{organization_id}}` in collection variables
2. Set `{{product_id}}` to a valid product ID
3. Adjust quantity and costing parameters
4. Send request
5. Captured variables: `product_total_cost`, `product_unit_cost`

---

### 5. Get Product Cost Summary

**GET** `/api/v1/manufacturing/products/{product_id}/cost-summary`

Retrieve a cost summary for a specific product at a given effective date.

**Query Parameters:**

- `effective_date` (string, optional): Date in YYYY-MM-DD format

**Response Example:**

```json
{
	"data": {
		"product_id": "product-uuid",
		"product_name": "Assembled Widget A",
		"product_code": "WIDGET-A",
		"effective_date": "2026-03-31",
		"active_bom_id": "bom-uuid",
		"active_bom_version": "1.2",
		"cost_summary": {
			"base_cost": 1250.0,
			"wastage_cost": 25.0,
			"total_cost": 1275.0,
			"unit_cost": 1275.0
		},
		"cost_history": [
			{
				"date": "2026-03-01",
				"base_cost": 1200.0,
				"wastage_cost": 24.0,
				"total_cost": 1224.0
			},
			{
				"date": "2026-03-15",
				"base_cost": 1225.0,
				"wastage_cost": 24.5,
				"total_cost": 1249.5
			},
			{
				"date": "2026-03-31",
				"base_cost": 1250.0,
				"wastage_cost": 25.0,
				"total_cost": 1275.0
			}
		]
	},
	"message": "Product cost summary retrieved"
}
```

**Setup Instructions:**

1. Set `{{product_id}}` in collection variables
2. Optionally adjust effective_date query parameter
3. Send request

---

## Environment Variables

Add these variables to your Postman Collection:

| Variable             | Purpose                               | Example                                |
| -------------------- | ------------------------------------- | -------------------------------------- |
| `base_url`           | Base URL of the API                   | `http://localhost:8000`                |
| `auth_token`         | Bearer token for authentication       | Set after login                        |
| `organization_id`    | Organization UUID                     | `550e8400-e29b-41d4-a716-446655440000` |
| `material_id`        | Material UUID                         | `550e8400-e29b-41d4-a716-446655440001` |
| `product_id`         | Product UUID                          | `550e8400-e29b-41d4-a716-446655440002` |
| `bom_id`             | BOM UUID                              | `550e8400-e29b-41d4-a716-446655440003` |
| `material_unit_cost` | Captured from Calculate Material Cost | Auto-populated                         |
| `bom_total_cost`     | Captured from Calculate BOM Cost      | Auto-populated                         |
| `bom_unit_cost`      | Captured from Calculate BOM Cost      | Auto-populated                         |
| `product_total_cost` | Captured from Calculate Product Cost  | Auto-populated                         |
| `product_unit_cost`  | Captured from Calculate Product Cost  | Auto-populated                         |

---

## Authentication

All endpoints require a valid Bearer token:

1. First, call **Authentication → Login** endpoint
2. Provide credentials (email, password)
3. The response token will be automatically set to `{{auth_token}}`
4. All subsequent requests will use this token

---

## Usage Workflow

### Simple Material Cost Calculation

1. Set organization_id and material_id variables
2. Call **Calculate Material Cost**
3. Check the response for unitCost

### BOM Assembly Costing

1. Set organization_id and bom_id variables
2. Call **Calculate BOM Cost**
3. Review the bomItems breakdown for line-item costs
4. Use captured `bom_total_cost` for downstream calculations

### Product Costing with Sub-Assemblies

1. Set organization_id and product_id variables
2. Call **Calculate Product Cost** with `use_active_bom: true`
3. Review bomItems which include sub-product costs
4. Use captured `product_unit_cost` for margin calculations

### Historical Price Analysis

1. Set material_id variable
2. Call **Get Material Price History** with from_date and to_date
3. Analyze price trends over time

### Product Cost Summary Report

1. Set product_id variable
2. Call **Get Product Cost Summary**
3. Use cost_history for costing trends

---

## Error Handling

### Common Errors

**400 Bad Request**

- Missing required fields
- Invalid UUID format
- Quantity less than 0.001
- Invalid costing_method

**401 Unauthorized**

- Missing or invalid authentication token
- User doesn't have access to organization

**404 Not Found**

- Material, Product, or BOM not found
- Invalid organization_id

**422 Unprocessable Entity**

- Validation error on request data
- Check the error message for specific field issues

---

## Performance Tips

1. **Cache Material Prices**: Use price history requests to cache static prices
2. **Batch Calculations**: Call endpoints sequentially for multiple products
3. **Optimize Requests**: Use specific effective_date if not needed current price
4. **Monitor Response Times**: Average response times: Material=50ms, BOM=100ms, Product=200ms

---

## Advanced Usage

### Calculating Multi-Unit Quantities

```
"quantity": 10.5  // Any decimal value >= 0.001
"quantityUnit": "kg" // Unit conversion handled automatically
```

### Handling Wastage

Wastage is calculated as a percentage at the BOM item level:

- Formula: `quantity_with_wastage = quantity × (1 + wastage_percent / 100)`
- Costs are tracked separately: `baseCost` vs `wastageAmount`

### Sub-Product BOM Recursion

When a BOM contains sub-products (nested BOMs), each sub-product cost is calculated recursively:

- Check `subProductCost` field in bomItems
- Supports up to 3-4 levels of nesting

---

## Support

For issues or questions:

1. Check [MANUFACTURING_COST_ENGINE.md](../../../documentations/MANUFACTURING_COST_ENGINE.md) for detailed API reference
2. Review [MANUFACTURING_COST_ENGINE_EXAMPLES.md](../../../documentations/MANUFACTURING_COST_ENGINE_EXAMPLES.md) for code samples
3. Check error messages in the response body for specific issue details
