# Frontend Context

## Architecture Overview
The frontend is a server-rendered application using **Laravel Blade**, **Bootstrap 5**, and **Alpine.js** for client-side interactivity.

### Key Technologies
- **Bootstrap 5 & Icons**: Core layout and UI elements.
- **Alpine.js**: Manages dynamic UI states (modals, material adjustment forms, file visibility).
- **TomSelect**: Used for searchable material/client dropdowns.
- **Debounced Search**: Stage modules use debounced inputs to filter orders without full page reloads.

---

## Core Components

### Modular Stage Table (`partials/stage-table.blade.php`)
This is the primary component for all production stage modules.
- **Stateful Status**: Uses `isNext` pulsing badges to highlight the next order in the queue.
- **Dynamic Actions**: Action buttons (Start, Pause, Finish) are rendered conditionally based on:
    - User role stage access.
    - Global resource permissions.
    - Order sequence position.
    - Queue priority (`StageAuthorizationService`).
- **Pervasive Modals**: 
    - Material detail modals.
    - Notes management.
    - "Mark as Pending" forms with reason tracking.

### Material Management Form (`orders/edit.blade.php`)
Managed via **Alpine.js** to handle complex material state:
- **Differential Tracking**: Tracks changes between estimated and actual stock usage.
- **Independent Cancellation**: Supports cancelling individual items within a material list.
- **Real-time Validation**: Enforces note character limits (50 chars for materials, 300 for order) and positive quantities.

---

## Visibility & Permissions Layer
Access control is enforced at both the backend (Gates) and frontend (VisibilityService).

### VisibilityService
Toggles UI components based on `role_visibility_permissions`:
- `can_view_files`: Global toggle for the files column.
- `can_view_order_file`: Specific toggle for the primary order PDF.
- `can_view_machine_file`: Specific toggle for technical stage files.

### Interactive Rules
- **Delivery Lock**: Material adjustments are disabled once `delivered_at` is set, unless the user has `orders.edit` permission for corrections.
- **Role-Based Menus**: Sidebar and top-bar links are generated based on `role_permissions`.

---

## Modules & Views

### Stage Modules (Corte, Enchape, etc.)
- **Role Association**: Each module is accessible only to users whose role is linked to that stage in `role_stages`.
- **Columns**: ID, Client, Materials (estimated), Status, Actions.

### Order Management (`orders.index`)
- **Global View**: Authorized users can see the entire production pipeline.
- **Current Stage Logic**: Displays "Entregada" if all stages are finished, or the name of the active stage.

### Client Management
- Accessible only to roles with `clients` resource permission.
- Unified search by Document or Name.

---

## Build Configuration
- **Sass Policy**: Suppressed deprecation warnings for modern Bootstrap 5 builds.
- **Vite/Mix**: Standard Laravel bundling for JS/CSS assets.

For database schema and backend logic, see CONTEXT.md.
