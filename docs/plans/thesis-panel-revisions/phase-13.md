← [Previous: Phase 12](./phase-12.md)

# Phase 13: First-Login Tutorial, Business Gallery, and GUI Overlap

**Status:** ✅ Complete, locally verified (2026-10-14). Final full suite: 671 PHP tests / 3342 assertions; 9 JavaScript regressions passed. Build, scoped formatting/syntax, Blade compilation, design detector and authenticated browser checks passed. Phase 13-only commit authorized; production deployment remains unverified.

**Goal:** New business and client accounts get a guided tutorial on first login. Clients can view each business's image gallery. Overlapping panel text is fixed.

## Dependencies
Phases 1 to 12 (the tutorial should describe the finished features).

## Execution decisions and checklist (2026-10-14)
- User approved working on `main` in the existing checkout, no worktree and no commits. Unrelated edits, deletions and uploads must remain untouched.
- This file is the source of truth and progress record; repeated `phase-1.md` references in the execution prompt are treated as a naming mistake. Phase 1 was read fully and remains unchanged.
- Phase 1's unreproduced production login issue remains open. Proceed only with local login/session regression proof; do not claim production login is fixed. Phases 2–12 report completion with documented acceptance caveats.
- Use the existing onboarding page rather than optional TourGuide. Replay must not clear the completion timestamp. Preserve existing portal visual language, fonts and palette (ENERGY 1 / RHYTHM 1 / MOTION 1); use real portfolio images only. Existing styling is direction, not permission for a redesign.
- Tutorial scope: client, owner and freelancer (self-registering business account); staff password change takes priority and staff/admin keep their dashboard flow. Staff are not new business accounts.
- [x] Verify local prerequisite baseline (full PHP suite: 642 passed / 3162 assertions).
- [x] Test-first login trigger, role content, completion/skip, replay and password-change precedence (39 focused auth/layout tests passed / 224 assertions at the first checkpoint; later browser proof below).
- [x] Test-first client-only studio profile/gallery using existing studio detail query/view; link listing and booking form (15 client regressions passed / 62 assertions at the first checkpoint; later browser proof below).
- [x] Reproduce overlap at desktop/phone widths before patching; add markup regression tests for confirmed fixes. Long topbar name reproduced; final seven-portal browser matrix passed. Unconfirmed KPI/date-filter/chatbot suspects unchanged.
- [x] Verify focused/full PHP, existing JavaScript regressions, formatting/syntax, Blade compilation, build and authenticated browser flows (final evidence below).
- [x] Update canonical task tracker, account/gallery part docs and role-screen map with verified local behavior only; production remains unverified.

### Prerequisite observations
- `tbl_users.onboarding_completed_at`, controller, page and tests exist. Current completed-user redirect prevents replay; login currently returns only the role dashboard URL.
- `EnsurePasswordChangedMiddleware` currently exempts onboarding routes. Tutorial must not bypass a required password change.
- `package.json` defines build/dev only: no configured lint or typecheck script; use scoped PHP formatting/syntax and JavaScript syntax/regression checks instead of introducing tooling.
- `CLAUDE.md` references missing `docs/00 - START HERE.md`; inspect the existing analysis documentation instead. No documentation restructure in scope.
- Initial full-suite foreground run exceeded 120 seconds without a reported failure. Restarted baseline passed: 642 tests / 3162 assertions, 55.26 seconds.

### Verified onboarding backend slice
- After correcting the old test fixture's plaintext password, new tests failed for the intended missing behaviors (13 failures / 7 passes). Added role-aware login redirects, idempotent completion, replay and password-change precedence. Existing staff-onboarding exemption test updated to match the requested password-first behavior.
- Focused command: `php artisan test --compact tests/Feature/Auth/OnboardingFlowTest.php tests/Feature/Auth/LoginDashboardRedirectTest.php tests/Feature/Auth/FirstLoginPasswordChangeTest.php tests/Feature/Layout/PortalLayoutRegressionTest.php`: **39 passed / 224 assertions**.
- Files: `app/Models/UserModel.php`, `Auth/AuthController.php`, `Auth/PasswordChangeController.php`, `OnboardingController.php`, `EnsurePasswordChangedMiddleware.php`; `resources/views/onboarding.blade.php`, client/owner/freelancer topbars; `public/assets/js/onboarding.js`; four auth/layout test files. Tutorial UI uses native buttons, hidden sections, live step progress and heading focus; finish submits the existing completion form. Later browser/JavaScript acceptance is recorded below.

### Verified studio profile backend slice
- Five regressions failed first on the missing route/access/content after fixture corrections. The existing portfolio migration changes nullability only on MySQL; the SQLite fixture explicitly mirrors that schema, without changing application migrations.
- Added `/client/studios/{id}` behind existing client/auth/password middleware. Reuses `BookingDetailsController::index` and its existing eligible-studio, active-package and published-review queries; no duplicate studio aggregate or public route.
- Shared portfolio partial shows active portfolio images only, filters missing public-disk files, exposes full-size links and an explicit empty state. Booking galleries, other studios and inactive galleries remain excluded.
- Listing (initial and filtered) and studio booking form link to the profile. Files: `routes/web.php`, client booking-details/dashboard controllers, client dashboard/booking-details/booking-forms views, new `client/portfolio-images.blade.php`, new `tests/Feature/Client/StudioProfileTest.php`.
- `php artisan test --compact tests/Feature/Client/StudioProfileTest.php tests/Feature/Client/BookingFormsPresentationTest.php tests/Feature/Client/ClientFavoritesTest.php`: **15 passed / 62 assertions** at the first checkpoint. Later authenticated browser acceptance is recorded below.

### Browser acceptance and final refinements
- Browser server used only `C:/Users/marka/AppData/Local/Temp/opencode/phase13-browser.sqlite`, with fresh migrations and disposable accounts. No application database migration, reseed, business-data update, new public media asset or storage symlink performed. Existing public image was referenced by the disposable gallery fixture only.
- Owner first login showed five role-specific steps. Next moved to Step 2 and focused `tutorial-heading-1`; Back restored Step 1; Finish posted completion and reached the owner dashboard. Client first login showed four steps; Skip reached the client dashboard. Profile-menu Replay opened `onboarding?replay=1`; replays did not reset completion (PHP proof). Freelancer first login showed four steps, and subsequent login reached its dashboard.
- Staff password-first acceptance: HR dashboard redirected to password change, tutorial GET redirected there too; valid password replacement returned to the HR dashboard without the business tutorial. Initial staff fixture omitted `permission_string`, producing an existing permission-denial self-redirect; corrected the disposable fixture, not application permission behavior.
- Confirmed topbar root cause: unrestricted full name flex content. A valid-length stress name pushed a 1280px viewport to **2112px scroll width**. Shared CSS caps the displayed name at `min(16rem, 20vw)` with ellipsis; full text stays in the DOM. Seven portals (admin, owner, client, freelancer, HR, finance, photographer), widths **1280 / 390 / 320**, both light/dark: **42 cases**, no horizontal overflow or KPI sub-label/value intersections; desktop name width 256px. Captures after settling layout ruled out false overlap from resize transitions.
- Client profile: initial/filtered listing -> profile -> full-size image (HTTP 200, keyboard Enter) -> booking form -> profile succeeded. Profile at **1280 / 768 / 390 / 320**, light/dark: images decoded, no horizontal overflow. Empty/missing-file privacy is tested; a zero-byte existing fixture image exercised visible decode-error recovery before selecting a valid existing image.
- New listing actions wrap with gaps instead of squeezing labels. New controls, replay and booking CTA are at least **44px** tall. Tutorial card width at 320px is **294px** (was 179px due inherited auth padding). Desktop tutorial at 200% CSS zoom retained no horizontal overflow.
- Native tutorial and photo controls have **3px keyboard focus**, overriding the vendor's `outline: 0 !important` only on these new controls. Scoped neutral text uses existing `--ins-body-color`; primary buttons use a slightly darker existing primary. Settled contrast checks: **5.32:1** primary/white, **8.41:1** neutral/light, **8.39:1** neutral/dark. No global palette change.
- Existing template asset/translation request errors and ApexCharts fixture warnings remain. Final profile and seven-dashboard inspection recorded **zero JavaScript exceptions**; no claim that every inherited console warning is repaired.
- Screenshots: approved temp folder, `phase13-stress-before-*.png`, `phase13-dashboard-final-{role}-{width}.png`, `phase13-profile-verified-{width}-{theme}.png`, `phase13-tutorial-accepted-{width}-{theme}.png`. Desktop/phone captures were inspected.
- JavaScript regression caught disabled controls on a cached tutorial return; `pageshow` restores controls. Node: **9 passed**, including two tutorial tests.
- Active/inactive package and published/removed review regression caught removed reviews in rating-distribution counts; shared detail query now filters published reviews consistently.
- Independent read-only review found malformed studio IDs causing PHP type errors. Regression reproduced HTTP 500; numeric route constraint plus existing untyped controller ID convention now handles malformed IDs through the existing portal fallback and overlarge numeric IDs with 404. Focused regression: **1 passed / 3 assertions**.
- Latest focused auth/layout/profile command: **49 passed / 309 assertions** before malformed-ID and explicit loading-feedback regressions. Final full `php artisan test --compact`: **671 passed / 3342 assertions**, 111.82 seconds. `node --test tests/Integration/*.test.cjs`: **9 passed**. `npm run build`, scoped Pint on onboarding controller and auth/profile/layout tests, 13 changed PHP syntax checks, JavaScript syntax and `php artisan view:cache` passed. Final Impeccable detector returned `[]`; scoped `git diff --check` passed (existing CRLF normalization warnings only). No npm lint/typecheck script exists; no new toolchain introduced.
- Delivery-gate state regression failed first on missing explicit image loading feedback. Added a `role="status"` loading label that clears on load/error. Browser-held image request kept loading visible; successful decode cleared it; forced 404 cleared loading, showed recovery text and removed the failed image link. Restoring the route loaded the valid image again.

## Tasks
- [x] Onboarding flag and static page exist (`tbl_users.onboarding_completed_at`, `OnboardingController.php`, `resources/views/onboarding.blade.php`, `tests/Feature/Auth/OnboardingFlowTest.php`).
- [x] The TourGuide plugin is bundled (`public/assets/plugins/tourguidejs/tour.js`, `tour.min.css`; demo `public/assets/js/pages/misc-tour.js`).
- [x] Owner portfolio upload exists (`StudioOwner/OnlineGalleryController.php:610-628`, `owner/view-portfolio-gallery.blade.php`) and the client sees a Portfolio section (`client/booking-details.blade.php:118-123`).
- [x] Trigger: client/owner/freelancer login with null completion redirects to tutorial through existing auth redirect. Staff/admin retain dashboards. Required password change blocks tutorial GET/POST until complete (focused tests above).
- [x] Tutorial content: client/owner/freelancer role-specific static steps, Next/Back/Finish/Skip, idempotent completion and profile-menu replay. PHP, Node and authenticated browser proof above. Optional TourGuide intentionally unused.
- [x] Business image gallery: `/client/studios/{id}`, client middleware, eligible studio info, active portfolio images/packages and published ratings; initial/filtered listing and booking-form links. PHP privacy and authenticated browser proof above.
- [x] GUI overlap: fixed confirmed long topbar-name overflow. Seven-portal desktop/phone light/dark matrix passed. KPI/date-filter/chatbot suspects were not confirmed after settled captures, so remain unchanged.
- [x] Extended `tests/Feature/Layout/PortalLayoutRegressionTest.php` for bounded names, replay links, wrapping/tap targets, theme tokens/focus reset override and phone tutorial padding; red-green evidence recorded above.
- [x] Updated canonical task tracker, account/gallery part docs and role-screen map with verified local behavior, test/browser evidence and explicit production caveats.

## Done when
- [x] First login of a new client or owner shows the tutorial once, and it can be replayed (automated plus authenticated browser).
- [x] A client can browse a studio's image gallery (image decode/full-size navigation and booking form round-trip verified).
- [x] No overlapping text in the tested seven-dashboard desktop/phone light/dark matrix (42 cases; scope and fixture limitations above).
- [x] Full `php artisan test --compact` (671 passed / 3342 assertions) and `npm run build` pass.

## Design delivery gate

Scope: new tutorial, portfolio partial, profile/replay links and confirmed topbar-name refinement. Unchanged legacy portal/template content is not claimed as a whole-product design audit.

### Hard gate
- R-02 PASS: no em dash introduced in new UI copy.
- R-03 PASS: tutorial/profile and seven-portal desktop/phone matrices have no horizontal overflow; new controls are at least 44px tall.
- R-17 PASS: profile prices/review counts come from existing database records; step counts come from role content.
- R-18 PASS: no testimonial or person identity invented for product UI.
- R-23 PASS: no new visual asset generated; gallery displays stored uploads, browser fixtures are explicitly disposable.
- R-24 PASS: profile and replay links resolve to registered routes; booking form round-trip verified.
- R-25 PASS: new control/body contrast checked at 5.32:1, 8.41:1 and 8.39:1 with the contrast script and settled browser colors.
- R-26 PASS: Next/Back change steps, Finish/Skip submit, Replay opens guide, both listing paths open profile, photo opens full size, booking link opens form and returns to profile.
- R-27 PASS: empty/missing portfolio tested; held image request shows loading; success clears it; forced decode/network error shows recovery text and removes dead link.
- R-28 PASS: no FAQ introduced.
- R-32 PASS: keyboard Enter advances tutorial/photo; heading focus and new link/button focus show 3px outlines; hidden steps leave tab order.
- R-33 PASS: features authored directly with source patches, not external source-rewrite scripts.
- R-34 PASS: both shipped themes checked at desktop/phone widths; new styles use existing theme tokens.
- R-35 PASS: app run on disposable database, new controls clicked, screenshots inspected, full PHP/Node/build checks passed; inherited console warnings explicitly noted.
- R-36 PASS: no new security/compliance/performance/customer claim.
- R-37 PASS: incumbent portal identity preserved; ENERGY 1 / RHYTHM 1 / MOTION 1 stated before implementation.
- R-38 PASS: displayed content is role-specific guidance and stored portfolio/business data; fixtures are labeled as test data.

### Purpose gate
- R-01 PASS: existing primary marks the next/booking action; slight darkening serves AA contrast, no new gradient.
- R-04 PASS: existing book icon identifies tutorial replay; no new icon family.
- R-06 PASS: incumbent portal font retained for continuity and readability, no display/monospace experiment.
- R-07 PASS: no decorative background pattern introduced.
- R-08 PASS: no decorative button arrows introduced.
- R-09 PASS: no new marketing/status badge introduced.
- R-10 PASS: no glass/blur introduced.
- R-12 PASS: existing single tutorial panel groups one focused task; no additional shadow layers.
- R-13 PASS: no glow introduced.
- R-14 PASS: guide shows one role step at a time; portfolio grid contains actual images, not feature-card filler.
- R-19 PASS: no automatic motion introduced; native state changes/reduced-motion checks retain predictable operation.
- R-22 PASS: existing uploaded work is the visual content; no generic illustration generated.

### Liveliness
- Dials PASS: ENERGY 1 / RHYTHM 1 / MOTION 1 declared in execution decisions.
- Dial fidelity PASS: quiet portal-consistent layouts and direct state changes, no cinematic motion.
- Focal point PASS: current tutorial task and portfolio work are the content focus.
- Whitespace PASS: step content and navigation separated; phone padding reduced where inherited layout squeezed reading.
- Accent PASS: existing primary reserved for next/booking action; secondary actions remain neutral.
- Identity PASS: incumbent typography, panel scale and portal controls preserved; role-specific tools make the guide product-specific.
- Design read PASS: preservation direction stated before UI generation, with incumbent implementation as authority.

### Craftsmanship and quality locks
- C-1 PASS: visual decisions serve continuity, task hierarchy, readable phones and accessible controls.
- C-2 PASS: new controls have recorded working actions.
- C-3 PASS: guide topics match real role tools; gallery contains stored portfolio work only.
- C-4 PASS: empty/loading/error, light/dark, phone/desktop and keyboard checks recorded above.
- C-5 PASS: figures and claims come from database/test evidence, not invented social proof.
- R-05 PASS: no landing-page template introduced; role step sequence and existing studio detail structure reused.
- R-11 PASS: existing panel/button radius scale retained, no pill-shape redesign.
- R-15 PASS: controls name actions (Next, Back, Finish, Skip, Replay tutorial, View studio).
- R-16 PASS: new guidance uses concrete product terms, no marketing buzzwords.
- R-20 PASS: budget/booking/gallery/chat and owner staffing/permission workflows make content specific to this platform.
- R-21 PASS: existing theme choice retained; no forced new default or toggle added.
- R-29 PASS: existing neutral/primary palette retained for new controls.
- R-30 PASS: no external product visual system cloned.
- R-31 PASS: color for contrast, sequential layout for guidance, incumbent type/radius for continuity, fluid phone padding for reading, photos for portfolio evidence and book icon for replay navigation.

## Files changed

Implementation (18 files):
- `app/Models/UserModel.php`
- `app/Http/Controllers/Auth/AuthController.php`
- `app/Http/Controllers/Auth/PasswordChangeController.php`
- `app/Http/Controllers/OnboardingController.php`
- `app/Http/Middleware/EnsurePasswordChangedMiddleware.php`
- `app/Http/Controllers/Client/BookingDetailsController.php`
- `app/Http/Controllers/Client/DashboardController.php`
- `routes/web.php`
- `resources/views/onboarding.blade.php`
- `resources/views/client/dashboard.blade.php`
- `resources/views/client/booking-details.blade.php`
- `resources/views/client/booking-forms.blade.php`
- `resources/views/client/portfolio-images.blade.php` (new)
- `resources/views/layouts/client/topbar.blade.php`
- `resources/views/layouts/owner/topbar.blade.php`
- `resources/views/layouts/freelancer/topbar.blade.php`
- `resources/views/layouts/partials/portal-base-scripts.blade.php`
- `public/assets/js/onboarding.js` (new)

Tests (6 files):
- `tests/Feature/Auth/OnboardingFlowTest.php`
- `tests/Feature/Auth/LoginDashboardRedirectTest.php`
- `tests/Feature/Auth/FirstLoginPasswordChangeTest.php`
- `tests/Feature/Layout/PortalLayoutRegressionTest.php`
- `tests/Feature/Client/StudioProfileTest.php` (new)
- `tests/Integration/Onboarding.test.cjs` (new)

Documentation (5 files):
- This file (existing untracked user plan, updated in place).
- `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/09 - TASK TRACKER.md`
- `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/PARTS/P-01 - ACCOUNTS AND ACCESS.md`
- `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/PARTS/P-06 - GALLERIES AND REVIEWS.md`
- `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/12 - SCREENS BY ROLE.md`

## Remaining work and boundaries
- No Phase 13 implementation task remains. Production deployment and smoke checks are not performed; Phase 1's unreproduced production login issue remains open.
- No commit was made during implementation. User subsequently authorized a Phase 13-only commit; Phase 9 documentation follow-up, unrelated deletions and uploads remain excluded. No branch/worktree creation, broad formatting, unrelated change removal, production migration, new plugin or public studio-page rollout.
- Fresh precommit verification repeated: **671 PHP tests / 3342 assertions**, **9 JavaScript regressions**, build and scoped Pint all passed.
- Exact next step after the authorized commit: run authenticated first-login/replay/gallery smoke checks in the intended deployment environment. Do not close Phase 1 from local evidence alone.

← [Previous: Phase 12](./phase-12.md)
