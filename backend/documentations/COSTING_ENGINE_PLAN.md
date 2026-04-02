# Dometrix ERP: Manufacturing Costing Engine - Architecture Review & Implementation Plan

**Date:** March 29, 2026  
**Scope:** MVP Manufacturing Costing Engine (Bakery Use Case)  
**Objective:** Critical architecture validation → Correct schema → Actionable build plan

---

## PHASE 1: EXISTING STRUCTURE ANALYSIS

### 1.1 Critical Questions to Answer

**Schema Design:**

- [✅] Is `organization_id` enforced on EVERY table via Foreign Key?
- [✅] Are soft deletes needed? (inventory audit trail)
- [✅] How are decimal/precision fields handled? (cost per unit, quantities)
- [✅] Are there composite unique constraints where needed? (e.g., material + organization)

**Tenant Isolation:**

- [ ] Can a user from Org A see data from Org B if they craft a query?
- [✅] Are row-level policies enforced in queries?
- [✅] Is organization context required on every model?

**Multi-tenancy Specific Risks:**

- [✅] Seeders and migrations - will they work correctly for multiple tenants?
- [] Audit trail - can you trace who changed a BOM in which organization?

**Core Flow Validation:**

- [ ] Material purchase recorded → where? (no purchase table visible)
- [ ] Stock updated → stock_movements table exists?
- [✅] BOM → production order → production line items → can they be linked?
- [ ] Costing calculation → weighted average formula - where lives?

**Data Integrity:**

- [] Bill of Materials: circular dependency prevention?
- [✅] Material prices over time (historical tracking)?
- [✅] Production: can you handle partial completions, wastage, yield?

---

### 1.2 Current Structure Observations

From workspace structure, I see:

**Created (visible in migrations):**

- `currencies` - ✓ Multi-tenant ready (org_id required?)
- `materials` - ? (schema unknown, needs review)
- `material_prices` - ? (time-series handling?)
- `products` - ? (is this finished goods or all items?)
- `boms` - ? (recursive structure, constraints?)

**Missing/Unclear:**

- Stock/Inventory table (quantities on hand)
- Stock movements (audit trail)
- Production planning table
- Production execution / line items
- Purchase orders (where do incoming materials come from?)
- Costing tables (unit costs, WIP, COGS)

---

## PHASE 2: DESIGN FLAW DETECTION

### 2.1 Common Pitfalls in Manufacturing Systems (Red Flags to Check)

**Inventory Model Errors:**

- [ ] Storing quantity only (no movements history) → can't audit stock changes
- [ ] No separation of: received stock vs. quality-inspected vs. reserved vs. scrap
- [ ] Missing lot/batch tracking (bakery needs batch dates, expiry)
- [ ] No multi-location support (even small bakery has: raw storage, staging, production area)

**BOM Design Issues:**

- [ ] Single-level BOM only (can't nest BOMs for sub-assemblies)
- [ ] No version/effectivity dates (BOM changes shouldn't affect old production)
- [ ] No yield/scrap factors per line (bakery flour waste, water evaporation)
- [ ] Can't reference sub-assemblies in BOMs (component is a material, not a finished good relationship)

**Costing Logic Flaws:**

- [ ] No method to handle material price changes mid-production
- [ ] Labor/overhead not modeled (even MVP needs placeholder)
- [ ] No WIP (Work in Progress) staging (cost accumulation during production)
- [ ] No COGS (Cost of Goods Sold) record for finished goods

**Production Flow Gaps:**

- [ ] Can't link purchase → receipt → stock → production order → finished good
- [ ] No traceability (which batch used which raw material batch?)
- [ ] Can't track partial completions (60% of batch done, hold for quality)

**Multi-Tenant Data Leaks:**

- [ ] Models missing `->where('organization_id', auth()->user()->org_id)` in queries
- [ ] No global scope enforcement (Laravel should use anonymous global scopes)
- [ ] Migrations not tenant-aware (shared IDaaS patterns)

---

## PHASE 3: REAL-WORLD BAKERY SCENARIO TEST

### Scenario: Simple Croissant Production

**The Flow:**

1. Purchase 10kg flour from supplier → receive stock
2. Create BOM: 1 croissant = 0.2kg flour + 0.05kg butter + ... (with 2% loss)
3. Plan production: make 40 croissants
4. Execute: 1st shift produces 35, 2nd shift produces 5, total 40
5. Calculate cost: flour $2/kg avg, butter $5/kg → cost per croissant = ?

**Critical Questions:**

- Where is "10kg flour" stored? (inventory table)
- When 8kg is allocated to the croissant order, is it reserved or consumed? (stock movements)
- If flour price was $1.8/kg last month, $2.2/kg this month, which is used? (weighted average costing)
- Can you trace: "batch #42 used flour from purchase order #15"? (traceability)
- What's the WIP cost at 50% complete? (production staging)
- Final COGS for batch #42? (cost ledger)

**If your schema can't answer all these → it will fail in production.**

---

## PHASE 4: REQUIRED FIXES (TO BE VALIDATED)

### 4.1 Suspected Missing/Broken Elements

**Likely Issues:**

1. **No Stock/Inventory Table**
    - Materials table exists, but no "quantity on hand"
    - Can't track what's in stock vs. what was purchased

2. **No Stock Movements**
    - Can't audit: received 10kg → allocated 8kg to order → used 7.9kg → loss 0.1kg
    - Forensics will be impossible

3. **No Production Order**
    - How do you link BOM to actual production run?
    - Can't track which production created which finished goods batch

4. **Material Pricing Over Time**
    - If `material_prices` is just current price, historical cost is lost
    - Weighted average costing breaks

5. **Missing Yield/Loss Factors**
    - BOM probably assumes 100% yield
    - Real: flour hydration, bake loss, trim waste → needs per-line-item factors

6. **No WIP / Production Staging**
    - Half-finished goods have cost, but where's it stored?
    - COGS calculation will be impossible

7. **Costing Method Not Defined**
    - Weighted average needs a clear table/formula
    - Labor/overhead placeholder missing

---

## PHASE 5: ACTION PLAN (STEP-BY-STEP)

### Step 1: Audit Existing Code

**Action:** Provide migrations and models  
**Deliverable:** List of actual gaps & design flaws  
**Time:** ~30 min analysis

### Step 2: Validate Tenant Isolation

**Action:** Review organization_id usage, query patterns  
**Deliverable:** Data leak risks identified & fixed  
**Time:** ~15 min

### Step 3: Refactor/Create Core Tables

**Action:** Fix schema, create missing tables  
**Deliverable:** Correct migrations for: inventory, stock_movements, production, costing  
**Time:** 1-2 hours

### Step 4: Define Models & Relationships

**Action:** Create models with correct relationships, scopes  
**Deliverable:** Working relationships, eager-load optimizations  
**Time:** 45 min

### Step 5: Implement Stock Management Service

**Action:** Code stock receipt, allocation, consumption logic  
**Deliverable:** Testable service with audit trail  
**Time:** 1.5 hours

### Step 6: Implement Costing Engine

**Action:** Weighted average cost calculation, WIP staging  
**Deliverable:** Cost per unit for finished goods  
**Time:** 1.5 hours

### Step 7: Production Flow & Testing

**Action:** Link BOM → Production Order → Execution → COGS  
**Deliverable:** End-to-end flow with test cases  
**Time:** 2 hours

---

## PHASE 6: MVP SCOPE DEFINITION

### INCLUDE ✓

- [x] Materials (with categories: raw, packaging, finished)
- [x] Material Purchases & Stock Receipt
- [x] Stock Inventory & Movements (audit trail)
- [x] Bills of Materials (single & multi-level)
- [x] Production Orders & Execution
- [x] Finished Goods Inventory
- [x] Weighted Average Costing
- [x] Multi-tenant isolation & data security
- [x] Basic audit trail for compliance

### EXCLUDE ✗

- [ ] Multi-location/warehouse (single location MVP)
- [ ] Actual accounting journal entries (no GL integration)
- [ ] Advanced reporting (just APIs for now)
- [ ] Batch/Lot tracking (v2)
- [ ] Serial numbers (v2)
- [ ] Quality management / inspection (v2)
- [ ] Supplier management (purchase linked to contact only)
- [ ] Demand forecasting (v2)
- [ ] MRP planning (v2)

---

## PHASE 7: NEXT STEPS

**I need you to provide:**

1. **Migrations** from `database/migrations/` - the actual material, BOM, pricing files
2. **Models** - Material.php, Product.php, any Domain classes
3. **Current schema for multi-tenancy** - how org_id is enforced
4. **Any domain logic** - costing formula pseudocode, inventory rules

**Then I will:**

- [ ] Identify ALL design flaws
- [ ] Show the croissant scenario and whether it works
- [ ] Deliver corrected migrations
- [ ] Give exact implementation sequence
- [ ] Provide code patterns (scopes, relationships)

---

## ASSUMPTIONS (Challenge Me If Wrong)

1. **You want semi-manual multi-tenancy** (organization data separation, not separate databases)
2. **Weighted average costing only** (no FIFO/LIFO, standard costing)
3. **Single legal entity** (one currency minimum, multi-currency later)
4. **Small-batch manufacturing** (not high-volume continuous)
5. **Bakery MVP** (10-100 SKUs, max operations: receive, produce, sell)

**If any of these are wrong, tell me now.**

---

## Success Criteria

After this review, you should be able to:

✓ Store and track material inventory with full audit trail  
✓ Create BOMs with multiple levels and yield factors  
✓ Execute production orders and record actual consumption  
✓ Calculate cost per finished good using weighted average  
✓ Identify ANY data isolation issues before go-live  
✓ Build the next phase (sales, reporting) without schema rewrites

---

**Ready to proceed?**  
→ Share your migration files and I'll do the detailed analysis.
