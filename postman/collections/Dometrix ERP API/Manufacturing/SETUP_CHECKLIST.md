# Manufacturing Cost Engine - Postman Setup Checklist

## ✅ Pre-Setup Requirements

- [ ] Postman installed (latest version recommended)
- [ ] Backend API running on configured `base_url`
- [ ] Database migrations completed
- [ ] Master data created (Organizations, Materials, Products, BOMs)

---

## ✅ Collection Setup

### Import Collection

- [ ] Navigate to `postman/collections/Dometrix ERP API`
- [ ] Import collection into Postman
- [ ] Collection appears in left sidebar
- [ ] All folders visible: Authentication, Organizations, Users, etc.
- [ ] Manufacturing folder visible ✨

### Import Environment

- [ ] Navigate to `postman/environments/`
- [ ] Import `Local Development.environment.yaml`
- [ ] Environment appears in top-right selector
- [ ] Select **Local Development** as active environment

### Configure Variables

- [ ] Set `base_url` to your API server (default: `http://localhost:8000`)
- [ ] Keep other variables empty initially
- [ ] Verify environment selector shows **Local Development**

---

## ✅ Authentication Setup

### Login & Token Setup

- [ ] Go to **Authentication** folder
- [ ] Open **Login** request
- [ ] Verify credentials (email, password)
- [ ] Click **Send**
- [ ] Response shows `success: true`
- [ ] Verify `{{auth_token}}` variable populated (click eye icon in env)
- [ ] ✅ Ready to call protected endpoints

---

## ✅ Master Data Verification

Before testing cost calculations, verify master data exists:

### Organizations

- [ ] At least one organization created
- [ ] Note the Organization ID
- [ ] Set `{{organization_id}}` variable

### Materials

- [ ] At least one material created in database
- [ ] Material has pricing data (material_prices table)
- [ ] Note the Material ID
- [ ] Set `{{material_id}}` variable

### Products

- [ ] At least one product created in database
- [ ] Note the Product ID
- [ ] Set `{{product_id}}` variable

### BOMs (Bills of Materials)

- [ ] At least one BOM created
- [ ] BOM contains line items (materials or sub-products)
- [ ] BOM is marked as active (is_active = true)
- [ ] Note the BOM ID
- [ ] Set `{{bom_id}}` variable

### Verify with Queries

```sql
-- Check organizations
SELECT id, name FROM organizations WHERE deleted_at IS NULL;

-- Check materials with pricing
SELECT m.id, m.code, COUNT(mp.id) as price_count
FROM materials m
LEFT JOIN material_prices mp ON m.id = mp.material_id
GROUP BY m.id, m.code;

-- Check products
SELECT id, code, name FROM products WHERE deleted_at IS NULL;

-- Check BOMs with items
SELECT b.id, b.code, COUNT(bi.id) as item_count
FROM boms b
LEFT JOIN bom_items bi ON b.id = bi.bom_id
WHERE b.is_active = true
GROUP BY b.id, b.code;
```

---

## ✅ Manufacturing Endpoint Testing

### 1. Calculate Material Cost

- [ ] Open **Manufacturing → Calculate Material Cost**
- [ ] Verify URL: `{{base_url}}/api/v1/manufacturing/material-cost`
- [ ] Verify headers include `Authorization: Bearer {{auth_token}}`
- [ ] Verify request body has:
    - `organization_id`: `{{organization_id}}`
    - `material_id`: `{{material_id}}`
    - `quantity`: 10.5 (or test quantity)
- [ ] Click **Send**
- [ ] Response status: 200 OK ✅
- [ ] Response body contains:
    - `data.type`: "material"
    - `data.costs.totalCost`: number > 0
    - `data.unitCost`: number > 0
- [ ] Console shows: `material_unit_cost` variable set

### 2. Get Material Price History

- [ ] Open **Manufacturing → Get Material Price History**
- [ ] Verify URL includes `{{material_id}}`
- [ ] Verify query params: `from_date` and `to_date`
- [ ] Click **Send**
- [ ] Response status: 200 OK ✅
- [ ] Response body contains:
    - `data.material_id`: UUID
    - `data.prices`: array with at least 1 item
    - Each price has `effective_date`, `price`, `currency`

### 3. Calculate BOM Cost

- [ ] Open **Manufacturing → Calculate BOM Cost**
- [ ] Verify URL: `{{base_url}}/api/v1/manufacturing/bom-cost`
- [ ] Verify request body has:
    - `organization_id`: `{{organization_id}}`
    - `bom_id`: `{{bom_id}}`
    - `quantity`: 1
    - `include_product_cost`: false
- [ ] Click **Send**
- [ ] Response status: 200 OK ✅
- [ ] Response body contains:
    - `data.type`: "bom"
    - `data.costs.totalCost`: number > 0
    - `data.bomItems`: array with materials/products
    - Each item has: lineNo, itemType, quantity, costBreakdown
- [ ] Console shows: `bom_total_cost`, `bom_unit_cost` variables set

### 4. Calculate Product Cost

- [ ] Open **Manufacturing → Calculate Product Cost**
- [ ] Verify URL: `{{base_url}}/api/v1/manufacturing/product-cost`
- [ ] Verify request body has:
    - `organization_id`: `{{organization_id}}`
    - `product_id`: `{{product_id}}`
    - `quantity`: 1
    - `use_active_bom`: true
- [ ] Click **Send**
- [ ] Response status: 200 OK ✅
- [ ] Response body contains:
    - `data.type`: "product"
    - `data.costs.totalCost`: number > 0
    - `data.bomItems`: array with product's BOM items
    - Include sub-product costs if applicable
- [ ] Console shows: `product_total_cost`, `product_unit_cost` variables set

### 5. Get Product Cost Summary

- [ ] Open **Manufacturing → Get Product Cost Summary**
- [ ] Verify URL includes `{{product_id}}`
- [ ] Verify query param: `effective_date` (optional)
- [ ] Click **Send**
- [ ] Response status: 200 OK ✅
- [ ] Response body contains:
    - `data.product_id`: UUID
    - `data.product_code`: string
    - `data.cost_summary`: object with costs
    - `data.cost_history`: array with historical costs

---

## ✅ Advanced Verification

### Multi-Tenant Isolation

- [ ] Create second organization
- [ ] Update `{{organization_id}}` to second org
- [ ] Call Calculate Material Cost
- [ ] Verify results are specific to second organization
- [ ] Switch back to first org
- [ ] Costs should differ if pricing differs

### Wastage Calculation

- [ ] Calculate BOM Cost
- [ ] Review bomItems breakdown
- [ ] Verify: `quantityWithWastage > quantity` (if wastage_percent > 0)
- [ ] Verify: `wastageAmount > 0` (if wastage applied)
- [ ] Formula check: `wastageAmount = baseCost × wastage_percent / 100`

### Sub-Product Recursion

- [ ] Create BOM with sub-product (nested assembly)
- [ ] Calculate Product Cost with that product
- [ ] Verify `bomItems` includes sub-product with:
    - `itemType`: "sub_product"
    - `subProductCost`: nested cost breakdown

### Unit Conversion

- [ ] Calculate material with quantity in different units
- [ ] Verify unit conversion happens automatically
- [ ] Check `quantityUnit` in response matches expected unit

### Historical Pricing

- [ ] Call Get Material Price History
- [ ] Verify prices vary over time
- [ ] Call Calculate Material Cost with specific `effective_date`
- [ ] Verify cost uses historical price for that date

---

## ✅ Error Scenario Testing

### Test 401 Unauthorized

- [ ] Remove `{{auth_token}}` from environment
- [ ] Try to call any Manufacturing endpoint
- [ ] ✅ Response: 401 Unauthorized

### Test 404 Not Found

- [ ] Set `{{material_id}}` to invalid UUID
- [ ] Call Calculate Material Cost
- [ ] ✅ Response: 404 Not Found

### Test 400 Bad Request (Validation)

- [ ] Set `quantity` to 0.00001 (below minimum 0.001)
- [ ] Call Calculate Material Cost
- [ ] ✅ Response: 422 Unprocessable Entity with validation error
- [ ] Error message: "Quantity must be greater than 0"

### Test Invalid Organization

- [ ] Set `organization_id` to invalid UUID
- [ ] Call Calculate Material Cost
- [ ] ✅ Response: 404 or validation error

---

## ✅ Performance Testing

### Response Times

- [ ] Material Cost calculation: < 100ms ✅
- [ ] BOM Cost calculation: < 200ms ✅
- [ ] Product Cost calculation: < 300ms ✅
- [ ] Price History: < 100ms ✅

### Load Testing (Optional)

- [ ] Use Postman Runner to call Material Cost 10 times
- [ ] Monitor average response time
- [ ] All responses should succeed (100% success rate)

---

## ✅ Integration Testing

### Complete Workflow Test

- [ ] Create organization (Organizations → Create)
- [ ] Create user (Users → Create)
- [ ] Assign user to org (Organization Users → Create)
- [ ] Calculate material cost (Manufacturing → Calculate Material Cost)
- [ ] Calculate BOM cost (Manufacturing → Calculate BOM Cost)
- [ ] Calculate product cost (Manufacturing → Calculate Product Cost)
- [ ] All endpoints succeed with proper responses

### Copy Quote Generation Scenario

- [ ] Calculate product cost
- [ ] Use `{{product_unit_cost}}` variable
- [ ] Add 30% markup: `unit_price = unit_cost × 1.30`
- [ ] Calculate quote: `total = unit_price × quantity`
- [ ] Store in collection notes for documentation

---

## ✅ Documentation & Support

### Documentation Review

- [ ] Read [POSTMAN_COLLECTION_SETUP_GUIDE.md](./POSTMAN_COLLECTION_SETUP_GUIDE.md)
- [ ] Read Manufacturing Usage Guide (.resources/USAGE_GUIDE.md)
- [ ] Review example payloads in request descriptions

### Backend Integration

- [ ] Verify all 5 routes in `routes/api_v1.php`
- [ ] Check all manufacturing controllers exist
- [ ] Services are injectable via DI container
- [ ] DTOs match request/response formats

---

## ✅ Final Verification

### Collection Ready for Distribution

- [ ] All 5 manufacturing endpoints working
- [ ] Variables auto-populate correctly
- [ ] Auth token persists across requests
- [ ] PreStep & AfterScripts execute without errors
- [ ] Error handling tested
- [ ] Performance acceptable

### Ready for Team

- [ ] Document base_url for team
- [ ] Share environment setup instructions
- [ ] Share master data requirements
- [ ] Share troubleshooting guide

---

## ✅ Sign-Off

**Collection Status**: ✅ READY FOR PRODUCTION

**Verified By**: [Your Name]  
**Date**: [Date]  
**Notes**:

---

## 🆘 Troubleshooting Quick Links

| Issue                | Solution                                            |
| -------------------- | --------------------------------------------------- |
| 401 Unauthorized     | Run **Authentication → Login** to get token         |
| 404 Not Found        | Verify resource ID exists and is set in variables   |
| 422 Validation Error | Check request payload matches documentation         |
| Empty Variables      | Ensure requests have valid response data to capture |
| Slow Responses       | Check backend server and database performance       |

---

**Postman Collection Setup Version**: 1.0  
**Last Updated**: March 31, 2026

For detailed API documentation, see [MANUFACTURING_COST_ENGINE.md](../backend/documentations/MANUFACTURING_COST_ENGINE.md)
