# Frontend Context – Madeiros Project

This file stores all frontend-specific rules, modules, and role-based behaviors for the production tracking app.

---

## Module: Corte
**Role:** Empleado de corte

**Orders shown:**  
- All orders that require Corte.

**Table columns:**  
- ID  
- Nombre de cliente  
- Tipo de Material  
- Archivo de la Orden (PDF, read-only)
- Archivo máquina (TBD)  
- Fecha envío  
- Estado de proyecto (e.g., Pendiente para corte, En corte)  
- Tiempo de corte  
- Observaciones de corte  
- Observaciones generales  
- Acciones

**Actions:**  
- Iniciar  
- Pausar  
- Finalizar (sends the order to the next stage)

**Special rules:**  
- Once an order is finished in this stage, it moves to the next stage in the workflow.  
- The current stage no longer shows this order; the next stage sees it as the next up.

---

## Module: Enchape
**Role:** Empleado de enchape

**Orders shown:**  
- All orders that require Enchape and have completed the previous stage (Corte).

**Table columns:**  
- ID  
- Nombre de cliente  
- Tipo de Material  
Archivo de la Orden (PDF, read-only if exists)
- Fecha envío  
- Estado de proyecto (e.g., Listo para Enchape, Enchapando)  
- Observaciones Enchape  
- Observaciones generales  
- Acciones  
- Remitir a (stage before this one)

**Actions:**  
- Iniciar  
- Pausar  
- Finalizar (sends the order to the next stage)

**Special rules:**  
- Same as Corte: finished orders move forward, current stage no longer sees it.

---

## Module: Servicios Especiales
**Role:** Empleado de servicios especiales

**Orders shown:**  
- All orders that require Servicios Especiales and have completed prior required stages.

**Table columns:**  
- ID  
- Nombre de cliente  
- Tipo de Material  
- Fecha envío  
- Estado de proyecto (e.g., Listo para Servicios Especiales, En proceso)  
Archivo de la Orden (PDF, read-only if exists) 
- Observaciones Servicios Especiales  
- Observaciones generales  
- Acciones  
- Remitir a (stage before this one)

**Actions:**  
- Iniciar  
- Pausar  
- Finalizar (sends the order to the next stage)

**Special rules:**  
- Finished orders move forward; current stage no longer sees it.

---

## Module: Revision
**Role:** Empleado de revisión

**Orders shown:**  
- All orders that have passed the required stages for that order.

**Table columns:**  
- ID  
- Nombre de cliente  
- Tipo de Material  
-Archivo de la Orden (PDF, read-only if exists)
- Fecha envío  
- Estado de proyecto (e.g., Listo para Revisión, En revisión)  
- Observaciones Enchape  
- Observaciones generales  
- Acciones  
- Remitir a (stage before this one)

**Actions:**  
- Iniciar  
- Pausar  
- Finalizar (sends the order to the next stage)

**Special rules:**  
- Finished orders move forward; current stage no longer sees it.

---
## Module: Orders List
**Role:** Admin (or any other role authorized to manage orders, e.g., Secretaria)

**Orders shown:**  
* Users with these roles can see **all orders**, regardless of which user created them.

**Table columns:**  
* ID  
* Nombre de cliente  
* Material  
* Current Stage (next stage to act on, or “Completed” if delivered)  
* Created At  
* Actions (Edit, Add Stage, Remove Stage)

**Actions:**  
* **Edit:** Authorized users can **only update** the following fields:  
  - Número de factura/pedido (must remain unique)  
  - Material  
  - Notas especiales  
  - Ruta de producción (the stages assigned to the order)  
* **Add Stage:** Can only add stages **after the current stage** of the order.  
* **Remove Stage:** Can only remove stages **not yet started** (`started_at` is null).  

**Special rules:**  
* After creating a new order, redirect to this Orders List view instead of going to the first stage.  
* Stage workflow integrity must be maintained.  
* Only users with roles authorized to manage orders can access this module.  
* Invoice numbers (`Número de factura/pedido`) must be unique across all orders.  
* Use `/orders/create` (`create.blade.php`) as a reference for how to structure the update/edit form.
---

## Module: Entrega
**Role:** Empleado de entrega

**Orders shown:**  
- Orders that have completed Revision.

**Table columns:**  
- ID  
- Nombre de cliente  
- Tipo de Material  
- Fecha envío  
- Estado de proyecto (e.g., Listo para entrega)  
-Archivo de la Orden (PDF, read-only if exists)
- Observaciones generales  
- Acciones

**Actions:**  
- Iniciar  
- Pausar  
- Finalizar (marks order as delivered, sets `delivered_at` and `delivered_by`)

**Special rules:**  
- Finished orders are marked as delivered; current stage no longer shows it.  
- Users cannot interact with production stages; only delivery actions are allowed.

---
## Module: Clients
**Role:** Admin (or any future role authorized via Gate)

**Clients shown:**  
- Only users authorized with `view-clients` can see the list.
- Currently, only Admin can view clients, but the system should allow extending to other roles later.

**Table columns:**  
- ID  
- Nombre  
- Documento  
- Teléfono  
- Fecha de creación  

**Actions:**  
- **Create Client:** Only visible to users authorized with `create-clients`.  
- **Update Client:** Only visible to users authorized with `edit-clients`.  
- **No Delete:** Clients cannot be deleted.  

**Special rules:**  
- Admin can create and update clients; other roles cannot.  
- Clients are permanent and cannot be removed.  
- Created clients can immediately be assigned to orders when creating or editing an order.  
- Frontend buttons or links for creating clients should respect Gate permissions.  
- Access control is entirely managed via Laravel Gates:
  - `view-clients` → can see the Clients list
  - `create-clients` → can create new clients
  - `edit-clients` → can update existing clients
- No roles are hardcoded; new roles can be granted permissions simply by assigning these Gate abilities.
- Frontend buttons and links must respect these Gate permissions, so users without access do not see or interact with forbidden actions.
---
## Notes on Role-Stage Access
- Each role can access one or more stages as defined in the `role_stages` table.  
- Default workflow: Corte → Enchape → Servicios Especiales → Revision → Entrega  
- Orders can skip stages if configured at creation.  
- Admin always has access to all stages.  
- Each module only shows orders for stages the user has permission to act on.  
- Actions available per module: Start, Pause, Finish, Add Notes (except Entrega, which only finishes/delivers).  
-  “Remitir a” sends the order back to the immediately previous completed stage and resets the current stage timestamps.
---


## Module: Order Creation
**Role:** Admin (or any role with `can_create` on orders)

**Route:**
- `GET /orders/create`
- `POST /orders`

**Form fields:**
- Cliente
- Número de factura/pedido (unique)
- Material
- Notas especiales
- Ruta de producción (stages selection)
- **Archivo de la Orden (PDF, optional, order-level)**

**Archivo de la Orden rules:**
- Optional
- Single file input
- PDF only
- Uploaded at order creation time
- Stored in external storage (Google Drive)
- Database stores public file URL only
- Creates exactly ONE row in `order_files`
- Uses a dedicated `file_types` value (e.g. `archivo_orden`)

**Hard constraints:**
- One input = one file
- Multi-file inputs are forbidden
- File cannot be replaced or deleted in v1
- Stages cannot upload or modify files

**Visibility:**
- Read-only
- Visible in all stage modules as reference material
---

## Build Configuration

**Sass Deprecations**
- We have explicitly silenced Sass deprecation warnings in `vite.config.js` to handle Bootstrap 5's legacy Sass usage.
- Silenced warnings: `import`, `if-function`, `global-builtin`, `color-functions`.
- This ensures clean build output without noise from `node_modules`.
