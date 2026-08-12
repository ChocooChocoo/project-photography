# 03 - CODE FINDINGS

[[00 - START HERE|Back to start]] · Previous: [[02 - DOCUMENT FINDINGS]] · Next: [[04 - COMBINED FINDINGS]]

## What was read

The current audit covered application models, controllers, middleware, requests, services, commands, routes, migrations, Blade views, configuration, and tests under `app`, `routes`, `database`, `resources/views`, `config`, and `tests`. Vendor code, generated output, caches, storage uploads, and environment secrets were excluded.

## The main parts

| Part | Evidence | Responsibility |
|---|---|---|
| Accounts and access | `app/Models/UserModel.php`, `app/Http/Middleware/` | Sign-in state, role routing, permissions, and studio scope. |
| Studio onboarding and approval | `app/Http/Controllers/StudioOwner/StudioController.php`, `Admin/StudioController.php` | Studio creation, location lookup, approval, rejection, and owner records. |
| Marketplace and services | `app/Http/Controllers/Client/`, `StudioOwner/ServicesController.php`, `Freelancer/ServicesController.php` | Provider discovery, categories, services, packages, and client entry points. |
| Booking and payment | `app/Models/BookingModel.php`, client booking controllers, PayMongo and Stripe services | Booking records, payment initialization, provider callbacks, and status changes. |
| Assignment and cancellation | owner booking controllers, photographer requests, assignment models | Photographer availability, assignment state, and operational changes. |
| Galleries and reviews | owner, freelancer, and client gallery controllers plus review controllers | Draft, upload, publish, portfolio, client viewing, and moderation surfaces. |
| Studio people and permissions | role, permission, member, employee controllers and `tbl_user_roles` | Staff membership, roles, permissions, and studio-scoped access. |
| Attendance and payroll | `AttendanceGeolocationService.php`, HR/finance controllers, payroll settings | Attendance checks, leave, overtime, schedules, and payroll configuration. |
| Procurement | `ProcurementWorkflowService.php` and procurement controllers | Review, purchase order, delivery, return, replacement, and payment states. |
| Subscriptions | subscription controllers, expiry/notification commands, access middleware | Trial and paid states, grace handling, notifications, and commercial access. |
| Notifications | `app/Http/Controllers/NotificationController.php`, notifiable helpers | Unread counts, recent records, read state, and route targets. |
| Assistant | `ChatbotController.php`, `ChatbotService.php`, chatbot requests | Photography-focused conversations, configuration, history, ownership, and guardrails. |

## How work travels through it

**Registration and entry**

1. The auth routes expose login, registration, verification, logout, and email verification in `routes/web.php`, lines 17–25.
2. Authenticated requests pass through the role-specific middleware groups in `routes/web.php`, beginning at lines 66, 130, 309, and the later client, freelancer, finance, and photographer groups.
3. Middleware checks the user's role and may redirect a person to the correct dashboard; permission middleware adds action-level checks.

**Booking and payment**

1. Client routes collect a chosen provider, service, dates, and booking details.
2. `BookingModel.php` is the cross-portal aggregate connecting client, package or service, payment, assignment, gallery, review, and notification records.
3. PayMongo and Stripe webhook routes are registered at `routes/web.php`, lines 13–14; controllers verify and apply provider messages to booking and payment records.
4. Owner routes then expose booking history, details, photographer availability, assignment, status, and completion actions around lines 156–164.

**Gallery delivery**

1. Owner and freelancer users upload or update gallery material through their portal controllers.
2. Owner routes expose draft details, upload, deletion, update, publish, and portfolio actions at `routes/web.php`, lines 167–176.
3. Client views read the published result; the code does not make a draft gallery public simply because files exist.

**Subscription access**

1. Scheduled commands handle expiry and lifecycle notices.
2. `EnforceStudioSubscriptionAccess.php` applies studio-scoped access decisions to owner and staff routes.
3. The current record reports seven-day grace, expiry, delisting, blocked commercial writes, retained history, and paid-booking fulfilment behavior; renewal and reactivation remain outside the delivered slice.

**Assistant conversation**

1. Authenticated chatbot routes expose configuration, start, message, history, end, and feedback operations at `routes/web.php`, lines 53–61.
2. Request objects validate ownership and message input before the service is called.
3. `ChatbotService.php` sanitizes user content, applies a fixed photography-focused system instruction, calls the provider when configured, and returns a safe fallback on failure.

## Where information is kept

| Information | Store | Evidence |
|---|---|---|
| Users, roles, permissions, bookings, payments, galleries, reviews, subscriptions, and operations | Relational application tables using the `tbl_` naming convention | `database/migrations/`, model files, and `app/Models/` |
| Public media references | Relative paths on the explicit public disk | `config/filesystems.php`, media services, and the media tests |
| Configuration and provider credentials | Environment-backed Laravel configuration | `.env.example`, `config/`, and service constructors; secret values were not read into the notes |
| Notifications and assistant history | Application records linked to users, studios, and conversations | notification and chatbot models/controllers |

## What it connects to outside itself

| Outside system | Purpose |
|---|---|
| PayMongo | Client booking payment and webhook confirmation. |
| Stripe | Subscription or payment provider integration and webhook path. |
| Groq | Configured assistant model provider when enabled. |
| Mail transport | Verification, lifecycle, booking, and operational messages where notifications are dispatched. |

## What is unfinished, switched off, or unused

| # | Finding | Evidence |
|---|---|---|
| U-01 | The owner Services page can call `json_decode` on an already-cast array and return HTTP 500. | `resources/views/owner/view-services.blade.php`; recorded as ISS-002 in the frozen technical progress record. |
| U-02 | A paid-booking photographer cancellation has no complete approved substitution, reschedule, refund, credit, notification, and audit outcome. | Frozen cancellation reference and current assignment code. |
| U-03 | Renewal, failed-renewal webhook behavior, cancellation beyond the historical refund window, and reactivation remain planned. | Frozen subscription reference and current subscription controllers/commands. |
| U-04 | Public landing-page work is documented but not approved as application implementation. | `docs/tasks/09.md` frozen copy. |
| U-05 | The historical Phase 3 task remains in progress even though several related routes and services already exist. | `docs/tasks/02.md` and current source. |

## Things worth flagging

The application contains more operational behavior than the old top-level index exposes: scoped permissions, staff portals, procurement states, lifecycle commands, assistant configuration, and role-specific routes all exist. Conversely, a route or controller proves that a path exists, not that every business rule is approved or every browser journey is healthy. Those distinctions are carried into [[04 - COMBINED FINDINGS]].
