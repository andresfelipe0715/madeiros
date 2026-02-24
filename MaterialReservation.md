PROMPT 1 — Material Reservation & Consumption System

Implement a material reservation, adjustment, and consumption system for production orders.

The system must preserve stock integrity and prevent negative inventory.

Core Principles

Corte stage NEVER consumes material
Corte only prepares pieces.

Materials are reserved when an order is created.

Materials may be adjusted, replaced, or cancelled while the order is in production.

Final consumption occurs ONLY when:

the order is delivered (delivered_at), OR

the order is cancelled (reservations released)

Inventory must never go negative.

All operations must be transactional.

Database Tables
materials

id

name

stock_quantity

reserved_quantity

created_at

updated_at

order_materials

id

order_id

material_id

estimated_quantity

actual_quantity (nullable)

cancelled_at (nullable)

created_at

updated_at

Lifecycle Overview
1️⃣ Reservation (Order Creation)

Materials are reserved when the order is created.

available = stock_quantity - reserved_quantity

IF available < estimated_quantity
    → prevent order creation
ELSE
    reserved_quantity += estimated_quantity
2️⃣ Adjustment (While Order is Active)

Adjustments happen before delivery and modify the reservation.

Reduce quantity
difference = old_estimated - new_estimated
reserved_quantity -= difference
Increase quantity
difference = new_estimated - old_estimated

IF available < difference
    → block change
ELSE
    reserved_quantity += difference
3️⃣ Cancel Material (While Order Active)

A material may be cancelled if:

added by mistake

replaced by another material

no longer required

reserved_quantity -= estimated_quantity
cancelled_at = NOW()

Rules:

cannot cancel after order delivery

cancelled materials must be excluded from consumption

action must be logged

4️⃣ Replace Material

If the wrong material was added:

cancel original material

create new reservation for replacement

5️⃣ Final Consumption (Order Delivered)

When the order is delivered:

Only process materials where:

cancelled_at IS NULL
Step 1 — Determine used quantity
IF actual_quantity IS NULL
    used_quantity = estimated_quantity
ELSE
    used_quantity = actual_quantity
Step 2 — Convert reservation into consumption
stock_quantity    -= used_quantity
reserved_quantity -= estimated_quantity
6️⃣ Consumption Adjustment at Delivery
If MORE material used than estimated
extra_used = actual_quantity - estimated_quantity
stock_quantity -= extra_used
If LESS material used than estimated
returned = estimated_quantity - actual_quantity
stock_quantity += returned
7️⃣ Order Cancellation (Before Delivery)

If an order is cancelled:

reserved_quantity -= estimated_quantity

No stock is consumed.

Critical Constraints

Consumption happens only at delivery.

Reservations protect stock during production.

Cancelled materials MUST NOT affect stock consumption.

Stock must never become negative.

All inventory updates must be atomic and consistent.

Required Query Rule (Prevents Ghost Consumption)

Consumption must always use:

WHERE cancelled_at IS NULL
Operational Guarantees

The system must:

✔ prevent over-reservation
✔ allow safe mid-production adjustments
✔ allow material cancellation and replacement
✔ maintain accurate inventory at delivery
✔ prevent double consumption
✔ preserve auditability