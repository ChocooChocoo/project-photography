# 05 - SYSTEM ARCHITECTURE

[Back to start](00%20-%20START%20HERE.md) · Previous: [04 - COMBINED FINDINGS](04%20-%20COMBINED%20FINDINGS.md) · Next: [06 - DIAGRAMS](06%20-%20DIAGRAMS.md)

## How to read this note

The first half describes the arrangement found in the current working files. The second half describes the dependency-based direction for unfinished work. 🔵 means already present; ⭕ means proposed or not started; 🟨 means partly present. A proposal is not a claim that the application already does it.

## The arrangement today

### The parts

| Part | Status | What it is for | Serves |
|---|---|---|---|
| Accounts and access | 🔵 | Sign-in, verification, role routing, permissions, and studio scope. | Every portal |
| Studio onboarding and approval | 🔵 | Creates and reviews studio records and locations. | Owners and administrators |
| Marketplace and services | 🔵 | Presents provider categories, services, packages, and discovery. | Clients, owners, freelancers |
| Booking and payment | 🔵 | Creates bookings and tracks provider payment confirmation. | Clients, providers, finance |
| Assignment and cancellation | 🔵 | Assigns photographers, tracks response deadlines and assignment state, and notifies owners; cancellation outcomes remain separately blocked. | Owners and photographers |
| Galleries and reviews | 🔵 | Delivers draft, published, portfolio, and review material. | Owners, providers, clients, administrators |
| Studio people and permissions | 🔵 | Manages members, roles, permissions, and employee scope. | Owners and HR |
| Attendance and payroll | 🔵 | Handles attendance, leave, overtime, schedules, and payroll settings. | HR, finance, staff |
| Procurement | 🔵 | Moves requests through review, order, delivery, return, replacement, and payment. | Owners, HR, finance, staff |
| Subscriptions | 🟨 | Handles trial, expiry, grace, access restrictions, and lifecycle notices. | Owners and administrators |
| Notifications | 🔵 | Stores and exposes operational messages and read state. | Every signed-in role |
| Assistant | 🔵 | Provides photography-focused help with owner configuration and history. | Clients, owners, photographers |

### How work passes between them

People enter through accounts and are sent to a portal. A client reaches marketplace and services, creates a booking, and starts payment. Payment confirmation updates the shared booking record. The owner then assigns a photographer; the photographer performs the work and contributes gallery material. The owner or freelancer publishes the gallery, the client views it, and reviews or notifications close the visible journey. Around that shared record, owners and staff use people, attendance, payroll, procurement, and subscription parts.

### Where things are kept

The application uses relational `tbl_` records for users, roles, permissions, studios, bookings, payments, assignments, galleries, reviews, subscriptions, attendance, payroll, procurement, notifications, and assistant conversations. Public media is represented by relative paths on the explicit public disk. Provider credentials and application settings stay in environment-backed configuration.

### Where it touches the outside world

The application calls PayMongo and Stripe for payment-related work, Groq when the assistant provider is enabled, and mail transport for verification and operational notices. Webhook routes enter through the client booking controller. The material does not prove that every provider failure path has a final business policy.

### What holds the arrangement together

`BookingModel` is the cross-portal aggregate. Role middleware chooses the portal, while permission middleware and studio-aware role pivots limit actions. Subscription middleware sits at the owner and staff boundary. Services centralize payment, availability, attendance geolocation, procurement, dashboards, and assistant behavior. These are current arrangements observed in the code, not a proposed rewrite.

## The arrangement being proposed

### What changes and why

| Part | Status | Purpose of the change |
|---|---|---|
| Defect and evidence register | ⭕ | Make current failures, verification dates, and status evidence visible before new work. |
| Complete cancellation outcome flow | ❌ Blocked | Give a paid booking one approved operational and financial path after photographer cancellation. |
| Complete subscription renewal lifecycle | ❌ Blocked | Add the policy and provider events for renewal, failed payment, cancellation, and reactivation. |
| Public entry experience | ✅ | Delivered: a Bootstrap landing page serves the guest root with login and register entry points. |
| Broader regression and permission coverage | ✅ | Delivered: role and studio boundaries are regression-checked through the 49-check portal matrix, RBAC middleware tests, and photographer middleware JSON parity. |

### How work would pass between them

The proposed additions stay around the current core. A policy decision first defines cancellation and renewal outcomes. The implementation then records state, notification, financial, and audit consequences through existing booking, subscription, notification, and permission parts. Regression checks verify the role and studio boundaries before the feature is called finished.

### What it would take to get there

First preserve evidence and settle policy blockers. Next repair current defects and complete the already-partly-built booking, gallery, operational, and subscription slices. Then build approved discovery, automation, landing, cancellation, and renewal work in the phase order in [07 - DEVELOPMENT ROADMAP](07%20-%20DEVELOPMENT%20ROADMAP.md).

### What is being given up

This plan does not invent payment, refund, cancellation, screen, or permission policy where the supplied material is silent. It also does not treat every historical roadmap line as active work. Those omissions are deliberate and remain visible as open questions or evidence-only history.
