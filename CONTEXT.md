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
- Laravel (PHP)
- MySQL / PostgreSQL compatible schema
- Bcrypt hashing for authentication

Frontend:
- Server-rendered views (no SPA requirement)
- Simple role-based UI

Storage:
- Local storage in Laravel `storage/app/orders/` (or `public/orders/` if you want public access)
- Database stores relative file paths only (e.g., `orders/12345.pdf`)

Auth:
- Staff users authenticate via password
- Clients do NOT have accounts
- Clients access order status via expiring tokenized link

---

## Core Concepts

### Users
- Staff only
- Each user has ONE role
- Users can be deactivated
- Passwords are stored as bcrypt hashes

Roles are stored in a lookup table, not enums.

---

### Orders
- An order represents one delivery / job
- Each order belongs to a client
- Orders do NOT have a fixed workflow
- Projects may include extras: Hardware (herrajería) and Assembly Manual
- Delivery of the main product (furniture) and extras happen independently
- System tracks delivery traceability (timestamp and user) for each component separately

---

### Stages
- Stages define possible process steps (e.g. corte, enchape, revision)
- Stages are static lookup data
- Each order selects which stages it will go through

---

### Role Stage Access

- Roles define what stages a user is allowed to work on
- A role may be allowed to work on multiple stages
- A stage may be worked on by multiple roles
- This allows temporary or permanent overlap between roles when understaffed
- Authorization is data-driven, not hardcoded
---
### Order Stages (Workflow Engine)

This is the heart of the system.

- Each order has its own set of stages
- Stages are linear per order
- A stage can be:
  - Not started
  - In progress
  - Completed
  - **Pendiente (Skipped)**: Temporarily blocked (skip from queue, traceability reason required).

Tracked fields (Pendiente):
- is_pending
- pending_reason (max 250)
- pending_marked_by
- pending_marked_at

Tracked fields:
- started_at
- completed_at
- started_by
- completed_by

Users must click:
- "Start" to begin a stage
- "Finish" to complete a stage

Duration is calculated from timestamps.

---

## Order File (Archivo de la Orden)

There is exactly ONE canonical file associated with an order in v1.

**Ownership and lifecycle**
- The file belongs to the ORDER, not to any stage
- It is uploaded ONLY at order creation
- It never changes during production stages

**Upload rules**
- Single file input
- PDF only
- Optional
- Uploaded at `/orders/create`
- Stored in local storage (`storage/app/public/order/` or `public/order/`)
- Database stores relative path only (e.g., `order/archivo_orden_123.pdf`)
- Creates exactly ONE row in `order_files`
- Uses `file_types = archivo_orden`

**Hard prohibitions (all versions)**
- Stages MUST NOT upload files
- Stages MUST NOT replace files
- Stages MUST NOT delete files
- Multi-file inputs are forbidden
- Multi-select inputs are forbidden

**Visibility**
- Read-only
- Visible in all stage modules as reference material
- If no file exists, nothing is shown

**Out of scope (v1)**
- File replacement
- File deletion
- Multiple order-level files

**Extending files in future versions**
- Supporting additional order-level files requires:
  - Creating a new `file_types` row
  - Adding exactly ONE new single-file input to `/orders/create`
- Each file type maps to one input and one `order_files` row
- No file type may accept multiple files
- No stage may introduce new file inputs

---

### Order Logs

- Used for auditing and traceability
- Logs important actions only
- Not used as the primary source of state

---

### Client Tracking Links

- Generated ONLY after the final stage (revision) is completed
- Token-based, no login
- Expiring access
- Read-only view

Each link:
- Belongs to one order
- Has an expiration date
- Can be regenerated if needed

---

### Delivery Workflow

- **Main Delivery**: Marking the final stage as "Entrega del mueble realizada" tracks when the product leaves the factory.
- **Independent Extras**: Hardware and manuals can be marked as delivered independently from the main product.
- **Stage Persistence**: An order remains visible in the "Entrega" module until ALL required components (Furniture, Hardware, and Manual) have been delivered. It only disappears from the production queue when no required delivery remains pending.
- **Traceability**: Each delivery component (Furniture, Hardware, Manual) stores its own `delivered_at` and `delivered_by` data.
- **Operational Flexibility**: Main delivery is NOT blocked if extras are still pending.
- **Conditional UI**: Buttons for extras delivery only appear if the order specifically requires them.

---

## Database Design Principles

- No ENUMs
- Lookup tables for roles, stages, and file types
- Clear separation of concerns
- Foreign keys for integrity
- Portability across SQL engines

---

## Security Rules

- Staff can only interact with stages allowed for their role
- Admin can see everything
- Clients cannot modify anything
- Tracking tokens must expire

---

## Non-Goals

- No client accounts
- No password recovery system for clients
- No real-time notifications
- No complex permission matrix

---

## Development Philosophy

- Simplicity over cleverness
- Business rules in application layer
- Database is a source of truth
- Build for clarity, not abstraction





## Database Schema (Authoritative)

This project uses a relational SQL database.
The schema below is the single source of truth.
The AI must NOT invent tables, columns, enums, or relationships outside of this definition.

No ENUMs are used. Lookup tables are used instead.

---

### Tables Overview

- roles
- users
- clients
- role_client_permissions
- orders
- stages
- order_stages
- file_types
- order_files
- order_logs
- order_tracking_links
- role_stages
- role_order_permissions
---

### Role Order Permissions

- This table defines which roles are allowed to **create, view, or edit orders**.
- It is separate from `role_stages`, which controls stage access.
- Columns:
  - `role_id` → links to `roles` table
  - `can_view` → whether the role can see all orders
  - `can_edit` → whether the role can edit orders
  - `can_create` → whether the role can create new orders
- This table is **managed by developers**, not admins.
- When checking permissions for order actions:
  - `/orders/create` → `can_create`
  - Orders List `/orders` → `can_view`
  - Order edit `/orders/{order}/edit` → `can_edit`
- Roles not included in this table have no access to these order actions by default.
- This allows dynamic control over which roles can manage orders without hardcoding Admin or Secretaria roles.
---
## SQL Schema

```sql
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    document VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    document VARCHAR(50) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (name),
    INDEX (document)
);

CREATE TABLE stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    default_sequence INT NOT NULL DEFAULT 0
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    material VARCHAR(255) NOT NULL,
    lleva_herrajeria TINYINT(1) NOT NULL DEFAULT 0,
    lleva_manual_armado TINYINT(1) NOT NULL DEFAULT 0,
    notes VARCHAR(300) NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    delivered_at TIMESTAMP NULL,
    delivered_by INT NULL,
    herrajeria_delivered_at TIMESTAMP NULL,
    herrajeria_delivered_by INT NULL,
    manual_armado_delivered_at TIMESTAMP NULL,
    manual_armado_delivered_by INT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (delivered_by) REFERENCES users(id),
    FOREIGN KEY (herrajeria_delivered_by) REFERENCES users(id),
    FOREIGN KEY (manual_armado_delivered_by) REFERENCES users(id)
);

CREATE TABLE order_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    stage_id INT NOT NULL,
    sequence INT NOT NULL,
    notes VARCHAR(300) NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    started_by INT NULL,
    completed_by INT NULL,
    is_pending TINYINT(1) NOT NULL DEFAULT 0,
    pending_reason VARCHAR(250) NULL,
    pending_marked_by INT NULL,
    pending_marked_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (stage_id) REFERENCES stages(id),
    FOREIGN KEY (started_by) REFERENCES users(id),
    FOREIGN KEY (completed_by) REFERENCES users(id),
    FOREIGN KEY (pending_marked_by) REFERENCES users(id),
    UNIQUE (order_id, stage_id),
    UNIQUE (order_id, sequence)
);

CREATE TABLE file_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE order_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    file_type_id INT NOT NULL,
    file_path TEXT NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (file_type_id) REFERENCES file_types(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE order_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    action VARCHAR(400) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE order_tracking_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

CREATE TABLE role_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    stage_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (stage_id) REFERENCES stages(id),
    UNIQUE (role_id, stage_id)
);


CREATE TABLE role_order_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL UNIQUE,
    can_view BOOLEAN NOT NULL DEFAULT 0,
    can_edit BOOLEAN NOT NULL DEFAULT 0,
    can_create BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
CREATE TABLE role_client_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL UNIQUE,
    can_view BOOLEAN NOT NULL DEFAULT 0,
    can_create BOOLEAN NOT NULL DEFAULT 0,
    can_edit BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);
For frontend context, see context-frontend.md