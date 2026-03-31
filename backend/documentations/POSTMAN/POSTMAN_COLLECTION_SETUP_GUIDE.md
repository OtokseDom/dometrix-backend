# Dometrix ERP API - Postman Collection Setup Guide

## 📋 Table of Contents
1. [Overview](#overview)
2. [Installation & Setup](#installation--setup)
3. [Collection Structure](#collection-structure)
4. [Authentication Workflow](#authentication-workflow)
5. [Manufacturing Cost Engine](#manufacturing-cost-engine)
6. [Testing Workflows](#testing-workflows)
7. [Troubleshooting](#troubleshooting)

---

## Overview

The Dometrix ERP API Postman collection provides comprehensive coverage of all backend endpoints including:
- **Authentication** (Login, Register, Logout, Password Reset)
- **Organization Management** (CRUD operations)
- **User Management** (CRUD operations)
- **Role Management** (CRUD operations)
- **Organization-User Assignment** (CRUD operations)
- **Units** (CRUD operations)
- **Currencies** (CRUD operations)
- **Manufacturing Cost Engine** (5 costing endpoints) ✨ NEW

---

## Installation & Setup

### Step 1: Import the Collection

1. Open Postman
2. Click **Import** (top left)
3. Select **Folder** tab
4. Navigate to: `postman/collections/Dometrix ERP API`
5. Click **Import**

### Step 2: Import the Environment

1. Click the **Environments** tab (left sidebar)
2. Click **Import**
3. Select the file: `postman/environments/Local Development.environment.yaml`
4. Click **Open**

### Step 3: Configure Environment Variables

1. Select **Local Development** environment (top right)
2. Click the **eye icon** to open variable editor
3. Set the following initial values:
   - `base_url`: `http://localhost:8000` (or your API server URL)
   - Keep other values empty - they'll be populated automatically

### Step 4: Start Testing!

1. Navigate to **Authentication** folder
2. Run **Login** endpoint with test credentials
3. The auth token will auto-populate in `{{auth_token}}`

---

## Collection Structure

```
Dometrix ERP API/
├── .resources/
│   └── definition.yaml (Collection-level variables & auth)
├── Authentication/
│   ├── .resources/
│   │   └── definition.yaml
│   ├── Register.request.yaml
│   ├── Login.request.yaml
│   ├── Reset Password.request.yaml
│   └── Logout.request.yaml
├── Organizations/
│   ├── .resources/definition.yaml
│   ├── List Organizations.request.yaml
│   ├── Create Organization.request.yaml
│   ├── Get Organization.request.yaml
│   ├── Update Organization.request.yaml
│   └── Delete Organization.request.yaml
├── Users/
│   ├── .resources/definition.yaml
│   └── [5 CRUD request files]
├── Roles/
│   ├── .resources/definition.yaml
│   └── [5 CRUD request files]
├── Organization Users/
│   ├── .resources/definition.yaml
│   └── [5 CRUD request files]
├── Units/
│   ├── .resources/definition.yaml
│   └── [5 CRUD request files]
├── Currencies/
│   ├── .resources/definition.yaml
│   └── [5 CRUD request files]
└── Manufacturing/ ✨ NEW
    ├── .resources/
    │   ├── definition.yaml
    │   └── USAGE_GUIDE.md
    ├── Calculate Material Cost.request.yaml
    ├── Get Material Price History.request.yaml
    ├── Calculate BOM Cost.request.yaml
    ├── Calculate Product Cost.request.yaml
    └── Get Product Cost Summary.request.yaml
```

---

## Authentication Workflow

### Before Any API Call

1. **Run Login Request**
   - Endpoint: `POST /api/v1/auth/login`
   - Payload:
     ```json
     {
       "email": "admin@demo.com",
       "password": "admin123"
     }
     ```
   - ✅ Auth token automatically stored in `{{auth_token}}`

2. **All Subsequent Requests**
   - Use the same session with captured token
   - Token remains valid per Postman collection runtime
   - For new session: Re-run Login

---

## Manufacturing Cost Engine

The Manufacturing module has 5 endpoints for calculating product costs:

### Quick Start

1. **Setup Prerequisites**
   - Run **Login** to get auth token
   - Create/note an Organization ID
   - Create/note a Material ID, Product ID, BOM ID

2. **Test Material Cost**
   - Open **Manufacturing → Calculate Material Cost**
   - Set `{{organization_id}}` and `{{material_id}}` variables
   - Click **Send**
   - Unit cost will auto-populate in `{{material_unit_cost}}`

3. **Test BOM Cost**
   - Open **Manufacturing → Calculate BOM Cost**
   - Set `{{organization_id}}` and `{{bom_id}}` variables
   - Click **Send**
   - Totals will auto-populate in `{{bom_total_cost}}` and `{{bom_unit_cost}}`

4. **Test Product Cost**
   - Open **Manufacturing → Calculate Product Cost**
   - Set `{{organization_id}}` and `{{product_id}}` variables
   - Click **Send**
   - Totals will auto-populate in `{{product_total_cost}}` and `{{product_unit_cost}}`

### Endpoints Reference

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/v1/manufacturing/material-cost` | POST | Calculate single material cost with quantity |
| `/api/v1/manufacturing/materials/{id}/price-history` | GET | Get historical pricing for a material |
| `/api/v1/manufacturing/bom-cost` | POST | Calculate BOM total cost with line-item breakdown |
| `/api/v1/manufacturing/product-cost` | POST | Calculate product cost including BOMs |
| `/api/v1/manufacturing/products/{id}/cost-summary` | GET | Get product cost summary |

### Response Payloads

All manufacturing endpoints return:
```json
{
  "success": true,
  "data": {
    "type": "material|bom|product",
    "itemId": "uuid",
    "itemName": "name",
    "itemCode": "code",
    "quantity": 10.5,
    "quantityUnit": "kg",
    "costs": {
      "baseCost": 525.00,
      "wastageAmount": 10.50,
      "totalCost": 535.50
    },
    "unitCost": 51.00,
    "bomItems": [...],
    "metadata": {}
  },
  "message": "Operation successful"
}
```

### Key Features

- ✅ **Material Costing**: Single material cost calculation with current/historical pricing
- ✅ **BOM Costing**: Multi-line BOM with wastage calculation and item breakdown
- ✅ **Product Costing**: Product cost including all BOMs and sub-assemblies
- ✅ **Wastage Handling**: Automatic wastage calculation at item level
- ✅ **Unit Conversion**: Automatic metric unit conversions (g↔kg, ml↔l, pcs↔dozen)
- ✅ **Price History**: Historical price lookup by effective date
- ✅ **Recursive BOMs**: Sub-product BOM support (nested assemblies)
- ✅ **Multi-Tenant**: All requests filtered by organization_id
- ✅ **Scripts**: Auto-population of cost variables from responses

### Example: Complete Quote Generation Workflow

```
1. Create Organization
   ├── GET {{organization_id}}
   
2. Create Material
   ├── Defined in ERP database
   ├── Has time-series pricing
   
3. Create Product with BOM
   ├── Product contains BOM (bill of materials)
   ├── BOM has line items (materials or sub-products)
   
4. Calculate Product Cost
   POST /api/v1/manufacturing/product-cost
   ├── Payload: org_id, product_id, quantity
   ├── Response includes: unit cost, total cost, BOM breakdown
   ├── {{product_unit_cost}} auto-populated
   
5. Generate Quote
   ├── Use {{product_unit_cost}}
   ├── Add markup: unit_price = unit_cost × (1 + margin%)
   ├── Calculate total: quantity × unit_price
```

---

## Testing Workflows

### Workflow 1: Complete Setup & Organization Management

```
1. Authentication → Register
   └── Create new user account
   
2. Authentication → Login
   └── Get auth token (stored in {{auth_token}})
   
3. Organizations → Create Organization
   └── Save org ID to {{organization_id}}
   
4. Organization Users → Create Organization User
   └── Link user to organization
   
5. Users → List Users
   └── Verify users in system
```

### Workflow 2: Master Data Setup

```
1. Units → List Units
   └── View available units (kg, pcs, l, etc.)
   
2. Currencies → Create Currency
   └── Add new currency if needed
   
3. Create Materials (via database or API if available)
   └── Before calculating costs, ensure materials exist
   
4. Create Products (via database or API if available)
   └── Before calculating costs, ensure products exist
   
5. Create BOMs (via database or API if available)
   └── Link materials/sub-products to products
```

### Workflow 3: Cost Calculations

```
1. Set material_id, product_id, bom_id in environment
   
2. Manufacturing → Calculate Material Cost
   └── Get unit cost for a material
   
3. Manufacturing → Get Material Price History
   └── View price trends (optional)
   
4. Manufacturing → Calculate BOM Cost
   └── Get cost breakdown with wastage
   
5. Manufacturing → Calculate Product Cost
   └── Get final product cost including assemblies
   
6. Manufacturing → Get Product Cost Summary
   └── View historical cost trend
```

### Workflow 4: Role-Based Access Testing

```
1. Create Role with specific permissions
   
2. Create User with that Role
   
3. Assign User to Organization
   
4. Login as that User
   
5. Test Manufacturing endpoints
   └── Verify permissions enforced
```

---

## Collection Variables Reference

### Core Variables

| Variable | Purpose | Set By |
|----------|---------|--------|
| `{{base_url}}` | API server URL | Manual (in environment) |
| `{{auth_token}}` | Bearer authentication token | Auto (after Login) |
| `{{organization_id}}` | Current organization UUID | Manual (from Create Org response) |

### Manufacturing Variables

| Variable | Set By | Used In |
|----------|--------|---------|
| `{{material_id}}` | Manual | Material costing endpoints |
| `{{product_id}}` | Manual | Product costing endpoints |
| `{{bom_id}}` | Manual | BOM costing endpoints |
| `{{material_unit_cost}}` | Auto (Calculate Material Cost) | Downstream calculations |
| `{{bom_total_cost}}` | Auto (Calculate BOM Cost) | Report generation |
| `{{product_total_cost}}` | Auto (Calculate Product Cost) | Quote generation |

### Resource IDs (All Set Manually)

| Variable | Example |
|----------|---------|
| `{{user_id}}` | From Create User response |
| `{{role_id}}` | From Create Role response |
| `{{unit_id}}` | From Units list |
| `{{currency_id}}` | From Currencies list |
| `{{organization_user_id}}` | From Create Org User response |

---

## Advanced Usage

### Setting Custom Headers

Some endpoints may require custom headers. To add globally:

1. Collection → Edit Collection → Variables tab
2. Add `Custom-Header: value` entries
3. Reference in requests: `{{Custom-Header}}`

### Pre-request Scripts

To validate data before sending:

1. Open request → Pre-request Script tab
2. Add validation logic:
   ```javascript
   if (!pm.collectionVariables.get("organization_id")) {
       throw new Error("organization_id not set");
   }
   ```

### Testing Response Body

To validate response structure:

1. Open request → Tests tab
2. Add assertions:
   ```javascript
   pm.test("Response has success flag", function() {
       pm.expect(pm.response.json().success).to.be.true;
   });
   ```

### Running Collections as Tests

1. Click **Runner** button (left sidebar)
2. Select collection: **Dometrix ERP API**
3. Select environment: **Local Development**
4. Click **Run**
5. Tests execute sequentially, capturing variables between requests

---

## Troubleshooting

### Issue: "401 Unauthorized" Error

**Cause**: Missing or expired auth token

**Solution**:
1. Run **Authentication → Login**
2. Verify `{{auth_token}}` is populated
3. Retry the failing request

### Issue: "404 Not Found" on GET {id}

**Cause**: Resource ID (organization_id, material_id, etc.) is invalid or empty

**Solution**:
1. Check environment variables → ensure IDs are set
2. Verify IDs exist in database
3. Run LIST endpoint first to get valid IDs
4. Copy ID to environment variables
5. Retry request

### Issue: "422 Unprocessable Entity" (Validation Error)

**Cause**: Request payload validation failed

**Solution**:
1. Check error message in response body
2. Review request payload against documentation
3. Verify all required fields are present
4. Check field types (string vs number vs uuid)
5. Retry with corrected payload

### Issue: POST Request Returns 500 Error

**Cause**: Server error or missing database records

**Solution**:
1. Check backend logs for detailed error
2. Ensure all referenced resources exist (organization, material, etc.)
3. Verify database migrations are run
4. Check for NULL constraint violations
5. Retry request

### Issue: Variables Not Auto-Populating

**Cause**: Script not executing or response format changed

**Solution**:
1. Open request → Scripts tab
2. Verify afterResponse script is present
3. Check that response is JSON (Content-Type: application/json)
4. Add console output to debug:
   ```javascript
   var jsonData = pm.response.json();
   console.log("Response:", jsonData);
   ```
5. Copy data manually if script fails

---

## Best Practices

### 1. Organization Management
- Create one organization for testing
- Reuse the same organization_id across requests
- Don't create multiple organizations unless testing org isolation

### 2. Authentication
- Login once at start of session
- Reuse token across all requests
- Set auth_token in collection-level variables for simplicity

### 3. Testing Manufacturing Costs
- Ensure master data exists (materials, products, BOMs)
- Test with realistic quantities
- Validate cost calculations match expectations
- Check BOM breakdown for accuracy

### 4. Variable Management
- Store IDs in environment after creation
- Don't hardcode UUIDs in requests
- Use descriptive variable names
- Document custom variables in environment

### 5. Collection Organization
- Keep requests grouped logically by domain
- Name requests descriptively (verb + resource)
- Use consistent ordering (List → Create → Get → Update → Delete)
- Add descriptions to requests for clarity

---

## Quick Links

- 📖 [Manufacturing Cost Engine Documentation](../../documentations/MANUFACTURING_COST_ENGINE.md)
- 📖 [Manufacturing Quick Start](../../documentations/MANUFACTURING_COST_ENGINE_QUICK_START.md)
- 📖 [Manufacturing Examples](../../documentations/MANUFACTURING_COST_ENGINE_EXAMPLES.md)
- 📖 [Manufacturing API Reference](../../documentations/MANUFACTURING_COST_ENGINE.md)
- 🛠️ [Manufacturing Usage Guide](./Dometrix%20ERP%20API/Manufacturing/.resources/USAGE_GUIDE.md)

---

## Support & Feedback

For issues or questions:
1. Check the [Manufacturing Usage Guide](./Dometrix%20ERP%20API/Manufacturing/.resources/USAGE_GUIDE.md)
2. Review [API Documentation](../../documentations/MANUFACTURING_COST_ENGINE.md)
3. Check backend logs for detailed errors
4. Verify all prerequisites are in place

---

**Last Updated**: March 31, 2026  
**Collection Version**: 1.0
