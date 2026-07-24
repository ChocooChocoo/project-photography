# Capstone B: System Scan, Revision Checklist & Automation Suggestions

> **This is an exploration and planning document only. No implementation is carried out.**
> Date: 2026-06-21

---

## Part 1 — System Overview

> Superseded by the dedicated analysis documents to avoid duplication. For the full system purpose,
> user roles/portals, and step-by-step system flow, see:
> - [`TECHNICAL ANALYSIS.md`](./TECHNICAL%20ANALYSIS.md) — technical detail, architecture, flowcharts
> - [`NON TECHNICAL ANALYSIS.md`](./NON%20TECHNICAL%20ANALYSIS.md) — plain-language walkthrough + glossary
>
> This document (`REVISION CHECKLIST AND RECOMMENDATIONS.md`) focuses on what's unique to it: the revision checklist,
> automation suggestions, deep-scan findings, and workflow improvement proposals below.

---

## Part 2 — Capstone B Revision Checklist

Legend: ✅ Exists | ⚠️ Partial | ❌ Not Yet Implemented | ❓ Unclear item

---

### General Errors / Fixes

| # | Item | Status | Notes |
|---|---|---|---|
| 1 | UI error, misalignment, overlapping elements | ⚠️ | No major structural bugs in code; runtime testing needed for actual visual regressions |
| 2 | Barangay error | ⚠️ | Known JSON encoding fragility in `StudioController::store()` — barangay JSON may silently malform; no hard null-guard |
| 3 | Fix online gallery (images not showing) | ❌ | `getThumbnailAttribute()` returns first image path but does not validate the path exists; image URLs may break |
| 4 | Fix online cancellation of photographer | ⚠️ | Cancellation logic exists (`canCancel()`) but only allows cancel at `assigned` or `confirmed` state — no formal "rejection with deadline" flow |

---

### Platform for Photographers with DDS — Studio Owner

| # | Item | Status | Notes |
|---|---|---|---|
| 5 | Add services | ✅ | `ServicesController` (CRUD) fully implemented |
| 6 | Income tracking | ⚠️ | `SystemRevenueModel` records per-payment revenue; no dedicated income report or per-service breakdown visible to the owner |
| 7 | Add amount for specific services | ❌ | Pricing is **package-level only** (`tbl_packages.package_price`); individual services (`tbl_services`) have no price field |

---

### Hierarchy / System Process

| # | Item | Status | Notes |
|---|---|---|---|
| 8 | RBAC | ✅ | Full studio-scoped RBAC: `tbl_roles`, `tbl_permissions`, `tbl_user_roles` (with `studio_id`), `hasPermission()`, `CheckPermissionMiddleware` |
| 9 | Freelancer model | ✅ | Freelancer portal fully implemented |
| 10 | Subscription (middleman / sustainability) | ✅ | `tbl_subscription_plans` with tiers (basic/premium/enterprise), monthly/yearly billing, commission rate, max studios/staff |
| 11 | Free trial for subscription | ❌ | No free-trial period implemented; 1st studio is free to register but that's not a time-limited trial |
| 12 | User types: Client, Customer, Studio Owner | ✅ | All three implemented (`client`, `owner` roles) |
| 13 | Budget category (not just for packages) | ⚠️ | `BudgetController` exists for clients to track personal spending by category, but no studio-side budget category system |

---

### Integrated Features

| # | Item | Status | Notes |
|---|---|---|---|
| 14 | DDS (Distance-based Discovery System) | ❌ | **Not implemented.** Studio lat/lng exists for attendance geofencing only, not client-facing discovery. Only static municipality/barangay filtering. |
| 15 | Booking | ✅ | Full booking flow implemented |
| 16 | Online Gallery | ⚠️ | Gallery models and upload exist; image display bug present (see item 3) |
| 18 | Photo Delivery | ⚠️ | Gallery upload is the delivery mechanism, but no download button, no delivery status, no watermarking |
| 19 | Online in Cavite (location scope) | ⚠️ | Location data includes Cavite municipalities/barangays, but platform has no Cavite-specific discovery logic |
| 20 | Sustainability (subscription revenue) | ✅ | Platform takes commission per booking payment (`SystemRevenueModel`); subscription model for recurring revenue |

---

### User Roles

| # | Item | Status | Notes |
|---|---|---|---|
| 21 | System Admin | ✅ | Full admin portal |
| 22 | Freelancers | ✅ | Full freelancer portal |
| 23 | Business Owner | ✅ | Owner portal with comprehensive studio management |
| 24 | Client | ✅ | Client portal with booking, payment, gallery, reviews, budget |
| 25 | QA for Photo | ❌ | No dedicated "photo QA" role. Closest equivalent: client confirms gallery received; no review/approval step for photo quality |

---

### Design Principle: Owner Self-Management

| # | Item | Status | Notes |
|---|---|---|---|
| 26 | Business owner can manage system without dev team | ✅ | Owner can configure RBAC, payroll settings, chatbot intents, schedules, services, packages — no dev involvement required |

---

### Geolocation and Studio Discovery

| # | Item | Status | Notes |
|---|---|---|---|
| 27 | Geolocation of studio visible to client | ❌ | `tbl_studios.attendance_latitude/longitude` exists but is used for employee attendance only; not rendered on any client-facing map |
| 28 | Client address geolocation (accurate pin) | ❌ | Clients enter barangay/municipality by dropdown; no GPS pin or map input for on-location bookings |
| 29 | DDS for finding nearby studios | ❌ | Same as item 14 — not implemented |

---

### Booking and Assignment

| # | Item | Status | Notes |
|---|---|---|---|
| 30 | "Approbnate/approbment" and tools | ❓ | Likely means "appointment" tools — booking system exists. Item is too unclear to fully evaluate. |
| 31 | Assign equipment (camera) to photographer | ❌ | No equipment/camera model. Procurement models exist for studio assets but not for booking-specific gear assignment. |
| 32 | Photographer rejection deadline | ⚠️ | Photographers can cancel at `assigned` or `confirmed` status only. No explicit "must accept/reject by [date]" deadline field or notification. |
| 33 | Calendar showing available and reserved dates visible to client | ⚠️ | `StudioScheduleModel` tracks operating days/hours; `checkAvailability` endpoint exists; no dedicated calendar UI component confirmed in Blade templates |
| 34 | Long-term and short-term booking | ❌ | All bookings are single-event. No recurring booking, retainer, or long-term engagement model. |

---

### Gallery and Media

| # | Item | Status | Notes |
|---|---|---|---|
| 35 | Authenticated proof gallery belongs to them | ❌ | No certification, digital signature, or ownership proof mechanism |
| 36 | "Kiosk" | ❓ | Item is unclear/illegible in the revision notes — cannot evaluate |
| 37 | Icon image error | ⚠️ | Tabler Icons used correctly in code; no broken icon references in code review. May be a runtime/CDN issue. |
| 38 | Text overflowing its border | ⚠️ | `max:255` fields on studio name with no truncation CSS confirmed. Likely a runtime visual bug needing browser testing. |
| 39 | Studio image backup / "sneak peek" of service (multiple images) | ⚠️ | Studios have a logo; packages do not have dedicated selling images/videos. No multi-image preview per service. |
| 40 | Online gallery — image not showing | ❌ | Bug confirmed in code: `getThumbnailAttribute()` returns stored path without validating file existence; JSON image array paths may not resolve correctly. |
| 41 | Per service, a selling image/video | ❌ | `tbl_services` has no media field. `tbl_packages` also has no image/video field. No visual selling material per service or package. |

---

### Account and Verification

| # | Item | Status | Notes |
|---|---|---|---|
| 42 | Multiple accounts under one email | ❌ | Not allowed — email uniqueness enforced (`unique:tbl_users,email`) |
| 43 | Contact between business/freelancer and platform | ⚠️ | Inquiry system and chatbot exist; no direct email/in-app messaging from provider to platform |
| 44 | Contact between business/freelancer and client | ⚠️ | Chatbot widget exists for client → studio; no direct messaging channel |
| 45 | Business verification by admin (permit check) | ✅ | `business_permit` + `owner_id_document` required on registration; admin approve/reject workflow with rejection notes |
| 46 | Business portfolio | ⚠️ | Online gallery serves as portfolio; no dedicated "portfolio" section with curated work display |
| 47 | Owner should have a profile picture | ⚠️ | `owner_profile_photo` upload exists in studio registration form; `tbl_users.profile_photo` field exists; not validated as strictly required |
| 48 | Unable to create a studio — barangay error | ⚠️ | Barangay JSON encoding bug confirmed in `StudioController` (lines 202-237); silent failure on malformed JSON possible |

---

### Payment

| # | Item | Status | Notes |
|---|---|---|---|
| 49 | Downpayment should be optional | ✅ | Freelancers: `deposit_policy = 'not_required'` forces `full_payment`. Studios: configurable `downpayment_percentage`. |
| 50 | Flexible option where downpayment is not required | ✅ | `payment_type` field supports `full_payment`; client can choose at booking time if studio allows it |

---

### Data

| # | Item | Status | Notes |
|---|---|---|---|
| 51 | Data validation | ✅ | Comprehensive validation: `RegisterRequest`, `BookingController`, `StudiosModel::rules()`, `UpdateBookingStatusRequest` all have thorough rules |

---

## Part 3 — Automation Suggestions

> Planning only — no implementation.

### Currently Manual Processes → Suggested Automation

| Manual Process | Current State | Suggested Automation |
|---|---|---|
| **Photographer Assignment** | Owner manually assigns a photographer to each booking | Auto-assign based on availability (via `PhotographerAvailabilityService`) — suggest available photographers ranked by schedule match |
| **Rejection Deadline** | No deadline; owner must monitor unaccepted assignments manually | Auto-cancel assignment and re-notify owner if photographer hasn't responded within N hours; configurable deadline per studio |
| **Booking Status Progression** | Owner manually moves bookings through statuses | Auto-transition `confirmed → in_progress` on event date/start time; auto-prompt for gallery upload after event end time |
| **Payroll Generation** | HR manually initiates payroll generation per period | Scheduled payroll generation trigger based on payroll settings (e.g., every 15th and 30th) |
| **Gallery Upload Reminder** | No reminder; photographers may forget to upload | Auto-notify assigned photographer if gallery not uploaded within N hours after booking `completed_at` |
| **Subscription Renewal** | Manual; owner must re-subscribe | Automated renewal reminder emails before expiry; auto-renew with stored payment method if Stripe is used |
| **Business Verification Queue** | Admin manually checks pending studios | Email/notification alert to admin when new studio submitted; SLA reminder if unreviewed after 48h |
| **Studio Discovery by Location** | Client manually selects municipality/barangay | Implement GPS-based "Find studios near me" using existing studio lat/lng fields — browser geolocation API → Haversine sort |
| **Income Report per Service** | No automated report; owner reads raw booking data | Auto-generate weekly/monthly income summary per service category from `SystemRevenueModel` |
| **Package Selling Media** | Owner has no place to attach visual per service | Add image/video upload to packages (`tbl_packages`) — auto-display as "sneak peek" on client marketplace |
| **Client Photo Download** | Client can only view gallery in browser | Add batch download (ZIP) for completed gallery; auto-expire link after 30/60 days |
| **Equipment Prep Reminder** | No equipment assignment; owner manages manually | Add equipment checklist per booking assignment — photographer confirms gear before event day |

---

## Part 3B — Deep Scan: Additional Findings

> Second-pass scan findings not covered in the initial review.

---

### Payment Webhooks — Critical Gap

| Item | Status | Notes |
|---|---|---|
| PayMongo webhook handler | ❌ | No `/webhook/paymongo` route exists. Payment verification is client-redirect only — if the user closes the browser after paying, the payment may never be confirmed in the system. |
| Stripe webhook handler | ❌ | `StripeService::verifyWebhookSignature()` is implemented but never called — no webhook route consumes it. Same client-redirect dependency as PayMongo. |
| Async payment confirmation | ❌ | No server-side payment event handling. All payment status updates depend on the client landing on the success/failed redirect page. |

---

### Events, Listeners, Jobs — All Missing

| Item | Status | Notes |
|---|---|---|
| `app/Events/` directory | ❌ | Does not exist. No domain events fired anywhere. |
| `app/Listeners/` directory | ❌ | Does not exist. All business logic is synchronous in controllers. |
| `app/Jobs/` directory | ❌ | Does not exist. No queued jobs — emails, notifications, and payroll generation all run synchronously in the request cycle. |
| Scheduled commands | ⚠️ | One command exists: `EscalateOverdueProcurementRequestsCommand` — but it must be run manually; no scheduler registration confirmed. |

---

### Authorization Policies — Missing

| Item | Status | Notes |
|---|---|---|
| `app/Policies/` directory | ❌ | Does not exist. No resource-level authorization. |
| Studio-scoped resource guards | ❌ | No check that a user editing a booking, gallery, or package actually owns the studio it belongs to. Authorization is role-based only (can this role do this action?) not resource-based (does this user own this record?). |

---

### Notification Coverage Gaps

Only 6 notification types are defined in the `Notifiable` trait. Large gaps exist:

| Missing Notification | Trigger |
|---|---|
| Booking completed / gallery ready | When owner marks booking complete |
| Gallery published to client | When owner publishes gallery (after Phase 3.2 is added) |
| Review received | When client leaves a review on a studio/freelancer |
| Payment failed | When PayMongo/Stripe payment fails |
| Budget exceeded | When a booking total exceeds the client's set budget |
| Photographer assigned (to client) | When owner assigns a photographer to a confirmed booking |
| Assignment deadline approaching | N hours before `response_deadline` on assignment |
| Subscription expired | When studio subscription lapses |
| Pending booking about to expire | N hours before `expires_at` (after expiry feature is added) |

---

### Reviews & Ratings — No Moderation

| Item | Status | Notes |
|---|---|---|
| Review moderation | ❌ | Reviews are published immediately with no approval step, no admin panel, no flagging, no soft-delete. Once submitted, a review is permanent. |
| Rating aggregation stored | ❌ | Average rating is calculated on-the-fly per request (`.avg('rating')`). No cached/stored aggregate on `tbl_studios` or `tbl_freelancer_profile`. Will slow down as reviews grow. |

---

### Budget Tracking — Not Connected to Bookings

| Item | Status | Notes |
|---|---|---|
| Auto-deduct from bookings | ❌ | `tbl_client_budget.spent_amount` field exists but is never updated by the booking/payment flow. Budget is purely a manual planning tool with no real spending data. |
| Budget alert on booking | ❌ | No check whether a booking's total exceeds the client's budget for that category before or after booking. |

---

### Attendance Geolocation — Validation Gap

| Item | Status | Notes |
|---|---|---|
| Geolocation stored on check-in | ✅ | Latitude, longitude, and distance stored on each attendance record |
| Geolocation enforced on check-in | ⚠️ | `AttendanceGeolocationService` calculates distance using Haversine but it is unclear from the scan whether the check-in is actually blocked if outside the radius, or only flagged. Needs runtime verification. |

---

### Authentication — Details Missed in First Scan

| Item | Status | Notes |
|---|---|---|
| Email verification on registration | ✅ | 24-hour token sent via email; login blocked until verified |
| Password reset | ⚠️ | No custom implementation found; may rely on default Laravel `CanResetPassword` trait — not confirmed |
| Social login | ❌ | Not implemented |

---

### Studio Member vs Studio Photographer — Clarified

These are two distinct concepts that were conflated in the first scan:

| | Studio Member | Studio Photographer |
|---|---|---|
| Table | `tbl_studio_members` | `tbl_studio_photographers` |
| Who | An existing **freelancer** invited to collaborate | A **salaried employee** created by the owner |
| Payroll | None | Full payroll, attendance, leave, OT |
| Flow | Owner invites → Freelancer accepts/rejects | Owner creates account directly |
| Access | Freelancer portal (limited studio context) | Studio Photographer portal |

---

### Test Coverage — Very Limited on Core Features

| Area | Covered? |
|---|---|
| Chatbot | ✅ ~50% of all tests |
| Payroll routes | ✅ Route-level only |
| Procurement routes | ✅ Route-level only |
| Dashboard access | ✅ Route-level only |
| Booking flow | ❌ Zero tests |
| Payment processing | ❌ Zero tests |
| Rating/review system | ❌ Zero tests |
| Online gallery | ❌ Zero tests |
| Authentication | ❌ Zero tests |
| Leave/overtime | ❌ Zero tests |
| Notifications | ❌ Zero tests |
| Geolocation validation | ❌ Zero tests |
| Subscription renewal | ❌ Zero tests |
| RBAC/permissions | ❌ Zero tests |

---

## Part 4 — Core Workflow & Process Improvement Suggestions

> Beyond automation — structural changes to the flow itself that would improve reliability, trust, and clarity.

---

### 1. The Booking Window Has a Trust Gap

**Current flow:** Client pays downpayment → Owner assigns photographer → Client finds out who their photographer is (maybe days later)

**Problem:** The client pays money before knowing who will actually shoot their event. If they're unhappy with the assigned photographer, there's no recourse — they've already paid.

**Suggestion:** Show the client the assigned photographer's profile (name, specialization, gallery samples) after assignment and give them a short window (e.g., 24h) to acknowledge or flag a concern before the booking is fully locked in. This doesn't require a full rejection flow — just a confirmation acknowledgment.

---

### 2. The Cancellation Flow Only Goes One Way

**Current flow:** Client can cancel (pending status, 24h+ notice). Photographer can cancel assignment (assigned/confirmed only).

**Missing:** What happens when the **studio cancels on the client**? There's no studio-initiated booking cancellation path with client notification and refund logic. If a studio cancels a confirmed booking the day before the event, the system has no structured response — no email to the client, no refund trigger, no rebooking prompt.

**Suggestion:** Add a studio-cancellation path with mandatory reason, client notification, and a payment reversal hook (even if manual refund for now).

---

### 3. Gallery is Locked to Bookings — Studios Can't Show Portfolio Work

**Current:** Gallery only exists per completed booking (`booking_id` required on `tbl_studio_online_gallery`). A studio with 50 past shoots can only show work from bookings made through this platform.

**Problem:** New studios on the platform have zero gallery content until their first booking completes — they look empty to clients even if they've been operating for years offline.

**Suggestion:** Add a separate **portfolio gallery** (no `booking_id`) where studios can upload curated work as their showcase. Keep the booking gallery separate. This also solves the "sneak peek" item in the revision list (item 39).

---

### 4. No Pending Booking Expiry

**Current:** Bookings stay in `pending` forever unless the owner manually confirms or the client cancels.

**Problem:** A client creates a booking and waits. The owner forgets or is busy. The client is stuck — their event date is approaching and they don't know if it's confirmed.

**Suggestion:** Auto-expire pending bookings after N days (configurable per studio) with notification to both parties. Let the client re-book or look elsewhere. This is a core UX trust issue, not just an automation win.

---

### 5. Freelancer and Studio Discovery Are Not Separated Enough

**Current:** The client dashboard has a toggle for studio vs freelancer, but the filtering and ranking work the same way for both.

**Problem:** A client looking for a solo wedding photographer has fundamentally different needs from someone booking a full studio. Freelancers have no staff, no physical location, different pricing models, and their availability works differently (they ARE the photographer — no assignment step).

**Suggestion:** The freelancer booking flow should skip the "photographer assignment" step entirely — the freelancer confirms the booking directly, no owner-assigns-photographer middle layer. The UI discovery experience should also make the distinction clearer upfront before the client even starts filtering.

---

### 6. No Photo Review Step Before Client Sees Gallery

**Current:** Photographer uploads → booking marked complete → client views gallery. Done.

**Problem:** The client gets whatever the photographer uploaded, with no quality check. For a photography business, this is a significant gap. The QA for Photo role was in the revision list as missing (item 25).

**Suggestion:** Add a gallery review stage: photographer uploads → owner/QA reviews and publishes → client views. The gallery would have a `draft/published` status. This adds accountability and is directly relevant to the capstone's photography focus.

---

### 7. Per-Service Pricing Has No Discovery Entry Point

**Current:** Pricing is entirely at the package level. Services (`tbl_services`) are just name labels — no price. Studios have a `starting_price` field but it's not surfaced per service.

**Problem:** When a client asks "how much for a birthday shoot?", the answer is buried inside the booking form. There's no pre-booking price signal per service type on the marketplace.

**Suggestion:** Add a `starting_from` price field to services. It's not the full package price — it's a discovery anchor. Client sees "Birthday Photography — from ₱3,500" on the studio card, then clicks to see full packages. This improves conversion before the booking form even opens.

---

### 8. Subscription Rank vs Rating Rank Is Opaque to Clients

**Current:** `priority_level` in subscription plans affects studio ranking in discovery. Higher-tier studios appear higher. But this is invisible to clients — they don't know why Studio A appears above Studio B.

**Problem:** This can feel unfair if a lower-rated but higher-paying studio ranks above a better-reviewed one. It risks platform credibility.

**Suggestion:** Either show a "Featured" or "Premium Studio" badge on promoted listings so clients understand the ranking, or restructure ranking to weight ratings and reviews more heavily than subscription tier. The platform's credibility with clients depends on discovery feeling merit-based.

---

### 9. On-Location Booking Address Is Too Vague

**Current:** Clients enter barangay/city for on-location shoots via dropdown. The `multiple_locations` field handles multi-location shoots.

**Problem:** For an on-location event (e.g., a wedding at a specific venue), "barangay + city" is not precise enough for the photographer to navigate to. The dropdown was designed for studio discovery filtering, not event venue addressing.

**Suggestion:** Add a freeform `venue_address` field (street + landmark notes) for on-location bookings, separate from the location dropdowns. Marketplace filtering stays dropdown-based; the actual event address in the booking form should allow freeform input and eventually a map pin (ties into the geolocation gap in items 27–29).

---

### 10. No Dispute or Revision Path After Booking Completion

**Current:** Booking reaches `completed` → terminal state. Client views gallery. End.

**Problem:** If the client receives photos that don't match what was agreed, there's no structured path — they can only leave a star rating. No formal revision request or re-upload flow exists.

**Suggestion:** Add a short post-completion window (e.g., 7 days) where the client can flag the gallery for revision. This reopens gallery upload for the photographer and notifies the owner. After the window closes, the booking locks permanently. This protects both sides.

---

### Summary Table

| # | Gap | Impact | Effort |
|---|---|---|---|
| 1 | Photographer identity revealed only after payment | Client trust | Low |
| 2 | Studio-side cancellation has no structured path | Data integrity, client protection | Medium |
| 3 | Gallery locked to bookings — no portfolio for new studios | Onboarding, discoverability | Low |
| 4 | Pending bookings never expire | UX trust, booking limbo | Low |
| 5 | Freelancer booking flow not distinct from studio flow | Clarity, UX accuracy | Medium |
| 6 | No photo QA/review step before client sees gallery | Core photography value | Medium |
| 7 | Per-service starting price missing from discovery | Conversion before booking form | Low |
| 8 | Subscription rank vs rating rank opaque to clients | Platform credibility | Low |
| 9 | On-location booking address too vague for navigation | Operational accuracy | Low |
| 10 | No post-completion dispute or revision request path | Client satisfaction, accountability | Medium |

**Low effort, high value (start here):** Items 1, 3, 4, 7, 8, 9
**Medium effort, core quality:** Items 2, 5, 6, 10

---

## Deliverable Summary

This document covers:
1. **System Scan** — 7 user roles, full portal breakdown, correct system flow from registration to photo delivery
2. **Revision Checklist** — 51 items evaluated: **15 ✅ Exist**, **16 ⚠️ Partial**, **17 ❌ Not Yet Implemented**, **3 ❓ Unclear**
3. **Automation Suggestions** — 12 manual processes identified with concrete automation paths
3B. **Deep Scan Findings** — Additional gaps: payment webhooks missing, no Events/Jobs/Policies, notification gaps, no review moderation, budget not connected to spending, test coverage near-zero on core features

### Priority Flags for Capstone B

**Critical bugs to fix first:**
- Item 3 / 40: Online gallery images not showing (path validation bug)
- Item 2 / 48: Barangay JSON encoding error blocking studio creation

**Features most impactful for Capstone B scope:**
- Item 14 / 27 / 29: DDS / geolocation studio discovery for clients
- Item 41: Per-service/package selling image or video
- Item 34: Long-term booking support
- Item 32: Photographer rejection deadline
- Item 33: Visual booking calendar for clients
- Item 25: QA for Photo role

**Quick wins (low effort, high value):**
- Item 7: Add price field to `tbl_services`
- Item 47: Enforce `owner_profile_photo` as required
- Item 11: Add free trial flag to subscription plans
- Item 46: Dedicated portfolio section (curated gallery subset)
