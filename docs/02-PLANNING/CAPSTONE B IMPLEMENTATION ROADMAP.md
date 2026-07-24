# Capstone B: Implementation Roadmap

> Execution guide derived from [`../01-ANALYSIS/REVISION CHECKLIST AND RECOMMENDATIONS.md`](../01-ANALYSIS/REVISION%20CHECKLIST%20AND%20RECOMMENDATIONS.md).
> Phases are ordered by dependency — each phase must be stable before the next begins.
> Do not implement phases out of order. Each phase has its own checklist.

---

## Phase Overview

| Phase | Label | Focus | Why First |
|---|---|---|---|
| 1 | **Stabilize** | Critical bugs | Broken things block all testing of everything else |
| 2 | **Complete** | Partial features | Finish what exists before adding what doesn't |
| 3 | **Core New Features** | Missing but foundational | Required for the system to make sense end-to-end |
| 4 | **Workflow Improvements** | Structural flow fixes | Improves trust and clarity once core is solid |
| 5 | **Advanced Features** | DDS, equipment, long-term booking | Builds on stable core |
| 6 | **Automation** | Notifications, triggers, scheduling | Only useful after the flows they automate are correct |
| 7 | **Authorization & Tests** | Policies, core feature test coverage | Security and confidence layer — after all features are stable |

---

## Phase 1 — Stabilize (Fix What's Broken)

> Nothing else should be tested until these are resolved. These are blockers.

### 1.1 Fix Barangay JSON Encoding Bug

**Problem:** `StudioController::store()` decodes barangay JSON from `tbl_locations` and validates the selected barangay against it. If the JSON is malformed or null, it silently falls back and may allow invalid data or reject valid data with a confusing error.

**Files to touch:**
- `app/Http/Controllers/StudioOwner/StudioController.php` (lines ~202–237)
- `app/Models/StudioOwner/StudiosModel.php`

**Steps:**
1. Add a null/empty guard before JSON decode — return a clear validation error if location has no barangay data
2. Replace silent `json_decode` fallback with a strict check: if `json_last_error() !== JSON_ERROR_NONE`, throw a readable validation message
3. Add a seed/data check: ensure all `tbl_locations` rows have valid JSON in the barangay column

**Done when:** Studio creation succeeds end-to-end with a valid Cavite barangay, and fails with a clear message for an invalid one.

---

### 1.2 Fix Online Gallery Images Not Showing

**Problem:** `getThumbnailAttribute()` on `StudioOnlineGalleryModel` returns the first path in the `images` JSON array without checking if the file exists on disk or if the URL resolves correctly.

**Files to touch:**
- `app/Models/StudioOwner/StudioOnlineGalleryModel.php`
- `app/Models/Freelancer/FreelanceOnlineGalleryModel.php`
- Any Blade template rendering gallery thumbnails in `resources/views/client/online-gallery/`

**Steps:**
1. Fix `getThumbnailAttribute()`: decode the images JSON array, return the first valid path wrapped with `Storage::url()` or `asset('storage/...')` — whichever is used elsewhere in the project
2. Check whether images are stored under `storage/app/public/` and that `php artisan storage:link` has been run
3. In gallery index views, guard against empty/null thumbnail: show a placeholder image if `images` array is empty or thumbnail is null
4. Test: upload an image to a gallery, then view it as a client — confirm it renders

**Done when:** Client gallery view shows uploaded images without broken image icons.

---

### 1.3 Fix UI Misalignment & Text Overflow

**Problem:** Known visual regressions — text overflowing borders (studio name field, max 255 chars with no truncation CSS), potential icon rendering issues.

**Files to touch:**
- Blade views in `resources/views/client/`, `resources/views/owner/`
- Any relevant Tailwind classes on card/badge/name elements

**Steps:**
1. Run the app and visually scan: studio cards, booking cards, gallery cards, owner dashboard
2. Add `truncate` or `line-clamp-2` Tailwind class to any text that overflows its container
3. Verify Tabler Icons CDN is loading — check browser network tab for failed icon font requests
4. Fix any icon that shows as a square/missing glyph

**Done when:** All visible text is contained in its UI element; no broken icon glyphs.

---

### 1.4 Enforce Owner Profile Photo as Required

**Problem:** `owner_profile_photo` upload exists in the studio registration form but is not validated as required. Studios can register with no owner photo.

**Files to touch:**
- `app/Models/StudioOwner/StudiosModel.php` (rules method)
- Studio registration Blade form in `resources/views/owner/`

**Steps:**
1. Add `'owner_profile_photo' => 'required|image|mimes:jpg,jpeg,png|max:3072'` to `StudiosModel::rules()` for the `create` scenario
2. Confirm the form shows a clear asterisk/required indicator for this field
3. Test: attempt studio creation without a photo — expect validation error

**Done when:** Studio creation fails with a clear error if owner profile photo is missing.

---

### 1.5 Fix Payment Verification — Add Webhook Handlers

**Problem (Critical):** Payment verification depends entirely on the client landing on the redirect success/failed URL. If a user pays and closes the browser, the payment is never confirmed in the system. `StripeService::verifyWebhookSignature()` exists but no route calls it. No PayMongo webhook handler exists at all.

**Files to touch:**
- `routes/web.php` — add webhook routes (outside auth middleware)
- New: `app/Http/Controllers/Webhook/PaymongoWebhookController.php`
- New: `app/Http/Controllers/Webhook/StripeWebhookController.php`
- `app/Models/PaymentModel.php`, `app/Models/BookingModel.php`
- `app/Http/Middleware/VerifyCsrfToken.php` — exclude webhook routes from CSRF

**Steps:**
1. Register two unprotected POST routes:
   - `POST /webhook/paymongo` → `PaymongoWebhookController@handle`
   - `POST /webhook/stripe` → `StripeWebhookController@handle`
2. Exclude both from CSRF in `VerifyCsrfToken::$except`
3. `PaymongoWebhookController`: verify PayMongo signature from header, parse event type (`payment.paid`, `payment.failed`), update `tbl_payments` and `tbl_bookings` accordingly
4. `StripeWebhookController`: call existing `StripeService::verifyWebhookSignature()`, handle `checkout.session.completed` and `payment_intent.payment_failed` events
5. On payment confirmed via webhook: set `payment_status = 'paid'`, notify client and owner

**Done when:** A payment confirmed in PayMongo/Stripe automatically updates the booking even if the user never returns to the success page.

---

### 1.6 Register Procurement Escalation in Scheduler

**Problem:** `EscalateOverdueProcurementRequestsCommand` exists but is not registered in the Laravel scheduler — it must be run manually and never fires automatically.

**Files to touch:**
- `app/Console/Kernel.php`

**Steps:**
1. Add `$schedule->command('procurement:escalate-overdue')->dailyAt('08:00')` to `Kernel::schedule()`
2. Confirm the command's handle method logs output
3. Ensure `php artisan schedule:run` is in the server crontab (`* * * * * php artisan schedule:run`)

**Done when:** Overdue procurement requests are automatically escalated every morning without manual intervention.

---

## Phase 2 — Complete (Finish What's Partial)

> These features exist in code but are incomplete in functionality or missing a key piece.

### 2.1 Add Starting Price to Services (Per-Service Price Anchor)

**Problem:** `tbl_services` has no price field. Pricing only exists at the package level. Clients browsing the marketplace have no price signal before entering the booking flow.

**Files to touch:**
- `database/migrations/` — new migration adding `starting_from` (decimal, nullable) to `tbl_services` and `tbl_freelancer_services`
- `app/Models/StudioOwner/ServicesModel.php`
- `app/Models/Freelancer/ServiceModel.php`
- `app/Http/Controllers/StudioOwner/ServicesController.php`
- `app/Http/Controllers/Freelancer/ServicesController.php`
- Service create/edit Blade forms
- Client marketplace/studio profile view — display "from ₱X" per service

**Steps:**
1. Write and run migration: `starting_from` decimal(10,2) nullable on both service tables
2. Add `starting_from` to fillable arrays and form validation rules (nullable, numeric, min:0)
3. Add the field to the owner and freelancer service create/edit forms
4. On the client-facing studio profile page, show "from ₱[starting_from]" beside each service name
5. If `starting_from` is null, show "Price varies" or omit the price line

**Done when:** Owner can set a starting price per service; client sees it on the studio profile before booking.

---

### 2.2 Owner Income Report (Per-Service/Per-Period Breakdown)

**Problem:** `SystemRevenueModel` records revenue per payment but there's no owner-facing report. The owner cannot see income broken down by service, period, or booking.

**Files to touch:**
- `app/Services/Dashboard/OwnerDashboardService.php`
- `app/Http/Controllers/StudioOwner/DashboardController.php`
- Owner dashboard Blade view in `resources/views/owner/dashboard/`

**Steps:**
1. Add a method to `OwnerDashboardService` that queries `SystemRevenueModel` filtered by `studio_id` + date range — group by booking category/service type
2. Expose this via a filter on the owner dashboard (this week / this month / custom range)
3. Display as a simple table: Service Category | Bookings | Total Revenue | Platform Fee | Net Income
4. Add to the existing dashboard CSV export

**Done when:** Owner can see a breakdown of income by service category and export it.

---

### 2.3 Photographer Assignment Deadline (Rejection Window)

**Problem:** When an owner assigns a photographer, there is no deadline for the photographer to accept or reject. The assignment sits indefinitely in `assigned` status.

**Files to touch:**
- `database/migrations/` — add `response_deadline` (timestamp, nullable) to `tbl_booking_assigned_photographers`
- `app/Models/StudioOwner/BookingAssignedPhotographerModel.php`
- `app/Http/Controllers/StudioOwner/BookingController.php` (assignment creation)
- `app/Http/Controllers/StudioPhotographer/AssignedBookingController.php` (accept/cancel)

**Steps:**
1. Migration: add `response_deadline` timestamp (nullable) to `tbl_booking_assigned_photographers`
2. When creating an assignment, set `response_deadline` to `now() + N hours` (default 24h, configurable later)
3. In `AssignedBookingController`, show the deadline to the photographer on their assignment list
4. Add a `isPastDeadline()` method to the model: returns true if `now() > response_deadline && status == 'assigned'`
5. Owner booking view: flag overdue assignments in the UI (e.g., "Awaiting response — deadline passed")

**Done when:** Assignments show a response deadline; owner can see which are overdue; photographer sees the countdown.

---

### 2.4 Visual Booking Calendar for Client

**Problem:** `StudioScheduleModel` and a `checkAvailability` endpoint exist, but there is no calendar UI shown to clients when browsing a studio or creating a booking. Clients cannot visually see available vs reserved dates.

**Files to touch:**
- `resources/views/client/booking/` (booking form Blade)
- `resources/js/` (add calendar JS — use a lightweight library already available, or vanilla JS)
- `app/Http/Controllers/Client/BookingController.php` (`getCalendarAvailability` endpoint — confirm it exists and returns correct data)

**Steps:**
1. Confirm `getCalendarAvailability` returns the right shape: `{ available: [...dates], booked: [...dates] }`
2. In the booking form, replace or augment the date input with a calendar picker that colors dates: green (available), red (fully booked), grey (not an operating day)
3. Wire the calendar to call `getCalendarAvailability` on studio/freelancer selection
4. On date select, auto-populate the event date field

**Done when:** Client sees a colored calendar when choosing a date; cannot select booked or non-operating dates.

---

### 2.5 Studio-Side Booking Cancellation Path

**Problem:** Only clients can cancel bookings (pending status, 24h+ notice). There is no structured path for the studio to cancel a confirmed booking. If it happens, there is no client notification and no refund hook.

**Files to touch:**
- `app/Http/Controllers/StudioOwner/BookingController.php`
- `app/Http/Requests/UpdateBookingStatusRequest.php`
- Notification: new `BookingCancelledByStudioNotification` (or reuse existing notification class)
- Owner booking management Blade view

**Steps:**
1. Add a "Cancel Booking" action on the owner's booking detail view (only shown for `pending` and `confirmed` bookings)
2. Require a mandatory cancellation reason (min 20 chars, text area)
3. On confirm: set booking status to `cancelled`, store `cancellation_reason`, set `cancelled_by = 'studio'`
4. Send notification to client: "Your booking [ref] has been cancelled by [studio name]. Reason: [reason]. Please contact us or re-book."
5. Flag the payment for manual refund review (no auto-refund yet — that is Phase 6)

**Done when:** Owner can cancel a confirmed booking with a reason; client receives a notification.

---

### 2.6 Portfolio Gallery (Independent of Bookings)

**Problem:** `tbl_studio_online_gallery` requires a `booking_id`. New studios have no gallery until their first booking completes on this platform, making their profile look empty to clients.

**Files to touch:**
- `database/migrations/` — make `booking_id` nullable on `tbl_studio_online_gallery`; add `gallery_type` enum (`booking`, `portfolio`)
- `app/Models/StudioOwner/StudioOnlineGalleryModel.php`
- `app/Http/Controllers/StudioOwner/OnlineGalleryController.php`
- Owner gallery Blade views
- Client-facing studio profile: show portfolio gallery tab

**Steps:**
1. Migration: make `booking_id` nullable; add `gallery_type` enum defaulting to `'booking'`
2. Add a "Portfolio" section in the owner gallery management page — separate from booking galleries
3. Allow owner to upload portfolio images without a booking reference (`gallery_type = 'portfolio'`)
4. On the client-facing studio profile, show a "Portfolio" tab with portfolio galleries and a "Past Work" tab showing completed booking galleries
5. Freelancer equivalent: same change to `tbl_freelancer_online_gallery`

**Done when:** Owner can upload portfolio images without a booking; client sees them on the studio profile.

---

### 2.7 Pending Booking Auto-Expiry

**Problem:** Pending bookings sit indefinitely. Clients don't know if their booking will ever be confirmed.

**Files to touch:**
- `database/migrations/` — add `expires_at` (timestamp, nullable) to `tbl_bookings`
- `app/Models/BookingModel.php`
- `app/Http/Controllers/Client/BookingController.php` (set expiry on creation)
- New artisan command or Laravel scheduled job: `ExpirePendingBookings`

**Steps:**
1. Migration: add `expires_at` timestamp (nullable) to `tbl_bookings`
2. On booking creation, set `expires_at = now() + 48 hours` (configurable per studio later)
3. Create a scheduled command: query pending bookings where `expires_at < now()`, set status to `cancelled`, notify both client and owner
4. Register the command in `app/Console/Kernel.php` to run hourly
5. Show a countdown to the client on their booking card: "Awaiting confirmation — expires in X hours"

**Done when:** Pending bookings expire automatically; both parties are notified; client sees countdown.

---

### 2.8 Expand Notification Coverage

**Problem:** Only 6 notification types exist. Major booking and payment events produce no notification at all.

**Files to touch:**
- `app/Traits/Notifiable.php` — add missing notification methods

**Steps:** Add the following notification methods to the trait and call them at the appropriate controller points:

| Method | Call point |
|---|---|
| `notifyBookingCompleted($booking, $client)` | When owner marks booking complete |
| `notifyPhotographerAssigned($booking, $client)` | When owner assigns photographer (Phase 4.1) |
| `notifyGalleryPublished($gallery, $client)` | When owner publishes gallery (Phase 3.2) |
| `notifyReviewReceived($rating, $provider)` | When client submits a rating |
| `notifyPaymentFailed($payment, $user)` | When PayMongo/Stripe webhook reports failure (Phase 1.5) |
| `notifyAssignmentDeadlineWarning($assignment, $photographer)` | N hours before `response_deadline` |
| `notifySubscriptionExpiring($studio, $owner)` | 7 days before subscription ends |

**Done when:** All major lifecycle events produce an in-app notification to the relevant party.

---

### 2.9 Connect Budget to Booking Payments

**Problem:** `tbl_client_budget.spent_amount` exists but is never updated. The budget module is a manual planning tool with no connection to actual spending.

**Files to touch:**
- `app/Http/Controllers/Client/BookingController.php` — post-payment hook
- `app/Models/ClientBudgetModel.php`

**Steps:**
1. After a payment is confirmed (success redirect + webhook from Phase 1.5), look up whether the client has an active budget for the booking's category
2. If found: add the payment amount to `spent_amount` on that budget record
3. If `spent_amount >= maximum_budget`: fire `notifyBudgetExceeded()` notification to client
4. Show the updated spent amount on the client's budget dashboard

**Done when:** Client budget `spent_amount` auto-increments when a booking payment is confirmed.

---

### 2.10 Add Review Moderation

**Problem:** Reviews publish instantly with no moderation. There is no way to remove a bad-faith review. Ratings are not stored as aggregates, causing a per-request recalculation.

**Files to touch:**
- `database/migrations/` — add `status` enum (`published`, `flagged`, `removed`) to `tbl_studio_ratings` and `tbl_freelancer_ratings`; add `avg_rating` decimal to `tbl_studios` and freelancer profile table
- `app/Http/Controllers/Admin/` — new `ReviewModerationController`
- `app/Models/StudioRatingModel.php`, `FreelancerRatingModel.php`

**Steps:**
1. Migration: add `status` enum (default `published`) to both rating tables
2. Migration: add `avg_rating` (decimal 3,2) and `total_reviews` (int) to `tbl_studios` and freelancer profile — update these on every new review
3. Admin panel: new page listing all reviews with filter by status — admin can flag or remove a review
4. Client-facing views: only show `status = 'published'` reviews
5. Replace on-the-fly `.avg('rating')` with the stored `avg_rating` field

**Done when:** Admin can moderate reviews; stored rating aggregate replaces per-request calculation.

---

## Phase 3 — Core New Features

> These don't exist at all but are foundational to the capstone revision requirements.

### 3.1 Per-Package/Service Selling Image or Video

**Problem:** `tbl_packages` and `tbl_services` have no media field. Clients see only text descriptions — no visual preview of what a service looks like.

**Files to touch:**
- `database/migrations/` — add `cover_images` JSON (nullable) to `tbl_packages` and `tbl_freelancer_packages`
- `app/Models/StudioOwner/PackagesModel.php`
- `app/Models/Freelancer/PackagesModel.php`
- Package create/edit forms
- Client studio profile / package selection view

**Steps:**
1. Migration: add `cover_images` JSON array (nullable) to both package tables — store up to 5 image paths
2. Add image upload to the package create/edit form (max 5 images, same mime/size rules as gallery)
3. On the client-facing package selection step in the booking form, display a small image carousel per package
4. On the studio public profile, show the first cover image as the package card thumbnail

**Done when:** Owner can upload images per package; client sees them when choosing a package.

---

### 3.2 Gallery Draft/Publish + QA Review Step

**Problem:** Photographer uploads photos → client immediately sees them. No quality review. The QA for Photo role is missing entirely.

**Files to touch:**
- `database/migrations/` — add `gallery_status` enum (`draft`, `published`) to gallery tables; default `draft`
- `app/Models/StudioOwner/StudioOnlineGalleryModel.php`
- `app/Http/Controllers/StudioOwner/OnlineGalleryController.php` (add publish action)
- `app/Http/Controllers/Client/OnlineGalleryController.php` (only show `published` galleries)
- Owner gallery management view: show draft galleries with a "Publish" button

**Steps:**
1. Migration: add `gallery_status` enum to `tbl_studio_online_gallery` and `tbl_freelancer_online_gallery`; default `draft`
2. Photographer upload creates gallery in `draft` status
3. Owner gallery view shows drafts in a "Pending Review" section with a "Publish to Client" button
4. On publish: set `gallery_status = 'published'`, set `published_at`, send notification to client
5. `Client\OnlineGalleryController` query: only return galleries where `gallery_status = 'published'`
6. (Optional) If QA role is added in Phase 5, the publish permission can be delegated to them

**Done when:** Photographer uploads → owner reviews → owner publishes → client sees gallery.

---

### 3.3 Post-Completion Revision Request Window

**Problem:** Once a booking is `completed`, the client has no structured way to request photo revisions. They can only leave a star rating.

**Files to touch:**
- `database/migrations/` — add `revision_requested_at` (timestamp, nullable) and `revision_deadline` (timestamp, nullable) to `tbl_bookings`
- `app/Models/BookingModel.php`
- `app/Http/Controllers/Client/MyBookingsController.php`
- `app/Http/Controllers/StudioOwner/BookingController.php`
- Client booking detail view: show "Request Revision" button within window

**Steps:**
1. Migration: add `revision_deadline` (set to `completed_at + 7 days` when booking is marked complete) and `revision_requested_at` (nullable)
2. On the client's completed booking detail, show a "Request Revision" button if `now() < revision_deadline`
3. Client submits revision request with a note → sets `revision_requested_at`, notifies owner and photographer
4. Gallery is unlocked for re-upload (photographer can upload new images to the existing gallery)
5. After new images uploaded, owner re-publishes the gallery
6. After `revision_deadline` passes, the "Request Revision" button disappears; booking is permanently locked

**Done when:** Client can request a revision within 7 days of completion; photographer can re-upload; deadline locks it.

---

### 3.4 Free Trial Flag on Subscription Plans

**Problem:** No free trial concept exists. The first studio being free to register is not the same as a time-limited trial of a paid plan.

**Files to touch:**
- `database/migrations/` — add `trial_days` (integer, default 0) to `tbl_subscription_plans`
- `app/Models/StudioOwner\SubscriptionPlanModel.php` (or equivalent)
- `app/Http/Controllers/Admin/SubscriptionController.php`
- Admin subscription plan create/edit form
- Owner subscription page: show "X-day free trial" if plan has `trial_days > 0`

**Steps:**
1. Migration: add `trial_days` integer (default 0) to `tbl_subscription_plans`
2. Admin can set trial days when creating/editing a plan
3. When owner subscribes to a plan with `trial_days > 0`, set subscription `trial_ends_at = now() + trial_days`
4. During trial period: subscription is active, no payment charged
5. On `trial_ends_at`: notify owner to add payment method; if not added within 48h, subscription downgrades

**Done when:** Admin can set trial days per plan; owners get a trial period before being charged.

---

## Phase 4 — Workflow Improvements

> Structural flow fixes that improve trust, clarity, and accuracy once core features are stable.

### 4.1 Reveal Photographer Profile to Client After Assignment

**Problem:** Client pays → gets assigned an unknown photographer. No visibility into who is coming to shoot their event.

**Files to touch:**
- `app/Http/Controllers/Client/MyBookingsController.php`
- `app/Http/Controllers/Client/BookingDetailsController.php`
- Client booking detail Blade view

**Steps:**
1. On the client's booking detail page, after assignment is created (status = `confirmed` or higher), show the assigned photographer's:
   - Name
   - Profile photo
   - Specialization
   - Link to their portfolio gallery (if public)
2. Send a notification to the client when a photographer is assigned: "Your photographer for [booking ref] has been assigned — meet [Name]."
3. No approval flow needed — this is informational only

**Done when:** Client can see who their photographer is after assignment; they receive a notification.

---

### 4.2 Freelancer Booking Flow — Skip Assignment Step

**Problem:** Freelancer bookings go through the same `pending → owner assigns photographer` flow as studio bookings. But the freelancer IS the photographer — there's no one to assign.

**Files to touch:**
- `app/Http/Controllers/Freelancer/BookingController.php`
- `app/Models/BookingModel.php` — check if `booking_type = 'freelancer'` is handled differently

**Steps:**
1. For freelancer bookings: when a client creates a booking, skip the assignment step entirely
2. The freelancer receives a booking notification and can directly `confirm` or `cancel` the booking
3. On confirm: booking goes to `confirmed` status, no `BookingAssignedPhotographerModel` record created
4. Freelancer's booking management page should show a "Confirm Booking" and "Decline Booking" action on pending bookings
5. On event day: freelancer marks themselves as on-site and marks the booking complete directly

**Done when:** Freelancer bookings don't require a photographer assignment step; freelancer acts as the direct responder.

---

### 4.3 Featured Badge for Premium Studio Listings

**Problem:** `priority_level` in subscription plans silently ranks premium studios higher. Clients don't know why certain studios appear first.

**Files to touch:**
- Client marketplace/discovery Blade view (`resources/views/client/dashboard/` or similar)
- Studio card partial

**Steps:**
1. On the studio card in the marketplace, show a "Featured" badge if `subscription.priority_level >= 3` (or whichever threshold is "premium")
2. Add a tooltip or small note: "Featured studios are verified premium members"
3. No ranking change needed — just make the ranking reason visible

**Done when:** Premium studios show a visible "Featured" badge; clients understand why they appear first.

---

### 4.4 On-Location Booking: Freeform Venue Address

**Problem:** On-location bookings use the municipality/barangay dropdown for the event address, which is too vague for photographer navigation.

**Files to touch:**
- `database/migrations/` — confirm `venue_name` and `street` fields exist on `tbl_bookings` (they do) — add `venue_landmark` (nullable, string)
- `app/Http/Controllers/Client/BookingController.php` (validation)
- Booking form Blade for on-location type

**Steps:**
1. Migration: add `venue_landmark` varchar(255) nullable to `tbl_bookings`
2. In the on-location booking form section, add a "Landmark / Directions" text field below the address dropdown
3. Add it to validation rules as nullable
4. Display it on the booking detail view for both client and photographer

**Done when:** Client can add landmark/directions for on-location shoots; photographer sees it in their assignment details.

---

### 4.5 Subscription Rank Transparency in Discovery

> Already covered in Phase 4.3 above (Featured badge). This item is resolved by that change.

---

## Phase 5 — Advanced Features

> Builds on a stable, complete core. These are the highest-effort Capstone B items.

### 5.1 DDS — Geolocation-Based Studio Discovery

**Problem:** Studios have `attendance_latitude` and `attendance_longitude` in `tbl_studios` (used for employee attendance) but these are never shown to clients. No "find studios near me" feature exists.

**Note:** The existing lat/lng fields are intended for attendance geofencing. For client discovery, studios should set a separate "public location" pin. Reusing attendance coordinates for public display without owner consent is not ideal — add a separate `public_latitude` / `public_longitude`.

**Files to touch:**
- `database/migrations/` — add `public_latitude`, `public_longitude` (decimal 10,7, nullable) to `tbl_studios`
- `app/Http/Controllers/StudioOwner/StudioController.php` — allow owner to set public pin
- `app/Http/Controllers/Client/DashboardController.php` — add distance-based sort
- Client marketplace Blade — add "Near Me" button, map view option

**Steps:**
1. Migration: add `public_latitude` and `public_longitude` to `tbl_studios`
2. Owner studio edit form: add a map pin picker for their public location (Google Maps JS API or Leaflet/OpenStreetMap)
3. On the client marketplace, add a "Near Me" button that requests browser geolocation
4. On allow: sort studios by Haversine distance from client's GPS coordinates (same formula as `AttendanceGeolocationService`)
5. Show distance on each studio card: "2.3 km away"
6. Add a map view toggle showing studios as pins on a map

**Done when:** Client can find studios sorted by distance; studio cards show "X km away."

---

### 5.2 Equipment Assignment to Photographer per Booking

**Problem:** No equipment/camera model exists. Owners cannot assign specific gear to a photographer for a booking.

**Files to touch:**
- `database/migrations/` — new table `tbl_booking_equipment` (booking_id, photographer_id, equipment_name, equipment_type, notes)
- New model: `BookingEquipmentModel`
- `app/Http/Controllers/StudioOwner/BookingController.php` — add equipment assignment to booking detail
- Owner booking detail Blade — equipment checklist section
- `app/Http/Controllers/StudioPhotographer/AssignedBookingController.php` — photographer sees assigned equipment

**Steps:**
1. Migration: create `tbl_booking_equipment` table
2. Owner booking detail: add an "Equipment" section where owner types/selects gear items and assigns to specific photographer
3. Photographer's assigned booking view: shows equipment list with a "Confirm I have this gear" checkbox per item
4. On event day, photographer cannot mark "on-site" until all equipment items are confirmed (optional enforcement)

**Done when:** Owner can assign equipment per booking; photographer confirms gear before the event.

---

### 5.3 Long-Term / Recurring Booking

**Problem:** All bookings are single events. Studios serving corporate clients or schools need retainer or multi-session bookings.

**Files to touch:**
- `database/migrations/` — add `booking_frequency` enum (`one_time`, `recurring`) and `recurrence_pattern` JSON (nullable) to `tbl_bookings`
- `app/Models/BookingModel.php`
- `app/Http/Controllers/Client/BookingController.php`
- Booking form Blade — add recurring options

**Steps:**
1. Migration: add `booking_frequency` and `recurrence_pattern` (JSON: frequency, interval, end_date) to `tbl_bookings`
2. Client booking form: add a "Recurring Booking" toggle (hidden by default)
3. On toggle: show options — Weekly / Monthly, number of sessions, end date
4. On submit: create the first booking normally; store the recurrence pattern
5. A scheduled job generates child bookings for future sessions, linked to the parent by a `parent_booking_id`
6. Owner and client see all sessions under one "booking series" view

**Done when:** Client can create a recurring booking series; each session is a separate booking linked to the parent.

---

### 5.4 QA for Photo Role

**Problem:** No dedicated QA role exists. Photo review before gallery publishing is handled by the owner (Phase 3.2). This phase adds a formal QA user role.

**Files to touch:**
- `database/migrations/` — no new table needed; `tbl_roles` and `tbl_permissions` handle this via RBAC
- New permission: `owner.gallery.publish` (restrict to QA role)
- New user type: `studio-qa` (or assign via RBAC role within a studio)
- `app/Http/Middleware/` — extend existing middleware or add `StudioQAMiddleware`

**Steps:**
1. Define a new RBAC role `QA Photographer` with permission `studio.gallery.review` and `studio.gallery.publish`
2. Owner can assign a studio member to this role via the existing Role Management page
3. The QA member sees draft galleries in their portal and can publish or request re-upload with a note
4. If no QA member is assigned, the owner retains the publish permission (fallback from Phase 3.2)

**Done when:** Owner can assign a QA role to a studio member; QA member reviews and publishes galleries.

---

## Phase 6 — Automation

> These are triggered processes that rely on the flows in Phases 1–5 being correct first.

### 6.1 Auto-Notify on Pending Booking Expiry

Extends Phase 2.7. The scheduled command already cancels expired bookings — ensure it also:
- Sends `BookingExpiredNotification` to client with a "Re-book" link
- Sends `BookingExpiredNotification` to owner with the booking reference

### 6.2 Auto-Notify Photographer on Gallery Upload Deadline

After booking event date passes + N hours, if gallery has no images:
- Send notification to assigned photographer: "Please upload photos for booking [ref] — client is waiting"
- Send a second reminder at 48h if still empty
- Notify owner at 72h for manual follow-up

### 6.3 Auto-Notify Admin on Pending Studio Verification Queue

When a new studio is submitted:
- Immediately notify admin via notification + email
- If unreviewed after 48h: send an SLA reminder to admin

### 6.4 Subscription Expiry Reminders

- 7 days before subscription expires: email + in-app notification to owner
- 3 days before: second reminder
- On expiry: downgrade to free tier, notify owner

### 6.5 Payroll Generation Trigger

Extends existing payroll settings. Add a scheduled job that:
- Reads each studio's payroll settings (cut-off dates)
- On cut-off date: sends a notification to HR to initiate payroll generation (not auto-generate — just remind)
- If HR hasn't generated within 48h of cut-off: escalate notification to owner

### 6.6 Photographer Assignment Auto-Suggestion

When owner opens a pending booking to assign:
- Show a ranked list of available photographers (filtered by `PhotographerAvailabilityService`)
- Ranked by: no conflicts on the booking date, fewest active assignments that week
- Owner still makes the final selection — this is a suggestion, not auto-assignment

---

## Phase 7 — Resource Authorization & Test Coverage

> Security and confidence layer. Do after all features are stable.

### 7.1 Resource-Level Authorization (Policies)

**Problem:** Authorization is role-based only (can this role perform this action?). There is no check that a user owns the record they're editing. An owner could theoretically manipulate another studio's booking IDs in a request.

**Files to touch:**
- New: `app/Policies/BookingPolicy.php`, `StudioPolicy.php`, `GalleryPolicy.php`
- Controllers that handle studio-scoped resources

**Steps:**
1. Create `StudioPolicy`: `view`, `update`, `delete` — checks `$studio->user_id === $user->id`
2. Create `BookingPolicy`: `view`, `cancel`, `complete` — checks booking belongs to the authenticated studio or client
3. Create `GalleryPolicy`: `upload`, `publish`, `delete` — checks gallery belongs to the authenticated studio/photographer
4. Register policies in `AuthServiceProvider::$policies`
5. Add `$this->authorize()` calls in relevant controller methods

**Done when:** Any attempt to access another studio's records via URL manipulation returns a 403.

---

### 7.2 Test Coverage for Core Features

**Problem:** Booking, payment, authentication, gallery, ratings — 0% test coverage. These are the highest-risk parts of the system.

**Files to touch:**
- New test files in `tests/Feature/`

**Priority order (write tests for these first):**

| Test File | What to cover |
|---|---|
| `AuthTest.php` | Register, verify email, login, login blocked before verification |
| `BookingFlowTest.php` | Create booking, check availability conflict, cancel booking, 24h restriction |
| `PaymentTest.php` | Payment success updates booking status, webhook handler updates payment |
| `PhotographerAssignmentTest.php` | Assign photographer, confirm, cancel restrictions by status |
| `OnlineGalleryTest.php` | Upload images, gallery visible to client after publish, hidden before publish |
| `RatingTest.php` | Can only rate completed bookings, one rating per booking, aggregate updates |
| `StudioCreationTest.php` | Barangay validation, business permit required, owner photo required |

**Done when:** All 7 test files pass; `composer test` exits green.

---

## Execution Order Summary

```
Phase 1 — Stabilize
  1.1  Fix barangay JSON bug
  1.2  Fix gallery images not showing
  1.3  Fix UI misalignment & text overflow
  1.4  Enforce owner profile photo
  1.5  Fix payment verification — add webhook handlers   ← NEW (critical)
  1.6  Register procurement escalation in scheduler      ← NEW

Phase 2 — Complete
  2.1  Per-service starting price
  2.2  Owner income report
  2.3  Photographer assignment deadline
  2.4  Visual booking calendar for client
  2.5  Studio-side booking cancellation
  2.6  Portfolio gallery (independent of bookings)
  2.7  Pending booking auto-expiry
  2.8  Expand notification coverage                      ← NEW
  2.9  Connect budget to booking payments                ← NEW
  2.10 Add review moderation + stored rating aggregate   ← NEW

Phase 3 — Core New Features
  3.1  Per-package cover images
  3.2  Gallery draft/publish + QA review step
  3.3  Post-completion revision request window
  3.4  Free trial flag on subscription plans

Phase 4 — Workflow Improvements
  4.1  Reveal photographer profile to client after assignment
  4.2  Freelancer booking flow — skip assignment step
  4.3  Featured badge for premium listings
  4.4  Freeform venue address for on-location bookings

Phase 5 — Advanced Features
  5.1  DDS — geolocation studio discovery
  5.2  Equipment assignment per booking
  5.3  Long-term / recurring booking
  5.4  QA for Photo role

Phase 6 — Automation
  6.1  Pending booking expiry notifications
  6.2  Gallery upload deadline reminders
  6.3  Admin verification queue alerts
  6.4  Subscription expiry reminders
  6.5  Payroll generation trigger
  6.6  Photographer assignment auto-suggestion

Phase 7 — Authorization & Tests                         ← NEW
  7.1  Resource-level policies (Booking, Studio, Gallery)
  7.2  Test coverage for core features (Auth, Booking, Payment, Gallery, Ratings)

```

---

## Rules for Execution

1. **One task at a time.** Complete and test before moving to the next.
2. **Write a migration for every schema change.** Never modify column types directly in production.
3. **Reuse existing service classes.** `PhotographerAvailabilityService`, `AttendanceGeolocationService`, `PaymongoService` — do not duplicate logic.
4. **Test the full booking flow** after any change that touches `BookingModel`, `BookingAssignedPhotographerModel`, or `PaymentModel`.
5. **Run `php artisan migrate:fresh --seed` in dev** after any migration to confirm seeder compatibility.
6. **Do not start Phase 6 automation** until Phase 5 is stable and tested.
7. **Phase 7 tests** should be written incrementally — add tests for each feature as it is completed, not all at the end.
