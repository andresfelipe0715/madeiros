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
- Archivo proyecto (pdf or csv if exists for that order)  
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
- Archivo proyecto (pdf or csv if exists for that order)  
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
- Archivo proyecto (pdf or csv if exists for that order)  
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
- Archivo proyecto (pdf or csv if exists for that order)  
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
- Archivo proyecto (pdf or csv if exists for that order)  
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

## Notes on Role-Stage Access
- Each role can access one or more stages as defined in the `role_stages` table.  
- Default workflow: Corte → Enchape → Servicios Especiales → Revision → Entrega  
- Orders can skip stages if configured at creation.  
- Admin always has access to all stages.  
- Each module only shows orders for stages the user has permission to act on.  
- Actions available per module: Start, Pause, Finish, Add Notes (except Entrega, which only finishes/delivers).  
