# InvenTrack Pro — Multi-Tenant Inventory & Supplier Management System

## Quick Start (XAMPP)

### Step 1 — Copy project to XAMPP htdocs
Copy the entire `Collaborative_project/` folder to your XAMPP `htdocs` directory:
```
C:\xampp\htdocs\Collaborative_project\
```

### Step 2 — Start XAMPP Services
Start **Apache** and **MySQL** in the XAMPP Control Panel.

### Step 3 — Run the Installer
Open your browser and navigate to:
```
http://localhost/Collaborative_project/install.php
```
This will:
- Create the `inventory_system_db` database
- Install all 7 tables with foreign key constraints
- Seed 2 tenants, 4 users, suppliers, products, orders, and stock history
- Generate correct bcrypt password hashes

### Step 4 — Log In
Navigate to:
```
http://localhost/Collaborative_project/
```

## Demo Credentials

| Username | Password   | Role  | Tenant              |
|----------|------------|-------|---------------------|
| admin1   | Admin@123  | Admin | NovaTech Solutions  |
| staff1   | Staff@123  | Staff | NovaTech Solutions  |
| admin2   | Admin@123  | Admin | Meridian Supplies   |
| staff2   | Staff@123  | Staff | Meridian Supplies   |

> **Note:** On the login page, click any demo account box to auto-fill credentials.

---

## Features

| Feature | Details |
|---|---|
| Multi-tenancy | All data partitioned by `tenant_id` — tenants cannot see each other's data |
| RBAC | Admin: full CRUD, destructive ops. Staff: read + stock update only |
| Live Search | Debounced Fetch API search on product list — no page reloads |
| Auto PO | Low-stock products trigger draft Purchase Orders automatically |
| Canvas Charts | Pure HTML5 Canvas line/area chart with animation, dark mode support |
| Dark Mode | CSS custom properties + `data-theme` toggle, persisted in localStorage |
| Export | Print-ready Purchase Order HTML page (Ctrl+P → Save as PDF) |
| Stock History | Every change logged to `stock_history` for audit trail and charting |

---

## Project Structure

```
Collaborative_project/
├── index.php              ← Entry point
├── login.php              ← Login page
├── logout.php             ← Session destroy
├── install.php            ← One-time DB setup (delete after use!)
├── config/
│   ├── db.php             ← PDO singleton
│   └── constants.php      ← App-wide constants
├── controllers/
│   ├── AuthController.php
│   ├── DashboardController.php
│   ├── ProductController.php
│   ├── SupplierController.php
│   ├── OrderController.php
│   └── StockController.php
├── api/
│   ├── products.php       ← JSON: live search
│   ├── stock_chart.php    ← JSON: chart data
│   ├── low_stock.php      ← JSON: alert feed
│   └── export.php         ← Print PO layout
├── views/
│   ├── layout/
│   │   ├── header.php
│   │   └── footer.php
│   ├── dashboard.php
│   ├── products.php
│   ├── suppliers.php
│   ├── orders.php
│   └── stock_update.php
├── assets/
│   ├── css/
│   │   ├── main.css       ← Design system
│   │   ├── dashboard.css  ← KPI/chart/alerts
│   │   └── theme.css      ← Login + print
│   └── js/
│       ├── app.js         ← Theme, sidebar, toasts
│       ├── live_search.js ← Fetch API search
│       └── charts.js      ← Canvas chart renderer
└── database/
    └── schema.sql         ← Full DDL + seed data
```

---

## Security Notes
- All SQL uses PDO prepared statements — SQL injection proof
- Passwords use `password_hash(PASSWORD_BCRYPT, ['cost' => 12])`
- Session uses `httponly`, `samesite=Strict` cookie flags
- Session ID regenerated on login (anti-fixation)
- Multi-tenancy enforced via `tenant_id` scoping on ALL queries
- **Delete `install.php` after first run**
