# InvenTrack Pro

An enterprise-grade, multi-tenant inventory and supplier management system designed to handle structured supply chains, role-based workflows, and automated procurement logic. 

InvenTrack Pro isolates commercial data strictly by tenant while providing full CRUD control over stock lifecycles, automated purchase order generation, and real-time interactive dashboards.

## Key System Features

* **Robust Multi-Tenancy:** All records are partitioned securely using unique `tenant_id` scopes. Cross-tenant data leaks are structurally prevented at the database query level.
* **Role-Based Access Control (RBAC):** Distinct administrative profiles handle critical mutations (full CRUD and destructive operations), while Staff accounts are restricted to standard monitoring and stock reconciliation updates.
* **Automated Purchase Orders (Auto-PO):** The platform tracks safety thresholds programmatically. When inventory falls below designated reorder levels, draft purchase orders are generated automatically to prevent stockouts.
* **Live Interactive Search:** Utilizes a debounced native Fetch API interface for lightning-fast product filtering and inventory catalog inquiries without refreshing pages.
* **Native Analytics Engine:** Includes a pure HTML5 Canvas analytical plotting grid featuring hardware-accelerated animations, multi-tenant trend parsing, and dark-theme persistence.

---

## Technical Stack Architecture

* **Frontend Environment:** Vanilla HTML5, CSS3 Custom Properties (Persisted Theme Matrix), JavaScript (ES6+ Engine)
* **Server Runtime:** Apache / PHP 8.1+ (Strict Data Typing Enforcement)
* **Relational Database:** MySQL 8.0+ (InnoDB Engine, Cascading Constraints, Indexed Foreign Vectors)
* **Security Mechanisms:** PDO Parameterized Preparations, Bcrypt Hashing (Work Factor 12), Cryptographically Secure Session Regeneative Matrices, `httponly` & `SameSite=Strict` Cookie Parameters.

---

## Workspace Directory Structure

```text
Collaborative_project/
├── index.php              # Secure Application Router & Entry Gate
├── login.php              # Session Initiation and Anti-Fixation Core
├── logout.php             # Session Purge & State Destruction
├── install.php            # One-Time Automated Database Provisioner
├── config/
│   ├── db.php             # PDO Singleton Connection Core
│   └── constants.php      # Global Application Declarations
├── controllers/           # Operational Process Controllers (Auth, Product, Orders)
├── api/                   # Async Fetch JSON Endpoints & Export Layout Drivers
├── views/                 # Presentation Layouts & Restricted Workflow Panels
├── assets/                # CSS Core Architecture, Custom Themes & Canvas Render Engine
└── database/
    └── schema.sql         # Relational Constraints Layout & Base Seeds
