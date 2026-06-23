SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS inventory_system_db;
CREATE DATABASE inventory_system_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_system_db;

CREATE TABLE tenants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    subdomain VARCHAR(80) NOT NULL UNIQUE,
    plan ENUM('free','pro','enterprise') NOT NULL DEFAULT 'pro',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    username VARCHAR(80) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenant_username (tenant_id,username),
    UNIQUE KEY uq_tenant_email (tenant_id,email),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    contact_email VARCHAR(150),
    phone VARCHAR(30),
    address TEXT,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_suppliers_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    sku VARCHAR(80) NOT NULL,
    description TEXT,
    stock_level INT NOT NULL DEFAULT 0,
    reorder_level INT NOT NULL DEFAULT 10,
    unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category VARCHAR(80),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tenant_sku (tenant_id,sku),
    CONSTRAINT fk_products_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_products_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    order_status ENUM('draft','pending','ordered','received','cancelled') NOT NULL DEFAULT 'draft',
    total_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_orders_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE stock_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    old_stock INT NOT NULL,
    new_stock INT NOT NULL,
    changed_by INT UNSIGNED,
    change_note VARCHAR(255),
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stockhist_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_stockhist_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO tenants (company_name,subdomain,plan) VALUES ('NovaTech Solutions','novatech','enterprise'),('Meridian Supplies','meridian','pro');

INSERT INTO users (tenant_id,username,email,password,role) VALUES
(1,'admin1','admin1@novatech.com','PLACEHOLDER_WILL_BE_REPLACED_BY_INSTALL','admin'),
(1,'staff1','staff1@novatech.com','PLACEHOLDER_WILL_BE_REPLACED_BY_INSTALL','staff'),
(2,'admin2','admin2@meridian.com','PLACEHOLDER_WILL_BE_REPLACED_BY_INSTALL','admin'),
(2,'staff2','staff2@meridian.com','PLACEHOLDER_WILL_BE_REPLACED_BY_INSTALL','staff');

INSERT INTO suppliers (tenant_id,name,contact_email,phone,address) VALUES
(1,'GlobalParts Inc.','orders@globalparts.com','+1-800-555-0101','123 Industrial Blvd, Chicago, IL'),
(1,'SwiftElectronics Ltd.','supply@swiftelectronics.io','+1-800-555-0202','45 Silicon Ave, San Jose, CA'),
(1,'PackagePro Co.','info@packagepro.net','+1-800-555-0303','78 Warehouse St, Dallas, TX'),
(2,'ArcMetal Distributors','sales@arcmetal.com','+44-20-555-0401','10 Forge Road, Birmingham, UK'),
(2,'ClearView Glass Works','orders@clearviewglass.co','+44-20-555-0402','22 Crystal Lane, Manchester, UK');

INSERT INTO products (tenant_id,supplier_id,name,sku,description,stock_level,reorder_level,unit_price,category) VALUES
(1,1,'Micro USB Hub 7-Port','SKU-NT-001','Industrial 7-port USB 3.0 hub',45,15,29.99,'Electronics'),
(1,1,'Cat6 Ethernet Cable 10m','SKU-NT-002','High-speed Cat6 shielded patch cable',8,20,12.49,'Networking'),
(1,2,'Wireless Keyboard Pro','SKU-NT-003','Ergonomic wireless keyboard with backlight',120,30,54.99,'Peripherals'),
(1,2,'Mechanical Switch Pack','SKU-NT-004','Blue MX switch replacement set (50pcs)',5,25,18.75,'Components'),
(1,3,'Anti-Static Bubble Wrap','SKU-NT-005','500m roll industrial bubble wrap',200,50,34.00,'Packaging'),
(1,3,'HDMI 2.1 Cable 2m','SKU-NT-006','8K/60Hz HDMI cable with braided jacket',3,10,21.99,'Cables'),
(2,4,'Steel Bracket L-Shape','SKU-MR-001','Heavy-duty galvanized L-bracket 150mm',300,50,4.50,'Hardware'),
(2,4,'Hex Bolt M8 x 30mm','SKU-MR-002','Grade 8.8 hex bolt, zinc plated (box/100)',12,40,8.25,'Fasteners'),
(2,5,'Tempered Glass Panel 4mm','SKU-MR-003','Clear tempered safety glass 600x400mm',75,20,67.00,'Glass'),
(2,5,'Mirror Adhesive 300ml','SKU-MR-004','Heavy-duty mirror bonding adhesive',6,15,11.50,'Adhesives');

INSERT INTO orders (tenant_id,supplier_id,order_status,total_cost,notes) VALUES
(1,1,'draft',498.00,'Auto-generated: SKU-NT-002 below reorder level'),
(1,2,'draft',562.50,'Auto-generated: SKU-NT-004 below reorder level'),
(1,3,'pending',219.90,'Auto-generated: SKU-NT-006 below reorder level'),
(1,1,'received',374.70,'Previous order - fully received'),
(2,4,'draft',330.00,'Auto-generated: SKU-MR-002 below reorder level'),
(2,5,'draft',103.50,'Auto-generated: SKU-MR-004 below reorder level');

INSERT INTO order_items (order_id,product_id,quantity,unit_cost) VALUES
(1,2,40,12.45),(2,4,30,18.75),(3,6,10,21.99),(4,1,15,24.98),(5,8,40,8.25),(6,10,9,11.50);

INSERT INTO stock_history (tenant_id,product_id,old_stock,new_stock,change_note,recorded_at) VALUES
(1,1,60,55,'Daily consumption','2026-05-24 09:00:00'),(1,1,55,50,'Daily consumption','2026-05-28 09:00:00'),
(1,1,50,47,'Sale batch','2026-06-01 09:00:00'),(1,1,47,65,'Restock received','2026-06-05 09:00:00'),
(1,1,65,58,'Daily consumption','2026-06-08 09:00:00'),(1,1,58,53,'Sale batch','2026-06-12 09:00:00'),
(1,1,53,49,'Daily consumption','2026-06-15 09:00:00'),(1,1,49,45,'Daily consumption','2026-06-19 09:00:00'),
(1,1,45,43,'Sale batch','2026-06-22 09:00:00'),
(1,2,40,35,'Sale batch','2026-05-24 09:00:00'),(1,2,35,25,'Daily consumption','2026-06-01 09:00:00'),
(1,2,25,15,'Sale batch','2026-06-10 09:00:00'),(1,2,15,8,'Daily consumption','2026-06-20 09:00:00'),
(1,3,150,145,'Sale batch','2026-05-24 09:00:00'),(1,3,145,130,'Daily consumption','2026-06-05 09:00:00'),
(1,3,130,120,'Sale batch','2026-06-15 09:00:00'),
(1,4,50,35,'Sale batch','2026-05-24 09:00:00'),(1,4,35,20,'Daily consumption','2026-06-05 09:00:00'),
(1,4,20,8,'Sale batch','2026-06-15 09:00:00'),(1,4,8,5,'Daily consumption','2026-06-22 09:00:00'),
(1,5,250,245,'Daily consumption','2026-05-24 09:00:00'),(1,5,245,225,'Sale batch','2026-06-05 09:00:00'),
(1,5,225,200,'Daily consumption','2026-06-20 09:00:00'),
(1,6,25,15,'Sale batch','2026-05-24 09:00:00'),(1,6,15,8,'Daily consumption','2026-06-05 09:00:00'),
(1,6,8,3,'Sale batch','2026-06-20 09:00:00');
