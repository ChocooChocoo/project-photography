# Client test log

Testing the platform the way a studio client uses it every day: every portal, the normal path, and the mistakes people make (empty forms, wrong values, double clicks, other people's links).

- **Where:** local copy first (`http://127.0.0.1:8000`, dev database, mail sent to the log). Each fix is then pushed to `main` and checked on live (https://lumoraphotography.online).
- **Accounts:** the seeded accounts in `docs/01 - SEEDED ACCOUNT CREDENTIALS.md` (Quarry Road Studio team, client Beatriz, freelancer Lorena, admin Adrian).
- **Status values:** Open, Fixed (code changed and tested locally), Fixed and retested (checked again as the client, locally and on live).

## Issues

| # | Where | What the client sees | Status |
|---|---|---|---|
| C-01 | Every portal, theme settings panel | The theme pictures in the settings panel are broken on every page except the top-level ones. | Fixed |
| C-02 | Login and sign-up | When login or sign-up fails on the server, the client only sees "Login failed. Please try again." and nothing is written to the server log, so support cannot find out why. | Fixed |
| C-03 | Owner, HR, finance and photographer dashboards | Pressing **Export CSV** shows a server error page instead of downloading the report. | Fixed |
| C-04 | All dashboards | A dashboard or export link with a date that isn't a date (for example an edited or pasted link) shows a server error page. | Fixed |
| C-05 | Detail pop-ups in every portal | Opening a booking, leave request, payroll, gallery or chatbot record that was removed (or a stale link) shows "Failed to load …" as a server error instead of "not found". | Fixed |
| C-06 | Admin, locations | The location edit link crashes with a server error (the page it points at does not exist). | Fixed |
| C-07 | Freelancer, services | Opening one of my own services shows a server error. | Fixed |
| C-08 | Login | After a few wrong passwords the login says only "Too Many Attempts.", so I don't know I just have to wait. | Fixed |
| C-09 | Login, studio staff | When six of us log in from the studio's Wi-Fi within a minute, the sixth person is locked out even with the right password. | Fixed |
| C-10 | Sign-up, employees, photographers, profiles | I can create an account with a half-typed mobile number (`+(63)917 ___ ____`), and the studio can't reach me. | Fixed |
| C-11 | Client booking form | Every time I change the start time, an error pops up: "Failed to check availability. Please try again." | Fixed |
| C-12 | Client booking form, rescheduling | I can book an in-studio session at 7:00 AM, or one ending at 7:00 PM, although the studio is open 9 to 6. | Fixed |
| C-13 | Client booking form | When I change the time quickly (or my connection is slow), the availability status can show the answer for the time I picked before. | Fixed |
| C-14 | Client, My Bookings | If I leave checkout (press Cancel at "Confirm Payment", close the Stripe tab, or my card fails), I can't pay for the booking afterwards; it just sits "pending" for 48 hours. | Fixed |
| C-15 | Client, budget planner | Typing a very large budget (for example 99,999,999,999) gives a server error instead of telling me the limit. | Fixed |
| C-16 | Every portal: saves, deletes, approvals | A second click on Delete (or acting on something already removed in another tab) answers "Failed to …" as a server error instead of "not found". | Fixed |
| C-17 | Owners with more than one studio | Deleting something that is already gone says "An active subscription or grace period is required for this studio" although my plans are active. | Fixed |

### C-01 Theme settings pictures are broken

- **As the client:** open any page below the top level, for example Bookings (`/owner/view/bookings`), then open the theme settings panel (the gear). The layout and colour pictures do not show.
- **Steps to reproduce:** log in as any role, open `/owner/view/bookings`, open the settings panel. The browser requests `/owner/view/assets/images/layouts/light.svg`, which redirects (302) instead of returning the image. The same happens on live.
- **Cause:** the seven `layouts/*/theme.blade.php` files wrote image paths relative to the current page (`src="assets/images/..."`, `url(assets/images/...)`).
- **Fix:** the paths now use `asset('assets/...')`, as the rest of each layout already does.
- **Retest:** pending.

### C-02 Failed logins and sign-ups leave no trace

- **As the client:** I type the right email and password and get "Login failed. Please try again." Support looks at the server log and finds nothing.
- **Steps to reproduce:** make the login query fail (found when the local server read `.env.local`, which points at the live database name, and the database refused the connection). `AuthController::login` caught the exception and returned the message without logging it. `store` (sign-up) did the same.
- **Cause:** both catch blocks in `app/Http/Controllers/Auth/AuthController.php` returned a response without `report($e)`.
- **Fix:** both now call `report($e)`, so the real error (here `SQLSTATE[HY000] [1045] Access denied …`) is in `storage/logs/laravel.log`.
- **Note:** the local failure itself was a setup issue, not an app bug. `php artisan serve` passes `APP_ENV=local`, so Laravel loaded the git-ignored `.env.local` (a copy of the live settings). The test server now runs with `php -S`, which reads `.env`.
- **Retest:** pending.

### C-03 Dashboard "Export CSV" crashes

- **As the client (studio owner):** on my dashboard I press **Export CSV** and get an error page instead of the file. HR, finance and photographers get the same.
- **Steps to reproduce:** log in as `yolanda.inocencio@gmail.com`, open the dashboard, press Export CSV. `GET /owner/dashboard/export` returns 500: `Call to undefined method Symfony\Component\HttpFoundation\StreamedResponse::header()` at `OwnerMiddleware.php:34`.
- **Cause:** the portal middleware (`Owner`, `StudioHR`, `StudioFinance`, `StudioPhotographer`, `Freelancer`) added no-cache headers with `$response->header()`, which only Laravel responses have. A CSV export (`StreamedResponse`) or a file download (`BinaryFileResponse`) has no such method. Admin's export worked because its middleware does not add headers.
- **Fix:** all five now use `$response->headers->add([...])`, which works for every response type.
- **Test:** `tests/Feature/Security/PortalMiddlewareDownloadTest.php` (5 portals, streamed and file downloads; all failed before the fix).
- **Retest (local):** the owner pressed Export CSV and downloaded `owner-dashboard-20261025-125942.csv` (200, `text/csv`). Live: pending.

### C-04 Dashboard links with an unreadable date crash

- **As the client:** I bookmark or edit a dashboard link and the date is wrong (`start_date=abc`); the page shows a server error.
- **Steps to reproduce:** as the owner, open `/owner/dashboard/export?start_date=abc&end_date=xyz`. It returned 500 (`InvalidFormatException`).
- **Cause:** `BaseDashboardService::resolveFilters()`, shared by all six dashboards, passed the text straight to `Carbon::parse()`.
- **Fix:** a small `parseDate()` helper returns null for unreadable text, and the filter uses the default last-30-days range. Reversed dates are still swapped.
- **Test:** `tests/Feature/Dashboard/DashboardFilterDatesTest.php`.
- **Retest (local):** export, filter and page with `abc`/`xyz` all return 200 with the default range. Live: pending.

### C-05 Removed or stale records show as server errors

- **As the client:** I open a booking (or a leave request, payroll, gallery or chatbot record) from an old tab after it was removed, and get "Failed to load …" with a server error, as if the site were broken.
- **Steps to reproduce:** as owner Yolanda, open `/owner/bookings/999999/details`; as HR, `/studio-hr/leave-requests/999999`; as the client, `/client/bookings/2/details` (another client's booking). Each returned 500.
- **Cause:** 25 detail methods wrap `findOrFail()` in a `catch (\Exception)` that answers 500, so "not found" was reported as a crash. Another user's record was already refused (not shown), only with the wrong status.
- **Fix:** a shared `Controller::recordNotFound()` returns 404 "This record was not found. It may have been removed." Each of the 25 methods catches `ModelNotFoundException` before its catch-all. Two tests in `CrossStudioIsolationTest` that expected the old 500 now expect 404.
- **Retest (local):** the detail crawl for all seven roles shows 404 for removed and foreign records and 200 for the user's own (for example `/owner/bookings/63/details`, `/client/bookings/63/details`). Live: pending.

### C-06 Admin location edit link crashes

- **As the client (admin):** opening `/admin/location/1/edit` shows a server error.
- **Cause:** the route rendered `admin.edit-locations`, a view that does not exist. No admin page links to it: the Locations list only offers View and Delete.
- **Fix:** removed the unused route and controller method (`LocationController::edit`). The URL now goes to the admin dashboard like any unknown admin page.
- **Feature gap (not a bug):** admins cannot edit a location's barangays or ZIP code from the screens; they can only add and delete. Recorded for the client to decide.
- **Retest (local):** `/admin/location/1/edit` no longer errors. Live: pending.

### C-07 Freelancer service details crash

- **As the client (freelancer):** I open one of my services and get a server error.
- **Steps to reproduce:** as `lorena.novilla@gmail.com`, open `/freelancer/services/1`. It returned 500: `Class "App\Models\User" not found`.
- **Cause:** `Freelancer\ServiceModel::user()` and `Freelancer\PackagesModel::user()` pointed at `App\Models\User`; the app's user model is `App\Models\UserModel`.
- **Fix:** both relations now use `UserModel`.
- **Test:** `tests/Feature/Freelancer/FreelancerModelOwnerRelationTest.php` (failed before).
- **Retest (local):** `/freelancer/services/1` returns 200. Live: pending.

### C-08 "Too Many Attempts." gives no next step

- **As the client:** I mistype my password a few times, then type it correctly and get "Login Failed: Too Many Attempts." I think my account is blocked.
- **Steps to reproduce:** on `/auth/login`, enter a wrong password five times, then the right one.
- **Cause:** Laravel's default 429 message for throttled requests.
- **Fix:** `bootstrap/app.php` renders a throttled JSON request as "Too many attempts. Please wait N seconds and try again.", using the `Retry-After` time. This covers every throttled form.
- **Test:** `ThrottleMessageTest::test_a_throttled_login_says_how_long_to_wait`.
- **Retest (local):** the sixth try shows "Too many attempts. Please wait 44 seconds and try again." Live: pending.

### C-09 One studio network locks out the sixth person to log in

- **As the client:** at 9 AM our team logs in from the studio Wi-Fi. The sixth person is told "Too many attempts" with the right password.
- **Steps to reproduce:** log in correctly as six different accounts from one IP within a minute. The sixth gets 429.
- **Cause:** the login route used `throttle:5,1`, which counts every login request per IP address, successful ones included.
- **Fix:** `AuthController::login` limits wrong passwords to five per minute per email and IP (`RateLimiter`), clears the count after a successful login, and the route keeps a looser per-IP cap of 30 a minute against password spraying. `SecurityRegressionTest` now checks the 30-per-IP cap.
- **Test:** `ThrottleMessageTest::test_staff_on_one_network_can_all_log_in_and_one_persons_typos_do_not_block_others` (seven users log in; one user's five typos block only that user). It failed before the fix with 429 on the sixth user.
- **Retest (local):** seven seeded clients (Daniela, Emilio, Fatima, Gabriel, Helena, Ignacio, Jasmine) logged in one after another from the same machine; all seven reached their account. Live: pending.

### C-10 A half-typed mobile number is accepted

- **As the client:** I start typing my number on the sign-up form, get distracted, and press Create Account. The account is made with the number `+63917`.
- **Steps to reproduce:** on `/auth/register`, type only `917` in Mobile Number and fill in the rest correctly. The field shows `+(63)917 ___ ____`, the server answers "Registration successful!", and the user is stored with `mobile_number = +63917`.
- **Cause:** the input mask only shapes what is typed; every server rule was `required|string|max:20`. The same rule was on the owner and HR employee forms, the studio photographer form, the profile form and freelancer profile setup.
- **Fix:** new rule `App\Rules\PhilippineMobileNumber` accepts a complete number in the usual shapes (`0917 123 4567`, `917 123 4567`, `+63 917 123 4567`, the mask `+(63)917 123 4567`) and otherwise says "Enter a complete mobile number, for example 0917 123 4567." It is used by all six forms. Stored numbers (`+63` and 10 digits) all pass, so existing profiles still save.
- **Test:** `RegistrationTest::test_registration_rejects_a_half_typed_mobile_number_and_accepts_the_usual_formats` (failed before).
- **Retest (local):** typing only `917` now shows "Enter a complete mobile number, for example 0917 123 4567." under the field and creates no account. A full number registers and is stored as `+639171234567`. The test account with `+63917` from the reproduction was deleted. Live: pending.

### C-11 Changing the start time pops an availability error

- **As the client:** I pick a package and a date, then change the start time from 10:00 to 13:00. An error pops up, "Failed to check availability. Please try again.", although the slot is free.
- **Steps to reproduce:** as Beatriz, open `/client/booking-form/studio/4`, pick Wedding Photography, the first package and Wed 4 Nov 2026, then change Start Time. The page sends `start_time=13:00&end_time=10:00` and gets 422 "The end time field must be a date after start time.", then sends the corrected `13:00–16:00`.
- **Cause:** the availability check on `change` ran before the package's fixed duration moved the end time, so it asked about an end before the start. Its error handler showed the generic message for any failure.
- **Fix (`client/booking-forms.blade.php`):** the auto-check does not call the server while the end is not after the start; it shows "End time must be after the start time" under the date instead. The check's error pop-up now shows the server's message for a 422.
- **Retest (local):** start times 13:00, 15:00 and 09:30 each move the end time and show "Available (0/3 bookings)" with no pop-up. Typing an end time of 10:00 with a start of 11:00 shows "End time must be after the start time". Live: pending.

### C-12 In-studio bookings outside opening hours are accepted

- **As the client:** I book an in-studio session at 7:00 AM at a studio that opens at 9. The booking goes through and the owner gets a booking for a time the studio is closed.
- **Steps to reproduce:** on the Quarry Road booking form (open 09:00–18:00), choose an in-studio package, Wed 4 Nov 2026 and start 07:00 (or 16:00 for a three-hour package ending 19:00). The status says "Available" and the booking can be submitted.
- **Cause:** neither the live check (`checkAvailability`) nor the save (`store` / `checkDateAvailability`) compared the time with the studio's `opening_time` and `closing_time`; rescheduling (`BookingRescheduleService::assertAvailable`) did not either. The form never showed the hours.
- **Fix:** `StudioScheduleModel::outsideHoursMessage()` holds the rule. The live check (when an in-studio location is chosen), the booking save and owner/client rescheduling of an in-studio booking all refuse with "The studio is open 9:00 AM to 6:00 PM. Choose a time within those hours, or choose On-Location." On-location bookings are not limited by studio hours (decision: an evening wedding is normal). The form now sends the location type with the check and re-checks when the location changes.
- **Test:** `tests/Feature/Booking/StudioOpeningHoursTest.php`, `RescheduleRequestTest::test_an_in_studio_booking_cannot_move_outside_opening_hours` (both failed before).
- **Retest (local):** in-studio 16:00–19:00 and 07:00–10:00 show the message in a pop-up and under the date, and Proceed is disabled; 10:00–13:00 and 11:00–14:00 are available. Live: pending.

### C-13 Out-of-date availability answers overwrite the current one

- **As the client:** I change 7:00 to 10:00 quickly; the page then says 10:00 is not available, because the answer about 7:00 arrived last.
- **Steps to reproduce:** delay the reply for one check (seen naturally on the slow local server; reproduced with a 3-second delay on the 07:00 reply), then change the time again.
- **Cause:** every reply updated the status, whatever order replies came back in.
- **Fix:** each check gets a number, and only the reply to the latest check may change the page. Choosing a closed day, clearing the date, or an end before the start also discards checks still in flight.
- **Retest (local):** with the 07:00 reply held back 3 seconds, the page ends on "Available (0/3 bookings)" for 10:00–13:00 with no pop-up. Live: pending.

### C-14 No way to finish paying after leaving checkout

- **As the client:** on the booking summary I press Proceed to Payment, then Cancel at "Confirm Payment: Are you sure you want to proceed to payment?" (or I close the Stripe page). The booking is already saved as pending. My Bookings shows only View Details and Cancel Booking, so I can't pay; the booking blocks the slot until it expires after 48 hours.
- **Steps to reproduce:** as Beatriz, book Quarry Road (Wedding, first package, 11 Nov 2026 10:00), press Proceed to Payment, then Cancel. Booking `BK-6ADD9A0FD3B8D` stays `pending` with a pending ₱2,370 payment and no pay button.
- **Cause:** the booking is saved before payment starts, and only partially paid confirmed bookings had a pay button (Pay Balance).
- **Fix:** My Bookings shows a **Complete payment** button for a pending booking with nothing paid and a pending payment. It asks "Pay ₱2,370.00 now to confirm booking …? Unpaid bookings are cancelled after 48 hours." and starts the existing Stripe checkout.
- **Also fixed:** `BookingController::initializePayment` accepted any booking id. It now only starts a payment for the logged-in client's own booking (404 otherwise) and refuses a cancelled booking ("This booking was cancelled, so it can no longer be paid.").
- **Test:** `tests/Feature/Booking/ResumeBookingPaymentTest.php` (another client gets 404; the owner of the booking gets the checkout link; a cancelled booking is refused). It failed before the fix.
- **Retest (local):** My Bookings showed Complete payment for `BK-6ADD9A0FD3B8D`. Pay now → Stripe test checkout with card 4242 4242 4242 4242 → "Payment Successful! Your booking has been confirmed", amount paid ₱2,370.00. The booking is `confirmed`, `partially_paid`, one succeeded payment, ₱5,530 remaining. Reloading the success page did not add a payment. Live: pending.

### C-15 A very large budget gives a server error

- **As the client:** I type 99,999,999,999 as my preferred budget and get "Something went wrong" instead of being told the limit.
- **Steps to reproduce:** as Beatriz, create a budget with `preferred_budget = 99999999999`. It returned 500 (`SQLSTATE[22003] … Out of range value for column 'preferred_budget'`).
- **Cause:** `tbl_client_budget.preferred_budget` is `decimal(10,2)`, but `StoreBudgetRequest` and `UpdateBudgetRequest` only required a number of at least 0.
- **Fix:** both requests allow at most 99,999,999.99 and say "The budget can be at most ₱99,999,999.99."
- **Test:** `BudgetMatchTest::test_budget_amount_must_fit_the_column` (failed before).
- **Retest (local):** the same request now returns 422 with that message; negative and text amounts still get their own messages; a 0 budget saves. Live: pending.

### C-16 Acting on a removed record answers with a server error (rest of the app)

- **As the client:** I double-click Delete on a budget, a service or a role, or approve something another tab already removed. The second request answers "Failed to delete …" as a server error.
- **Steps to reproduce:** as Beatriz, `DELETE /client/budget/3` (another client's budget) returned 500 "Failed to delete budget: No query results for model …"; as the owner, `DELETE /owner/roles/999999` and `/owner/employee/999999` returned 500. Another user's record was already refused; only the answer was wrong.
- **Cause:** the same pattern as C-05, in 95 more methods: `findOrFail()` / `firstOrFail()` inside a `try` whose `catch (\Exception)` answers 500.
- **Fix:** every such method in `app/Http/Controllers` that answers JSON now catches `ModelNotFoundException` first and returns `recordNotFound()` (404 "This record was not found. It may have been removed."). Where the method had opened a database transaction, the new catch rolls it back first (20 methods). Two tests that expected the old 500 (`GalleryLifecycleTest`, `PermitGateTest`) now expect 404.
- **Retest (local):** another client's budget update, delete and status toggle; the owner's role, service, payroll and employee deletes with a missing id; admin category and location deletes: all 404 with that message. The client and owner detail crawl still opens every own record (200). Full suite 755 passed. Live: pending.

### C-17 "Subscription required" for a record that is already gone

- **As the client (studio owner with two studios):** I click Delete twice on a service. The second click says "An active subscription or grace period is required for this studio," although both my studios are on active plans.
- **Steps to reproduce:** as Yolanda (Quarry Road and T02 Annex, both on plans), `DELETE /owner/services/999999` returned 403 with that message.
- **Cause:** `EnforceStudioSubscriptionAccess` looks up the studio from the record. When the record no longer exists and the owner has more than one studio, it could not pick a studio and refused every save.
- **Fix:** when no studio can be found for a save, it is allowed only if **every** studio the owner has is on an active plan or grace period, so no unsubscribed studio can be changed this way. The controller then answers its own 404. Pages (GET) keep the existing rule (any studio with access).
- **Test:** `SubscriptionAccessTest::test_an_unresolvable_write_is_allowed_only_when_every_owned_studio_has_access` (one studio on a plan and one without: refused; both on plans: allowed). `test_manage_access_fails_closed_when_studio_cannot_be_resolved` still passes.
- **Retest (local):** the same deletes now answer 404 "This record was not found. It may have been removed." Live: pending.
