# Project Overview

This is a factory / production tracking web application.

Purpose:
- Track production orders through custom stages
- Track who worked on what, when it started, and when it finished
- Allow staff to upload PDFs and images related to each order
- Allow clients to receive a read-only tracking link after completion

Constraints:
- Must be simple, cheap to run, and reliable
- No over-engineering, no enums, no hardcoded workflows
- Designed to work on low-cost hosting

---

## Tech Stack

Backend:
- Laravel 12 (PHP 8.2)
- SQLite (Development) / MySQL / PostgreSQL compatible schema
- Bcrypt hashing for authentication

Frontend:
- Server-rendered views (Blade)
- Bootstrap 5 + Vanilla CSS
- Alpine.js for interactive state (e.g., Material Adjustments)
- TomSelect for searchable dropdowns

Storage:
- Local storage for order files
- Database stores relative file paths or public URLs depending on the driver

Auth:
- Staff users authenticate via password
- Role-based access control managed via lookup tables and Laravel Gates
- Clients access order status via expiring tokenized links

---

## Core Concepts

### Users & Roles
- Staff only; each user has ONE role.
- Roles and Users are soft-managed via lookup tables.
- Permissions are granular:
  - `role_stages`: Determines which stages a user can start/finish.
  - `role_permissions`: Controls access to resources (orders, clients, performance).
  - `role_visibility_permissions`: Toggles UI elements like file view buttons.

### Orders
- An order represents one job/delivery.
- Belongs to a client and is associated with multiple materials.
- **Workflow Persistence**: Orders with "Extras" (Herrajería/Manual) stay in the Entrega module until ALL components are delivered.

---

### Workflow Engine (Order Stages)

- **Linear & Custom**: Each order has its own sequence of stages.
- **Resequencing**: Adding/Removing stages triggers dynamic sequence shifting to maintain a gap-less 1-N order.
- **Integrity Rule**: New stages cannot be inserted BEFORE a stage that has already been completed.
- **Status Lifecycle**:
  - Not started (started_at IS NULL)
  - In progress (started_at IS NOT NULL)
  - Completed (completed_at IS NOT NULL)
  - **Pendiente (Blocked)**: Marked by Admin with a reason; blocks Start/Finish actions.

---

### Inventory & Materials
- **Reservation System**: Stock is reserved (`reserved_quantity`) upon order creation.
- **Differential Adjustment**: Editing an order's materials adjusts the `reserved_quantity` based on the difference (NEW - OLD).
- **Consumption Lifecycle**: 
  - On final stage completion, reserved quantity is released, and `stock_quantity` is deducted.
  - **Post-Delivery Correction**: Authorized users can update `actual_quantity` after delivery, triggering stock reconciliation.

---

### Traceability (Order Logs)
- Logs all critical actions (Start, Finish, Remit, Inventory Adjustments).
- **Structured Action Grammar**:
  - `inventory|reserve|material:X|qty:Y`
  - `remit|from:ID|to:ID|reason:TEXT`
  - `inventory|consume|material:X|qty_est:Y|qty_act:Z`

---

## Database Schema (Authoritative)

This project uses a relational SQL database. The schema below is the single source of truth. No ENUMs are used; lookup tables are mandatory.

### Table List
- `roles`: RBAC groups
- `users`: Staff accounts
- `clients`: Order owners
- `orders`: Core job data
- `stages`: Global stage definitions
- `order_stages`: The order-specific workflow
- `materials`: Global stock tracking
- `order_materials`: Order-specific material usage (Pivot)
- `file_types`: Lookup for file categories
- `order_files`: File metadata and paths
- `order_logs`: Audit trail
- `order_tracking_links`: Client access tokens
- `role_stages`: Process permissions
- `role_permissions`: Resource permissions
- `role_visibility_permissions`: UI/Visibility permissions

---

### SQL Schema

```sql
CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE, -- INDEX: Unique
    active BOOLEAN NOT NULL DEFAULT 1
);

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    role_id INTEGER NOT NULL, -- INDEX: FK
    name VARCHAR(150) NOT NULL,
    document VARCHAR(50) NOT NULL UNIQUE, -- INDEX: Unique
    password VARCHAR(255) NOT NULL,
    active BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE clients (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL, -- INDEX: Explicit
    document VARCHAR(50) NOT NULL UNIQUE, -- INDEX: Unique + Explicit
    phone VARCHAR(30) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE stages (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE, -- INDEX: Unique
    default_sequence INTEGER NOT NULL DEFAULT 0,
    can_remit BOOLEAN NOT NULL DEFAULT 1,
    is_delivery_stage BOOLEAN NOT NULL DEFAULT 0
);

CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    client_id INTEGER NOT NULL, -- INDEX: FK
    notes VARCHAR(300) NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE, -- INDEX: Unique
    created_by INTEGER NOT NULL, -- INDEX: FK
    delivered_at TIMESTAMP NULL,
    delivered_by INTEGER NULL, -- INDEX: FK
    lleva_herrajeria BOOLEAN NOT NULL DEFAULT 0,
    lleva_manual_armado BOOLEAN NOT NULL DEFAULT 0,
    herrajeria_delivered_at TIMESTAMP NULL,
    herrajeria_delivered_by INTEGER NULL, -- INDEX: FK
    manual_armado_delivered_at TIMESTAMP NULL,
    manual_armado_delivered_by INTEGER NULL, -- INDEX: FK
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

CREATE TABLE order_stages (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    order_id INTEGER NOT NULL, -- INDEX: FK
    stage_id INTEGER NOT NULL, -- INDEX: FK
    sequence INTEGER NOT NULL,
    notes VARCHAR(300) NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    started_by INTEGER NULL, -- INDEX: FK
    completed_by INTEGER NULL, -- INDEX: FK
    is_pending BOOLEAN NOT NULL DEFAULT 0,
    pending_reason VARCHAR(250) NULL,
    pending_marked_by INTEGER NULL, -- INDEX: FK
    pending_marked_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (order_id, stage_id), -- INDEX: Unique Composite
    UNIQUE (order_id, sequence), -- INDEX: Unique Composite
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (stage_id) REFERENCES stages(id)
);

CREATE TABLE materials (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    stock_quantity DECIMAL(12, 2) NOT NULL DEFAULT 0,
    reserved_quantity DECIMAL(12, 2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE order_materials (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    order_id INTEGER NOT NULL, -- INDEX: FK
    material_id INTEGER NOT NULL, -- INDEX: FK
    estimated_quantity DECIMAL(12, 2) NOT NULL,
    actual_quantity DECIMAL(12, 2) NULL,
    notes VARCHAR(50) NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES materials(id) ON DELETE CASCADE
);

CREATE TABLE order_logs (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    order_id INTEGER NOT NULL, -- INDEX: FK
    user_id INTEGER NOT NULL, -- INDEX: FK
    action VARCHAR(400) NOT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE file_types (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL UNIQUE -- INDEX: Unique
);

CREATE TABLE order_files (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    order_id INTEGER NOT NULL, -- INDEX: FK
    file_type_id INTEGER NOT NULL, -- INDEX: FK
    file_path TEXT NOT NULL,
    uploaded_by INTEGER NOT NULL, -- INDEX: FK
    uploaded_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (file_type_id) REFERENCES file_types(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE order_tracking_links (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    order_id INTEGER NOT NULL, -- INDEX: FK
    token VARCHAR(255) NOT NULL UNIQUE, -- INDEX: Unique
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE role_stages (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    role_id INTEGER NOT NULL, -- INDEX: FK
    stage_id INTEGER NOT NULL, -- INDEX: FK
    UNIQUE (role_id, stage_id), -- INDEX: Unique Composite
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (stage_id) REFERENCES stages(id)
);

CREATE TABLE role_permissions (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    role_id INTEGER NOT NULL, -- INDEX: FK
    resource_type VARCHAR(100) NOT NULL,
    can_view BOOLEAN NOT NULL DEFAULT 0,
    can_create BOOLEAN NOT NULL DEFAULT 0,
    can_edit BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE (role_id, resource_type), -- INDEX: Unique Composite
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE role_visibility_permissions (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    role_id INTEGER NOT NULL UNIQUE, -- INDEX: Unique + FK
    can_view_files BOOLEAN NOT NULL DEFAULT 1,
    can_view_order_file BOOLEAN NOT NULL DEFAULT 1,
    can_view_machine_file BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
```

For frontend details and module-specific UI, see context-frontend.md.