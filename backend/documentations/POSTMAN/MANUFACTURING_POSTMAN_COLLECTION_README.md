# Postman Collection - Manufacturing Cost Engine Integration

## 📦 What Was Generated

### New Manufacturing Folder
**Path**: `postman/collections/Dometrix ERP API/Manufacturing/`

Contains 5 fully-functional Postman request YAML files for all manufacturing endpoints:

```
Manufacturing/
├── .resources/
│   ├── definition.yaml (Folder metadata)
│   ├── USAGE_GUIDE.md (Comprehensive API guide)
│   └── [Example responses and payload templates]
├── SETUP_CHECKLIST.md (Step-by-step verification)
├── Calculate Material Cost.request.yaml
├── Get Material Price History.request.yaml
├── Calculate BOM Cost.request.yaml
├── Calculate Product Cost.request.yaml
└── Get Product Cost Summary.request.yaml
```

### Updated Collection Definition
**Path**: `postman/collections/Dometrix ERP API/.resources/definition.yaml`

Added 9 new collection-level variables for manufacturing:
- `material_id`
- `product_id`
- `bom_id`
- `material_unit_cost` (auto-populated)
- `bom_total_cost` (auto-populated)
- `bom_unit_cost` (auto-populated)
- `product_total_cost` (auto-populated)
- `product_unit_cost` (auto-populated)

### New Environment File
**Path**: `postman/environments/Local Development.environment.yaml`

Pre-configured environment with all collection variables for immediate testing:
- Base URL
- Auth token
- Organization, user, role IDs
- Manufacturing resource IDs
- Cost calculation outputs

### Documentation Files
- `POSTMAN_COLLECTION_SETUP_GUIDE.md` (Root level guide)
- `Manufacturing/SETUP_CHECKLIST.md` (Manufacturing specific)
- `Manufacturing/.resources/USAGE_GUIDE.md` (API usage reference)

---

## 🚀 Features Included

### 1. Complete Manufacturing Endpoints
✅ **Calculate Material Cost** (POST)
- Calculate single material cost with quantity
- Auto-captures `material_unit_cost`

✅ **Get Material Price History** (GET)
- Retrieve historical pricing data
- Supports date range filtering

✅ **Calculate BOM Cost** (POST)
- Multi-line BOM costing with breakdown
- Auto-captures `bom_total_cost`, `bom_unit_cost`
- Includes sub-assemblies support

✅ **Calculate Product Cost** (POST)
- Product cost with active BOM
- Recursive sub-product costing
- Auto-captures `product_total_cost`, `product_unit_cost`

✅ **Get Product Cost Summary** (GET)
- Historical cost trends
- Effective date support

### 2. Automatic Variable Capture
Each POST endpoint includes an afterResponse script that:
- Extracts cost values from response
- Auto-populates collection variables
- Enables chaining requests for complex workflows

Example:
```javascript
var jsonData = pm.response.json();
if (jsonData.data && jsonData.data.unitCost) {
    pm.collectionVariables.set("material_unit_cost", jsonData.data.unitCost);
}
```

### 3. Security & Authentication
- All endpoints protected with `auth:sanctum` 
- Bearer token automatically included in headers
- Token captured from Login endpoint
- Token persists across collection execution

### 4. Variable Management
**Collection-Level Variables**:
```
{{base_url}}            - API server URL
{{auth_token}}          - Authentication bearer token
{{organization_id}}     - Organization UUID
{{material_id}}         - Material UUID
{{product_id}}          - Product UUID
{{bom_id}}              - BOM UUID
[captured costs]        - Auto-populated from responses
```

### 5. Ready-to-Use Request Payloads
Each request includes realistic example payloads based on actual validation rules:

```json
{
  "organization_id": "{{organization_id}}",
  "material_id": "{{material_id}}",
  "quantity": 10.5,
  "effective_date": "2026-03-31",
  "costing_method": "weighted_average"
}
```

### 6. Comprehensive Documentation
- 📖 Setup guide with step-by-step instructions
- 📖 API usage guide with request/response examples
- 📖 Testing checklist for verification
- 📖 Troubleshooting guide for common issues

---

## 🎯 Quick Start (5 Minutes)

### 1. Import Collection & Environment
```bash
# In Postman:
1. File → Import → Select "postman/collections/Dometrix ERP API" folder
2. File → Import → Select "postman/environments/Local Development.environment.yaml"
```

### 2. Set Environment Variables
```
Select "Local Development" environment (top right)
Set base_url: http://localhost:8000 (or your API server)
Keep other variables empty (will auto-populate)
```

### 3. Authenticate
```
Go to Authentication folder → Run "Login" endpoint
Token auto-captured in {{auth_token}} ✅
```

### 4. Set Resource IDs
```
In environment variables:
- {{organization_id}}: [your organization UUID]
- {{material_id}}: [your material UUID]
- {{product_id}}: [your product UUID]
- {{bom_id}}: [your BOM UUID]
```

### 5. Test Manufacturing Endpoints
```
Manufacturing folder:
1. Calculate Material Cost → Response populates {{material_unit_cost}}
2. Calculate BOM Cost → Response populates {{bom_total_cost}}
3. Calculate Product Cost → Response populates {{product_unit_cost}}
4. Get Product Cost Summary → View historical trends
```

---

## 📋 What Each Request Includes

### HTTP Details
- ✅ Correct HTTP method (POST, GET)
- ✅ Full URL with `{{base_url}}`
- ✅ Request order for logical execution
- ✅ Content-Type headers
- ✅ Accept headers

### Request Body (POST requests)
- ✅ All required parameters
- ✅ Realistic example values
- ✅ Uses collection variables where applicable
- ✅ Optional parameters demonstrated

### Authorization
- ✅ Bearer token in Authorization header
- ✅ Implicit via collection-level auth setup
- ✅ Token from {{auth_token}} variable

### Response Handling
- ✅ AfterResponse scripts extract values
- ✅ Cost values auto-populate variables
- ✅ Success validation on response code
- ✅ Ready for downstream requests

---

## 🔄 Integration Patterns

### Pattern 1: Simple Lookup
```
1. Set material_id variable
2. Call "Get Material Price History"
3. View price trends
```

### Pattern 2: Material Costing
```
1. Set organization_id, material_id, quantity
2. Call "Calculate Material Cost"
3. {{material_unit_cost}} populated
4. Use in quote/pricing calculations
```

### Pattern 3: BOM Breakdown
```
1. Set organization_id, bom_id
2. Call "Calculate BOM Cost"
3. {{bom_total_cost}}, {{bom_unit_cost}} populated
4. Review bomItems for line-item analysis
```

### Pattern 4: Product Costing Workflow
```
1. Set organization_id, product_id
2. Call "Calculate Product Cost"
3. {{product_unit_cost}} populated
4. Use for margin calculation: margin = price - cost
5. Use for quote total: total = unit_price × quantity
```

### Pattern 5: Historical Analysis
```
1. Set product_id, effective_date
2. Call "Get Product Cost Summary"
3. Review cost_history array
4. Analyze price trends over time
```

---

## 📊 Payload Examples

### Material Cost Request
```json
{
  "organization_id": "550e8400-e29b-41d4-a716-446655440000",
  "material_id": "550e8400-e29b-41d4-a716-446655440001",
  "quantity": 10.5,
  "effective_date": "2026-03-31",
  "costing_method": "weighted_average"
}
```

### Material Cost Response
```json
{
  "success": true,
  "data": {
    "type": "material",
    "itemId": "550e8400-e29b-41d4-a716-446655440001",
    "itemName": "Steel Sheet Grade A",
    "quantity": 10.5,
    "quantityUnit": "kg",
    "costs": {
      "baseCost": 525.00,
      "wastageAmount": 0.00,
      "totalCost": 525.00
    },
    "unitCost": 50.00,
    "bomItems": null,
    "metadata": {}
  },
  "message": "Material cost calculated successfully"
}
```

### BOM Cost Request
```json
{
  "organization_id": "550e8400-e29b-41d4-a716-446655440000",
  "bom_id": "550e8400-e29b-41d4-a716-446655440003",
  "quantity": 1,
  "effective_date": "2026-03-31",
  "costing_method": "weighted_average",
  "include_product_cost": false
}
```

### BOM Cost Response (Simplified)
```json
{
  "success": true,
  "data": {
    "type": "bom",
    "itemId": "550e8400-e29b-41d4-a716-446655440003",
    "itemName": "Assembly A",
    "quantity": 1,
    "quantityUnit": "pcs",
    "costs": {
      "baseCost": 1250.00,
      "wastageAmount": 25.00,
      "totalCost": 1275.00
    },
    "unitCost": 1275.00,
    "bomItems": [
      {
        "lineNo": 1,
        "itemType": "material",
        "itemName": "Steel Sheet",
        "quantity": 5,
        "wastagePercent": 2.0,
        "costs": {
          "baseCost": 250.00,
          "wastageAmount": 5.00,
          "totalCost": 255.00
        }
      }
    ]
  }
}
```

---

## 🛠️ Configuration & Customization

### Change Base URL
1. Select **Local Development** environment (top right)
2. Click the eye icon to show environment variables
3. Change `base_url` value to your API server
4. All requests will use the new URL

### Add Custom Headers
1. Edit collection → Variables tab
2. Add `custom_header: value`
3. Reference in requests: `{{custom_header}}`

### Add Pre-request Script
1. Open request → Pre-request Script tab
2. Add validation or setup code
3. Runs before request is sent

### Add Response Tests
1. Open request → Tests tab
2. Add assertions:
   ```javascript
   pm.test("Response is successful", function() {
       pm.expect(pm.response.code).to.equal(200);
   });
   ```

---

## ✅ Verification Checklist

Before using in production:
- [ ] Collection imported successfully
- [ ] Environment imported successfully
- [ ] Local Development environment selected
- [ ] base_url configured to your server
- [ ] Can run Login endpoint successfully
- [ ] auth_token auto-populated after login
- [ ] Can call Manufacturing endpoints
- [ ] Responses show expected data structure
- [ ] Cost variables auto-populate correctly
- [ ] Error scenarios handled properly (401, 404, 422)

---

## 🐛 Troubleshooting

### Issue: "Collection import failed"
**Solution**: Ensure folder structure is intact. Import from `postman/collections/Dometrix ERP API` folder.

### Issue: "401 Unauthorized"
**Solution**: Run Authentication → Login endpoint first to get token.

### Issue: "{{variable}} shows as empty"
**Solution**: Ensure you've called a request that populates it, or set it manually in environment.

### Issue: "404 Not Found"
**Solution**: Verify material_id, product_id, bom_id exist in database.

### Issue: "Payload validation error"
**Solution**: Check request body matches documentation (types, formats, required fields).

---

## 📚 Documentation

| Document | Purpose |
|----------|---------|
| [POSTMAN_COLLECTION_SETUP_GUIDE.md](./POSTMAN_COLLECTION_SETUP_GUIDE.md) | Complete setup & usage guide |
| [Manufacturing/SETUP_CHECKLIST.md](./collections/Dometrix%20ERP%20API/Manufacturing/SETUP_CHECKLIST.md) | Step-by-step verification |
| [Manufacturing/USAGE_GUIDE.md](./collections/Dometrix%20ERP%20API/Manufacturing/.resources/USAGE_GUIDE.md) | API endpoint reference |
| [MANUFACTURING_COST_ENGINE.md](../backend/documentations/MANUFACTURING_COST_ENGINE.md) | Backend API docs |

---

## 📞 Support

For issues:
1. Check [POSTMAN_COLLECTION_SETUP_GUIDE.md](./POSTMAN_COLLECTION_SETUP_GUIDE.md)
2. Review [Manufacturing/USAGE_GUIDE.md](./collections/Dometrix%20ERP%20API/Manufacturing/.resources/USAGE_GUIDE.md)
3. Check backend logs for detailed errors
4. Verify master data exists in database

---

## 🎉 You're Ready!

The Postman collection is fully configured and ready to test the Manufacturing Cost Engine APIs. 

**Start with**: 1. Import collection & environment
2. Configure base_url
3. Run Login endpoint
4. Test Manufacturing → Calculate Material Cost

Happy testing! 🚀

---

**Generated**: March 31, 2026  
**Version**: 1.0  
**Collection Format**: Postman v11 (YAML)
