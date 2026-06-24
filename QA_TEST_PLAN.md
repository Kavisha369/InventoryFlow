# QA Test Plan & Manual Test Case Suite — InventoryFlow

## 1. Introduction & Objectives
This document serves as the official Quality Assurance (QA) Test Plan for **InventoryFlow**, a multi-tenant Inventory & Supplier Management System. 

The primary objective is to verify that all functional, security, and interface components perform according to specifications. The test suite focuses on validating core multi-tenant data isolation, role-based access control (RBAC), automated procurement triggers, and storefront search/filter catalog showcase responsiveness.

---

## 2. Scope of Testing

### 2.1 In Scope
* **Authentication & Access Control**: Login/logout states, route guarding, session timeouts, and 403 authorization boundary checks.
* **Multi-Tenant Isolation**: Ensuring Tenant A users cannot see, edit, delete, or fetch Tenant B records.
* **Product Catalog CRUD**: Adding, updating, and soft-deleting products.
* **Procurement Automations**: Reorder level threshold checks and automated draft Purchase Order (PO) creation.
* **Inventory Updates**: Inline stock count adjustments, input validation bounds, and stock transaction history logging.
* **Public Customer Showcase**: Static mock fallback detection, search bar debouncing, category tabs filtering, details quick view modal pop-ups, and sorting filters.

### 2.2 Out of Scope
* Actual external email sending (SMTP integration).
* Production database scaling load tests (above 10,000 concurrent writes).
* Real payment gateways and supplier invoice banking.

---

## 3. Testing Methodologies & Techniques

To ensure efficient coverage, the following test design techniques are applied:
* **Equivalence Partitioning (EP)**: Inputs (like pricing or quantities) are grouped into valid and invalid partitions.
* **Boundary Value Analysis (BVA)**: Focuses on values at the edge of input ranges (e.g., stock levels exactly at, above, or below the reorder threshold).
* **Role-Based Security Matrix**: Grid mapping access allowances for Admins, Staff, and Public Visitors.

### Access Control Matrix
| Feature / Action | Admin | Staff | Public Visitor |
| :--- | :---: | :---: | :---: |
| Access Dashboard | ✅ Yes | ✅ Yes | ❌ No |
| Add/Edit/Delete Suppliers | ✅ Yes | ❌ No | ❌ No |
| Add/Edit/Delete Products | ✅ Yes | ❌ No | ❌ No |
| Adjust Stock Levels | ✅ Yes | ✅ Yes | ❌ No |
| Change PO Status | ✅ Yes | ❌ No | ❌ No |
| View Public Catalog | ✅ Yes | ✅ Yes | ✅ Yes |
| Fetch raw data via `api/products.php` | ✅ Yes | ✅ Yes | ❌ No (requires public key) |

---

## 4. Test Case Suite

### 4.1 Authentication & Security (TC-AUTH)

| Test ID | Description | Pre-conditions | Test Steps | Expected Result | Severity |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-AUTH-001** | Valid Admin Login | Application is installed. Database is seeded. | 1. Go to `http://localhost/Collaborative_project/login.php`<br>2. Enter username `admin1`<br>3. Enter password `Admin@123`<br>4. Click "Sign In" | Redirects to Dashboard. Displays Tenant 1 company name. Sidebar shows all Admin items. | **Critical** |
| **TC-AUTH-002** | Invalid Credentials | Same as above. | 1. Go to Login page.<br>2. Enter username `admin1`<br>3. Enter password `WrongPassword`<br>4. Click "Sign In" | Fails to log in. Stays on login page. Displays alert: "Invalid username or password." | **Critical** |
| **TC-AUTH-003** | Unauthorized Admin Route Block (RBAC) | Logged in as Staff (`staff1` / `Staff@123`). | 1. Manually enter URL in address bar: `http://localhost/Collaborative_project/views/suppliers.php?action=new` | Access is blocked. Returns HTTP 403 Forbidden. Displays custom permission error message. | **Critical** |
| **TC-AUTH-004** | API Endpoint Guard check | No active session (Logged out). | 1. Open new private tab.<br>2. Navigate directly to `http://localhost/Collaborative_project/api/products.php` (without public key) | Blocked from viewing data. Redirects to Login page or outputs `401 Unauthorized`. | **Critical** |

### 4.2 Multi-Tenant Data Isolation (TC-TENANT)

| Test ID | Description | Pre-conditions | Test Steps | Expected Result | Severity |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-TENANT-001** | Cross-Tenant Data Leak Block | Two distinct tenants seeded: Tenant 1 (GlobalParts) and Tenant 2 (SwiftElectronics). | 1. Log in as Tenant 1 Admin (`admin1`).<br>2. Note down list of visible products.<br>3. Log out.<br>4. Log in as Tenant 2 Admin (`admin2`).<br>5. Review product list. | Products from Tenant 1 do not appear in Tenant 2 dashboard or lists. All tables are strictly isolated. | **Critical** |

### 4.3 Product Management & Boundary Thresholds (TC-PROD)

| Test ID | Description | Pre-conditions | Test Steps | Expected Result | Severity |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-PROD-001** | Create Product with Valid Inputs | Logged in as Admin. | 1. Go to Products view.<br>2. Click "Add Product".<br>3. Fill out: Name=`Logitech Mouse`, SKU=`SKU-MOU-01`, Supplier=`GlobalParts`, Category=`Peripherals`, Stock=`50`, Reorder=`10`, Price=`24.99`.<br>4. Submit form. | Product is created. Appears in catalog table. Success toast message displayed. | **Major** |
| **TC-PROD-002** | Boundary Check: Stock Level Equals Reorder Level | Logged in as Admin. Supplier exists. | 1. Create a product.<br>2. Set `stock_level` = `10`.<br>3. Set `reorder_level` = `10`. | Product displays normal availability status. No automated draft Purchase Order is triggered. | **Major** |
| **TC-PROD-003** | Boundary Check: Stock Level Below Reorder Level (Auto PO Trigger) | Same as above. | 1. Create a product.<br>2. Set `stock_level` = `9` (Reorder = `10`).<br>3. Submit. | Reorder is triggered. Low stock alert badge shows. A new draft Purchase Order is auto-generated. | **Major** |

### 4.4 Stock Adjustments & Validation Bounds (TC-STOCK)

| Test ID | Description | Pre-conditions | Test Steps | Expected Result | Severity |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-STOCK-001** | Inline Stock Level Adjustment | Logged in as Staff or Admin. | 1. Navigate to Stock Update page.<br>2. Change quantity from `45` to `50` inside input.<br>3. Click "Adjust Stock". | Stock count updates in database. Product table reflects `50`. Log is written to `stock_history`. | **Major** |
| **TC-STOCK-002** | Validation: Non-Numeric or Negative Quantity Inputs | Same as above. | 1. Navigate to Stock Update page.<br>2. Type `-5` or `abc` inside the stock input field.<br>3. Try to save. | Form validation catches the input. Blocked before submitting (requires positive integer). | **Major** |

### 4.5 Public Customer Showcase Catalog (TC-STORE)

| Test ID | Description | Pre-conditions | Test Steps | Expected Result | Severity |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **TC-STORE-001** | Storefront Offline Fallback | Browser running storefront, Apache Web Server is offline or opened via `file://`. | 1. Double click `storefront.html` on your desktop directory. | Page opens. Detects `file://` protocol. Loads 6 default seeded products from offline JavaScript cache. | **Major** |
| **TC-STORE-002** | Storefront Instant Search Debouncing | Storefront is open. | 1. Click search input.<br>2. Type `USB`. | Catalog grid updates instantly to show only products containing "USB" (e.g. Micro USB Hub). Matches are highlighted. | **Major** |
| **TC-STORE-003** | Storefront Category Filtering Tabs | Storefront is open. | 1. Click category button `Networking`. | Grid filters to show only products belonging to the "Networking" category. Button state switches to active. | **Minor** |
| **TC-STORE-004** | Storefront Quick View Detail Modal | Storefront is open. | 1. Click on any product card (e.g. *Mechanical Switch Pack*). | Modal overlay pops up. Displays full description, supplier contact email/phone, and reorder levels. | **Major** |
| **TC-STORE-005** | Storefront Sorting Filters | Storefront is open. | 1. Click sorting dropdown.<br>2. Select `Price (Low to High)`. | Catalog cards rearrange immediately from lowest unit price to highest unit price. | **Minor** |

---

## 5. Bug Reporting Template (QA Best Practice)
Below is the template to document any defects found during manual testing.

```markdown
### [BUG] Short Descriptive Title (e.g., Auto PO creation triggers duplicate orders)

**Environment**: Local XAMPP, Windows 11, Chrome v125
**Role/Tenant**: Admin / Tenant 1
**Severity**: Major
**Test Case ID**: TC-PROD-003

**Steps to Reproduce**:
1. Log in as admin1
2. Go to views/products.php and add a product with Stock Level = 5 and Reorder Level = 10
3. Navigate to views/orders.php to check the auto-generated draft PO
4. Go back to products and edit the product name, then save.

**Expected Result**:
The draft PO should remain single and unchanged.

**Actual Result**:
A second duplicate draft PO is generated for the same product.

**Screenshots/Logs**:
[Attach image or stack trace here]
```
