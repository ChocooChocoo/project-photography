# T-14: Platform feature and UI updates

**Status:** ✅ Locally verified, 3 November 2026; production unverified.
**Source:** [frozen prompt](../../ANALYSIS%20-%20PLATINUM%20STUDIO%20PLATFORM/MATERIAL/LEGACY%20DOCUMENTATION/tasks/14.md), asked for in conversation on 3 November 2026.
**Verification:** 775 PHP tests / 4021 assertions and the onboarding JavaScript test pass. A local browser run checked the landing page at 1440 px and 375 px (no horizontal overflow, 44 px tap targets), the client topbar, the fixed-package end time, and the venue pin map.

## Decisions (answers to the prompt's open questions, 3 November 2026)

| Question | Decision |
|---|---|
| Landing style | Dark editorial: near-black, champagne accent, serif display type. |
| Landing photos | Real package covers and studio portfolio images; sections with no data are hidden. |
| Geolocation purpose | GPS check-in when the photographer arrives. No live tracking. |
| Arrival detection | The photographer checks in; the server checks the distance (300 m limit). |
| Shoot location for on-location bookings | The client drops a pin on a map when booking. |
| Arrival alert | Email and in-app notification to the client and the studio owner. No web push. |
| Flexible bookings past closing | Allowed until midnight the same day. The start must be within opening hours. |
| On-site cancellation | A reason is required. Owner and client are notified. Payments do not change. |
| Replacement after an on-site cancel | The owner assigns one through the normal Assign Photographer flow. The client is told; no client approval step. |
| Freelancer groups | A shared display name only. Bookings, payments, and reviews stay with each freelancer. |
| Old studio discount rules | Retired. They never changed a price. The table and model stay. |

## Phases

Each phase is one commit.

| # | What changed | Main files | Tests |
|---|---|---|---|
| 1 | Prices show ₱ instead of "PHP " everywhere: views, JavaScript, controllers, chatbot prompt, contract text. Currency codes sent to Stripe and PayMongo are unchanged. | 44 views and services | `ChatbotFeatureTest` |
| 2 | The messages icon and unread badge moved from every portal sidebar to the topbar. | `partials/chat-topbar-link.blade.php`, `layouts/*/topbar.blade.php` | `PortalLayoutRegressionTest` |
| 3 | Fixed-length packages hide the end time and the server computes it (start + duration, refused past midnight). Flexible in-studio sessions may run past closing until midnight. A bug that made every package look fixed was fixed. | `Client\BookingController`, `StudioScheduleModel::outsideHoursMessage`, `client/booking-forms.blade.php` | `StudioOpeningHoursTest` |
| 4 | Each package (studio and freelancer) has its own discount percentage. The discount lowers the total, deposit, and payment; the booking snapshot records it. Studio discount rules are retired. | `PackagesModel::discountedPrice()`, package forms and requests | `DepositPolicyTest` |
| 5 | Studio equipment has a specs field. The studio profile and booking form list cameras and gear before booking. | `partials/studio-gear.blade.php`, `StudioEquipmentController`, `booking-preparation.js` | `StudioProfileTest` |
| 6 | On-location bookings need a venue pin. On-site check-in sends GPS and is refused beyond 300 m of the venue pin or studio. The owner gets an in-app alert; client and owner get an email. | `AssignedBookingController`, `PhotographerArrivedMail`, `work-form-script.blade.php` | `VenueConfirmationChainTest` |
| 7 | Photographers can cancel while on site or in progress (reason required). The owner's booking view flags the booking for a replacement; the client is notified when one is assigned. | `BookingAssignedPhotographerModel::canCancel`, `StudioOwner\BookingController` | `AssignmentLifecycleTest` |
| 8 | Freelancers can set a shared group name. Clients see it on cards and profiles, with links to the other members, and can search by it. | `ProfileModel::groupMates()`, `Freelancer\ProfileController::updateGroup` | `FreelancerGroupTest` |
| 9 | Photographers, owners, and freelancers can attach the unedited original to a gallery photo. Clients see a before-and-after slider. | `HasBeforeAfterPairs`, `GalleryOriginalController`, `before-after.js` | `BeforeAfterTest` |
| 10 | The tutorial opens with "How Platinum works" and the booking path. Each section explains what the tool is, why it matters, and where to find it. | `OnboardingController::steps`, `onboarding.blade.php` | `OnboardingFlowTest` |
| 11 | New dark editorial landing page, built for mobile. Invented stats and testimonials are removed. | `LandingController`, `landing.blade.php`, `public/assets/css/landing.css` | `LandingPageTest` |

## Known limits

- The arrival radius is one constant (300 m) for every studio.
- A group name is free text, so anyone can type any group's name.
- Direct freelancer bookings have no assignment record, so GPS check-in, arrival alerts, and on-site cancellation do not apply to them.
- Studio HR's gallery screen has no "Add original" button.
- Migrations run on the local database only. Run `php artisan migrate` on production before deploying the code.
