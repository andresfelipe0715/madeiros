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
- External file storage (Google Drive or similar)
- Database stores file URLs only

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

---

### Stages
- Stages define possible process steps (e.g. corte, enchape, revision)
- Stages are static lookup data
- Each order selects which stages it will go through

---

### Order Stages (Workflow Engine)

This is the heart of the system.

- Each order has its own set of stages
- Stages are linear per order
- A stage can be:
  - Not started
  - In progress
  - Completed

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

### Files

- Orders can have multiple files
- File types are stored in a lookup table
- Examples:
  - Initial order PDF
  - Revision images
  - Extra attachments

Database stores file URLs only.

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

## Database Design Principles

- No ENUMs
- Lookup tables for roles, stages, and file types
- Clear separation of concerns
- Foreign keys for integrity
- Portability across SQL engines

---

## Security Rules

- Staff can only interact with stages matching their role
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
- orders
- stages
- order_stages
- file_types
- order_files
- order_logs
- order_tracking_links

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
    phone VARCHAR(30) NULL
);

CREATE TABLE stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE order_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    stage_id INT NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    started_by INT NULL,
    completed_by INT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (stage_id) REFERENCES stages(id),
    FOREIGN KEY (started_by) REFERENCES users(id),
    FOREIGN KEY (completed_by) REFERENCES users(id),
    UNIQUE (order_id, stage_id)
);

CREATE TABLE file_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE order_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    file_type_id INT NOT NULL,
    file_url TEXT NOT NULL,
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
    action VARCHAR(255) NOT NULL,
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