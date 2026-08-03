# Architecture

> **In plain terms:** Each kind of user has its own workspace, but shared records such as bookings and payments connect the whole service. The current application keeps those areas within one Laravel system.

### ANL-003 — Portal boundaries

**Area:** Application architecture.  
**Observation:** Role middleware selects portal route groups and controllers. Studio-scoped authorization uses roles, permissions, and a studio-aware user-role pivot; controller actions can be gated by `CheckPermissionMiddleware`.  
**Evidence:** `routes/web.php`, `app/Models/UserModel.php`, `app/Http/Middleware/CheckPermissionMiddleware.php`, RBAC migrations and seeders.

### ANL-008 — Shared business services

**Area:** Business logic.  
**Observation:** Non-trivial shared logic is concentrated in services for dashboards, payments, availability, procurement, attendance geolocation, and the chatbot.  
**Evidence:** `app/Services/`.

### ANL-009 — Booking is the cross-portal aggregate

**Area:** Core workflow.  
**Observation:** `BookingModel` connects client booking, package selection, payment, assigned photographers, galleries, reviews, and related notifications. Booking types distinguish studio and freelancer services.  
**Evidence:** `app/Models/BookingModel.php`, booking controllers, and booking-related migrations.

See the [architecture diagram](../07-diagrams/architecture.md) and [data-flow diagram](../07-diagrams/data-flow.md).
