# Client test log

Testing the platform the way a studio client uses it every day: every portal, the normal path, and the mistakes people make (empty forms, wrong values, double clicks, other people's links).

- **Where:** local copy first (`http://127.0.0.1:8000`, dev database, mail sent to the log). Each fix is then pushed to `main` and checked on live (https://lumoraphotography.online).
- **Accounts:** the seeded accounts in `docs/01 - SEEDED ACCOUNT CREDENTIALS.md` (Quarry Road Studio team, client Beatriz, freelancer Lorena, admin Adrian).
- **Status values:** Open, Fixed (code changed and tested locally), Fixed and retested (checked again as the client, locally and on live). Resumed issues explicitly marked **Fixed and retested (local)** have client-browser proof locally only; live verification remains pending deployment approval.

## For the owner (not bugs)

- **Delete one test account on live.** While waiting for the deploy, a sign-up probe ran against the old code and created `probe.deploy.<timestamp>@example.com`. In phpMyAdmin: `DELETE FROM tbl_users WHERE email LIKE 'probe.deploy.%@example.com';`
- **Feature gaps found (no screen exists, so nothing is broken):** admins cannot edit a location (only add and delete, C-06), and cannot delete a studio (they approve or reject, C-23).
- **Live data notes:** Quarry Road's plan has reached its 5-staff limit, so HR cannot add staff until a place is freed or the plan is upgraded (this is the intended rule). Seeded mobile numbers are one digit short (`+63` and 9 digits); they keep working, and new or changed numbers must be complete (C-10, C-18).
- **Test data left:** locally, leave and overtime requests 16–19 and 21, procurement request 16 (completed) and booking `BK-6ADD9A0FD3B8D` (paid in Stripe test mode). On live, booking `BK-6ADDCDBB01021` (cancelled, unpaid), leave request 16 (cancelled) and one empty owner–client conversation for booking 76.

## Issues

| # | Where | What the client sees | Status |
|---|---|---|---|
| C-01 | Every portal, theme settings panel | The theme pictures in the settings panel are broken on every page except the top-level ones. | Fixed and retested |
| C-02 | Login and sign-up | When login or sign-up fails on the server, the client only sees "Login failed. Please try again." and nothing is written to the server log, so support cannot find out why. | Fixed and retested |
| C-03 | Owner, HR, finance and photographer dashboards | Pressing **Export CSV** shows a server error page instead of downloading the report. | Fixed and retested |
| C-04 | All dashboards | A dashboard or export link with a date that isn't a date (for example an edited or pasted link) shows a server error page. | Fixed and retested |
| C-05 | Detail pop-ups in every portal | Opening a booking, leave request, payroll, gallery or chatbot record that was removed (or a stale link) shows "Failed to load …" as a server error instead of "not found". | Fixed and retested |
| C-06 | Admin, locations | The location edit link crashes with a server error (the page it points at does not exist). | Fixed and retested |
| C-07 | Freelancer, services | Opening one of my own services shows a server error. | Fixed and retested |
| C-08 | Login | After a few wrong passwords the login says only "Too Many Attempts.", so I don't know I just have to wait. | Fixed and retested |
| C-09 | Login, studio staff | When six of us log in from the studio's Wi-Fi within a minute, the sixth person is locked out even with the right password. | Fixed and retested |
| C-10 | Sign-up, employees, photographers, profiles | I can create an account with a half-typed mobile number (`+(63)917 ___ ____`), and the studio can't reach me. | Fixed and retested |
| C-11 | Client booking form | Every time I change the start time, an error pops up: "Failed to check availability. Please try again." | Fixed and retested |
| C-12 | Client booking form, rescheduling | I can book an in-studio session at 7:00 AM, or one ending at 7:00 PM, although the studio is open 9 to 6. | Fixed and retested |
| C-13 | Client booking form | When I change the time quickly (or my connection is slow), the availability status can show the answer for the time I picked before. | Fixed and retested |
| C-14 | Client, My Bookings | If I leave checkout (press Cancel at "Confirm Payment", close the Stripe tab, or my card fails), I can't pay for the booking afterwards; it just sits "pending" for 48 hours. | Fixed and retested |
| C-15 | Client, budget planner | Typing a very large budget (for example 99,999,999,999) gives a server error instead of telling me the limit. | Fixed and retested |
| C-16 | Every portal: saves, deletes, approvals | A second click on Delete (or acting on something already removed in another tab) answers "Failed to …" as a server error instead of "not found". | Fixed and retested |
| C-17 | Owners with more than one studio | Deleting something that is already gone says "An active subscription or grace period is required for this studio" although my plans are active. | Fixed and retested |
| C-18 | Profile (all roles), freelancer profile | After the C-10 fix, saving my profile without touching the mobile number fails with "Enter a complete mobile number". | Fixed and retested |
| C-19 | Messages (all roles) | Sending a message of only spaces shows "The body field is required." under the box. | Fixed and retested |
| C-20 | Client dashboard, favourites | Double-clicking the heart on a studio sends two toggles, so it ends up not favourited (or shows "Failed to update favorite."). | Fixed and retested |
| C-21 | Reviews, gallery uploads, booking status and assignment, payments | When I make a mistake (a short review, a non-image photo, no rating), the live site says only "Something went wrong" instead of what to fix. | Fixed and retested |
| C-22 | HR and owner: add employee / add studio photographer | Adding a photographer whose specialisation has no service yet says only "Something went wrong". | Fixed and retested |
| C-23 | Admin, studios | The "delete studio" action crashes with a server error. | Fixed and retested |
| C-24 | Leave and overtime (HR, finance, photographer, owner), chatbot intents | Approving, cancelling or deleting a leave or overtime request (or a chatbot answer) that was already removed shows a server error. | Fixed and retested |
| C-25 | Client booking | When a studio's plan has lapsed, I'm told "An active subscription or grace period is required for this studio." | Fixed and retested |
| C-26 | Leave and overtime (HR, finance, photographer) | I can file the same leave dates twice (or overlapping dates, or the same overtime hours), so HR and payroll see duplicates. | Fixed and retested |
| C-27 | Procurement (photographer, HR, finance) | A very large quantity or cost on a purchase request, purchase order, payment or receipt says only "Something went wrong". | Fixed and retested |
| C-28 | First visit, login and every portal | While I type my login, an "Admin Customizer" panel with a "Buy Now" button opens by itself and covers the form. | Fixed and retested (local and live) |
| C-29 | Client budget list, view and edit | My budget's angle brackets become formatting, and quotes can truncate the name when I edit it. | Fixed and retested (local and live) |
| C-30 | Client discovery filters and favourites | I save a studio, then clear filters. The heart becomes empty again even though the studio is still saved. | Fixed and retested (local and live) |
| C-31 | Filtered client discovery cards | After filtering, a studio's favourite heart extends over the next card and cannot be clicked because the next studio's picture covers it. | Fixed and retested (local and live) |
| C-32 | Client discovery filters | I clear a price filter, but a slow response brings back the old empty result even though no filter is selected. | Fixed and retested (local and live) |
| C-33 | Client booking package selection | I choose a package, but a delayed package reload clears it; Proceed later says "Package Required". | Fixed and retested (local and live) |
| C-34 | Booking reschedule window, customer and provider | After a reschedule action succeeds, Escape stops closing the booking window because keyboard focus leaves it. | Fixed and retested (local) |
| C-35 | Owner refund target and customer photographer replacement pages | The sidebar covers page controls, so I cannot click Save refund target normally. | Fixed and retested (local) |
| C-36 | HR payroll generation and finance review | A payroll with no attendance showed ₱32,375.00 of deductions against ₱0.00 gross. | Fixed and retested (local) |
| C-37 | Admin, Users | Clicking a person's name in the user list does nothing, although the name looks clickable. | Fixed and retested (local and live) |
| C-38 | Owner booking details, hardcopy quote | After saving a hardcopy quote, Escape no longer closes Booking Details because keyboard focus leaves the window. | Fixed and retested (local) |
| C-39 | Owner Subscription | My annual plan is valid until July 2027, but the page says "Expiring Soon" today. | Fixed and retested (local and live) |
| C-40 | Live site, every page using app.js | After deployment, a normal browser still kept the old app.js from cache, so the fixed login behavior did not reach returning visitors. | Fixed and retested (local and live) |
| C-41 | Owner refund target and customer photographer replacement pages | The browser tab says "Default Title" instead of the page name. | Fixed and retested (local and live) |

### C-01 Theme settings pictures are broken

- **As the client:** open any page below the top level, for example Bookings (`/owner/view/bookings`), then open the theme settings panel (the gear). The layout and colour pictures do not show.
- **Steps to reproduce:** log in as any role, open `/owner/view/bookings`, open the settings panel. The browser requests `/owner/view/assets/images/layouts/light.svg`, which redirects (302) instead of returning the image. The same happens on live.
- **Cause:** the seven `layouts/*/theme.blade.php` files wrote image paths relative to the current page (`src="assets/images/..."`, `url(assets/images/...)`).
- **Fix:** the paths now use `asset('assets/...')`, as the rest of each layout already does.
- **Retest (local):** the owner bookings page loads the theme pictures from `/assets/images/layouts/…` (200).
- **Retest (live):** Live (25 Oct, deploy of `48ce62fb`): the login page now loads `https://lumoraphotography.online/assets/images/layouts/light.svg`, which returns 200.

### C-02 Failed logins and sign-ups leave no trace

- **As the client:** I type the right email and password and get "Login failed. Please try again." Support looks at the server log and finds nothing.
- **Steps to reproduce:** make the login query fail (found when the local server read `.env.local`, which points at the live database name, and the database refused the connection). `AuthController::login` caught the exception and returned the message without logging it. `store` (sign-up) did the same.
- **Cause:** both catch blocks in `app/Http/Controllers/Auth/AuthController.php` returned a response without `report($e)`.
- **Fix:** both now call `report($e)`, so the real error (here `SQLSTATE[HY000] [1045] Access denied …`) is in `storage/logs/laravel.log`.
- **Note:** the local failure itself was a setup issue, not an app bug. `php artisan serve` passes `APP_ENV=local`, so Laravel loaded the git-ignored `.env.local` (a copy of the live settings). The test server now runs with `php -S`, which reads `.env`.
- **Retest (local):** with the database refusing the connection, `storage/logs/laravel.log` now has the real error.
- **Retest (live):** Live: logins and sign-ups work for every role tested below; the server log cannot be read from outside, so the logging itself was confirmed locally (the reported `SQLSTATE[HY000] [1045]` line).

### C-03 Dashboard "Export CSV" crashes

- **As the client (studio owner):** on my dashboard I press **Export CSV** and get an error page instead of the file. HR, finance and photographers get the same.
- **Steps to reproduce:** log in as `yolanda.inocencio@gmail.com`, open the dashboard, press Export CSV. `GET /owner/dashboard/export` returns 500: `Call to undefined method Symfony\Component\HttpFoundation\StreamedResponse::header()` at `OwnerMiddleware.php:34`.
- **Cause:** the portal middleware (`Owner`, `StudioHR`, `StudioFinance`, `StudioPhotographer`, `Freelancer`) added no-cache headers with `$response->header()`, which only Laravel responses have. A CSV export (`StreamedResponse`) or a file download (`BinaryFileResponse`) has no such method. Admin's export worked because its middleware does not add headers.
- **Fix:** all five now use `$response->headers->add([...])`, which works for every response type.
- **Test:** `tests/Feature/Security/PortalMiddlewareDownloadTest.php` (5 portals, streamed and file downloads; all failed before the fix).
- **Retest (local):** the owner pressed Export CSV and downloaded `owner-dashboard-20261025-125942.csv` (200, `text/csv`).
- **Retest (live):** Live: owner, finance and photographer `dashboard/export` each return 200 `text/csv` ("Dashboard Export").

### C-04 Dashboard links with an unreadable date crash

- **As the client:** I bookmark or edit a dashboard link and the date is wrong (`start_date=abc`); the page shows a server error.
- **Steps to reproduce:** as the owner, open `/owner/dashboard/export?start_date=abc&end_date=xyz`. It returned 500 (`InvalidFormatException`).
- **Cause:** `BaseDashboardService::resolveFilters()`, shared by all six dashboards, passed the text straight to `Carbon::parse()`.
- **Fix:** a small `parseDate()` helper returns null for unreadable text, and the filter uses the default last-30-days range. Reversed dates are still swapped.
- **Test:** `tests/Feature/Dashboard/DashboardFilterDatesTest.php`.
- **Retest (local):** export, filter and page with `abc`/`xyz` all return 200 with the default range.
- **Retest (live):** Live: `/owner/dashboard/export?start_date=abc&end_date=xyz` returns 200 CSV with the default range "Sep 26, 2026 - Oct 25, 2026".

### C-05 Removed or stale records show as server errors

- **As the client:** I open a booking (or a leave request, payroll, gallery or chatbot record) from an old tab after it was removed, and get "Failed to load …" with a server error, as if the site were broken.
- **Steps to reproduce:** as owner Yolanda, open `/owner/bookings/999999/details`; as HR, `/studio-hr/leave-requests/999999`; as the client, `/client/bookings/2/details` (another client's booking). Each returned 500.
- **Cause:** 25 detail methods wrap `findOrFail()` in a `catch (\Exception)` that answers 500, so "not found" was reported as a crash. Another user's record was already refused (not shown), only with the wrong status.
- **Fix:** a shared `Controller::recordNotFound()` returns 404 "This record was not found. It may have been removed." Each of the 25 methods catches `ModelNotFoundException` before its catch-all. Two tests in `CrossStudioIsolationTest` that expected the old 500 now expect 404.
- **Retest (local):** the detail crawl for all seven roles shows 404 for removed and foreign records and 200 for the user's own (for example `/owner/bookings/63/details`, `/client/bookings/63/details`).
- **Retest (live):** Live: `/client/bookings/999999/details` and `/owner/bookings/999999/details` return 404 "This record was not found. It may have been removed."

### C-06 Admin location edit link crashes

- **As the client (admin):** opening `/admin/location/1/edit` shows a server error.
- **Cause:** the route rendered `admin.edit-locations`, a view that does not exist. No admin page links to it: the Locations list only offers View and Delete.
- **Fix:** removed the unused route and controller method (`LocationController::edit`). The URL now goes to the admin dashboard like any unknown admin page.
- **Feature gap (not a bug):** admins cannot edit a location's barangays or ZIP code from the screens; they can only add and delete. Recorded for the client to decide.
- **Retest (local):** `/admin/location/1/edit` no longer errors.
- **Retest (live):** Live: `/admin/location/1/edit` no longer errors (the admin lands on a normal page).

### C-07 Freelancer service details crash

- **As the client (freelancer):** I open one of my services and get a server error.
- **Steps to reproduce:** as `lorena.novilla@gmail.com`, open `/freelancer/services/1`. It returned 500: `Class "App\Models\User" not found`.
- **Cause:** `Freelancer\ServiceModel::user()` and `Freelancer\PackagesModel::user()` pointed at `App\Models\User`; the app's user model is `App\Models\UserModel`.
- **Fix:** both relations now use `UserModel`.
- **Test:** `tests/Feature/Freelancer/FreelancerModelOwnerRelationTest.php` (failed before).
- **Retest (local):** `/freelancer/services/1` returns 200.
- **Retest (live):** Live: Lorena's service 1 (`/freelancer/services/1`) returns 200 with the service.

### C-08 "Too Many Attempts." gives no next step

- **As the client:** I mistype my password a few times, then type it correctly and get "Login Failed: Too Many Attempts." I think my account is blocked.
- **Steps to reproduce:** on `/auth/login`, enter a wrong password five times, then the right one.
- **Cause:** Laravel's default 429 message for throttled requests.
- **Fix:** `bootstrap/app.php` renders a throttled JSON request as "Too many attempts. Please wait N seconds and try again.", using the `Retry-After` time. This covers every throttled form.
- **Test:** `ThrottleMessageTest::test_a_throttled_login_says_how_long_to_wait`.
- **Retest (local):** the sixth try shows "Too many attempts. Please wait 44 seconds and try again."
- **Retest (live):** Live: the sixth wrong password returns 429 "Too many attempts. Please wait 57 seconds and try again."

### C-09 One studio network locks out the sixth person to log in

- **As the client:** at 9 AM our team logs in from the studio Wi-Fi. The sixth person is told "Too many attempts" with the right password.
- **Steps to reproduce:** log in correctly as six different accounts from one IP within a minute. The sixth gets 429.
- **Cause:** the login route used `throttle:5,1`, which counts every login request per IP address, successful ones included.
- **Fix:** `AuthController::login` limits wrong passwords to five per minute per email and IP (`RateLimiter`), clears the count after a successful login, and the route keeps a looser per-IP cap of 30 a minute against password spraying. `SecurityRegressionTest` now checks the 30-per-IP cap.
- **Test:** `ThrottleMessageTest::test_staff_on_one_network_can_all_log_in_and_one_persons_typos_do_not_block_others` (seven users log in; one user's five typos block only that user). It failed before the fix with 429 on the sixth user.
- **Retest (local):** seven seeded clients (Daniela, Emilio, Fatima, Gabriel, Helena, Ignacio, Jasmine) logged in one after another from the same machine; all seven reached their account.
- **Retest (live):** Live: right after that address was locked out, eight different seeded accounts (client, owner, HR, finance, photographer, freelancer, admin) logged in from the same machine without a lockout.

### C-10 A half-typed mobile number is accepted

- **As the client:** I start typing my number on the sign-up form, get distracted, and press Create Account. The account is made with the number `+63917`.
- **Steps to reproduce:** on `/auth/register`, type only `917` in Mobile Number and fill in the rest correctly. The field shows `+(63)917 ___ ____`, the server answers "Registration successful!", and the user is stored with `mobile_number = +63917`.
- **Cause:** the input mask only shapes what is typed; every server rule was `required|string|max:20`. The same rule was on the owner and HR employee forms, the studio photographer form, the profile form and freelancer profile setup.
- **Fix:** new rule `App\Rules\PhilippineMobileNumber` accepts a complete number in the usual shapes (`0917 123 4567`, `917 123 4567`, `+63 917 123 4567`, the mask `+(63)917 123 4567`) and otherwise says "Enter a complete mobile number, for example 0917 123 4567." It is used by all six forms. Stored numbers (`+63` and 10 digits) all pass, so existing profiles still save.
- **Test:** `RegistrationTest::test_registration_rejects_a_half_typed_mobile_number_and_accepts_the_usual_formats` (failed before).
- **Retest (local):** typing only `917` now shows "Enter a complete mobile number, for example 0917 123 4567." under the field and creates no account. A full number registers and is stored as `+639171234567`. The test account with `+63917` from the reproduction was deleted.
- **Retest (live):** Live: sign-up with `+63917` returns 422 "Enter a complete mobile number, for example 0917 123 4567." and creates no account.

### C-11 Changing the start time pops an availability error

- **As the client:** I pick a package and a date, then change the start time from 10:00 to 13:00. An error pops up, "Failed to check availability. Please try again.", although the slot is free.
- **Steps to reproduce:** as Beatriz, open `/client/booking-form/studio/4`, pick Wedding Photography, the first package and Wed 4 Nov 2026, then change Start Time. The page sends `start_time=13:00&end_time=10:00` and gets 422 "The end time field must be a date after start time.", then sends the corrected `13:00–16:00`.
- **Cause:** the availability check on `change` ran before the package's fixed duration moved the end time, so it asked about an end before the start. Its error handler showed the generic message for any failure.
- **Fix (`client/booking-forms.blade.php`):** the auto-check does not call the server while the end is not after the start; it shows "End time must be after the start time" under the date instead. The check's error pop-up now shows the server's message for a 422.
- **Retest (local):** start times 13:00, 15:00 and 09:30 each move the end time and show "Available (0/3 bookings)" with no pop-up. Typing an end time of 10:00 with a start of 11:00 shows "End time must be after the start time".
- **Retest (live):** Live: on the Quarry Road form, start times 13:00 and 10:00 move the end time (16:00, 13:00) and show "Available (0/3 bookings)" with no pop-up.

### C-12 In-studio bookings outside opening hours are accepted

- **As the client:** I book an in-studio session at 7:00 AM at a studio that opens at 9. The booking goes through and the owner gets a booking for a time the studio is closed.
- **Steps to reproduce:** on the Quarry Road booking form (open 09:00–18:00), choose an in-studio package, Wed 4 Nov 2026 and start 07:00 (or 16:00 for a three-hour package ending 19:00). The status says "Available" and the booking can be submitted.
- **Cause:** neither the live check (`checkAvailability`) nor the save (`store` / `checkDateAvailability`) compared the time with the studio's `opening_time` and `closing_time`; rescheduling (`BookingRescheduleService::assertAvailable`) did not either. The form never showed the hours.
- **Fix:** `StudioScheduleModel::outsideHoursMessage()` holds the rule. The live check (when an in-studio location is chosen), the booking save and owner/client rescheduling of an in-studio booking all refuse with "The studio is open 9:00 AM to 6:00 PM. Choose a time within those hours, or choose On-Location." On-location bookings are not limited by studio hours (decision: an evening wedding is normal). The form now sends the location type with the check and re-checks when the location changes.
- **Test:** `tests/Feature/Booking/StudioOpeningHoursTest.php`, `RescheduleRequestTest::test_an_in_studio_booking_cannot_move_outside_opening_hours` (both failed before).
- **Retest (local):** in-studio 16:00–19:00 and 07:00–10:00 show the message in a pop-up and under the date, and Proceed is disabled; 10:00–13:00 and 11:00–14:00 are available.
- **Retest (live):** Live: in-studio 07:00–09:00 at Quarry Road answers "The studio is open 9:00 AM to 6:00 PM. Choose a time within those hours, or choose On-Location."; 10:00–13:00 is available.

### C-13 Out-of-date availability answers overwrite the current one

- **As the client:** I change 7:00 to 10:00 quickly; the page then says 10:00 is not available, because the answer about 7:00 arrived last.
- **Steps to reproduce:** delay the reply for one check (seen naturally on the slow local server; reproduced with a 3-second delay on the 07:00 reply), then change the time again.
- **Cause:** every reply updated the status, whatever order replies came back in.
- **Fix:** each check gets a number, and only the reply to the latest check may change the page. Choosing a closed day, clearing the date, or an end before the start also discards checks still in flight.
- **Retest (local):** with the 07:00 reply held back 3 seconds, the page ends on "Available (0/3 bookings)" for 10:00–13:00 with no pop-up.
- **Retest (live):** Live: with the 07:00 reply held back 3 seconds, the form ends on "Available (0/3 bookings)" for 10:00–13:00 with no pop-up.

### C-14 No way to finish paying after leaving checkout

- **As the client:** on the booking summary I press Proceed to Payment, then Cancel at "Confirm Payment: Are you sure you want to proceed to payment?" (or I close the Stripe page). The booking is already saved as pending. My Bookings shows only View Details and Cancel Booking, so I can't pay; the booking blocks the slot until it expires after 48 hours.
- **Steps to reproduce:** as Beatriz, book Quarry Road (Wedding, first package, 11 Nov 2026 10:00), press Proceed to Payment, then Cancel. Booking `BK-6ADD9A0FD3B8D` stays `pending` with a pending ₱2,370 payment and no pay button.
- **Cause:** the booking is saved before payment starts, and only partially paid confirmed bookings had a pay button (Pay Balance).
- **Fix:** My Bookings shows a **Complete payment** button for a pending booking with nothing paid and a pending payment. It asks "Pay ₱2,370.00 now to confirm booking …? Unpaid bookings are cancelled after 48 hours." and starts the existing Stripe checkout.
- **Also fixed:** `BookingController::initializePayment` accepted any booking id. It now only starts a payment for the logged-in client's own booking (404 otherwise) and refuses a cancelled booking ("This booking was cancelled, so it can no longer be paid.").
- **Test:** `tests/Feature/Booking/ResumeBookingPaymentTest.php` (another client gets 404; the owner of the booking gets the checkout link; a cancelled booking is refused). It failed before the fix.
- **Retest (local):** My Bookings showed Complete payment for `BK-6ADD9A0FD3B8D`. Pay now → Stripe test checkout with card 4242 4242 4242 4242 → "Payment Successful! Your booking has been confirmed", amount paid ₱2,370.00. The booking is `confirmed`, `partially_paid`, one succeeded payment, ₱5,530 remaining. Reloading the success page did not add a payment.
- **Retest (live):** Live: a booking left at "Confirm Payment" (`BK-6ADDCDBB01021`) showed **Complete payment** for ₱2,370.00 in My Bookings, with the 48-hour note; the test booking was then cancelled. Starting a payment for another client's booking (FS-BKS-00019) returns 404.

### C-15 A very large budget gives a server error

- **As the client:** I type 99,999,999,999 as my preferred budget and get "Something went wrong" instead of being told the limit.
- **Steps to reproduce:** as Beatriz, create a budget with `preferred_budget = 99999999999`. It returned 500 (`SQLSTATE[22003] … Out of range value for column 'preferred_budget'`).
- **Cause:** `tbl_client_budget.preferred_budget` is `decimal(10,2)`, but `StoreBudgetRequest` and `UpdateBudgetRequest` only required a number of at least 0.
- **Fix:** both requests allow at most 99,999,999.99 and say "The budget can be at most ₱99,999,999.99."
- **Test:** `BudgetMatchTest::test_budget_amount_must_fit_the_column` (failed before).
- **Retest (local):** the same request now returns 422 with that message; negative and text amounts still get their own messages; a 0 budget saves.
- **Retest (live):** Live: a budget of 99,999,999,999 returns 422 "The budget can be at most ₱99,999,999.99."

### C-16 Acting on a removed record answers with a server error (rest of the app)

- **As the client:** I double-click Delete on a budget, a service or a role, or approve something another tab already removed. The second request answers "Failed to delete …" as a server error.
- **Steps to reproduce:** as Beatriz, `DELETE /client/budget/3` (another client's budget) returned 500 "Failed to delete budget: No query results for model …"; as the owner, `DELETE /owner/roles/999999` and `/owner/employee/999999` returned 500. Another user's record was already refused; only the answer was wrong.
- **Cause:** the same pattern as C-05, in 95 more methods: `findOrFail()` / `firstOrFail()` inside a `try` whose `catch (\Exception)` answers 500.
- **Fix:** every such method in `app/Http/Controllers` that answers JSON now catches `ModelNotFoundException` first and returns `recordNotFound()` (404 "This record was not found. It may have been removed."). Where the method had opened a database transaction, the new catch rolls it back first (20 methods). Two tests that expected the old 500 (`GalleryLifecycleTest`, `PermitGateTest`) now expect 404.
- **Retest (local):** another client's budget update, delete and status toggle; the owner's role, service, payroll and employee deletes with a missing id; admin category and location deletes: all 404 with that message. The client and owner detail crawl still opens every own record (200). Full suite 755 passed.
- **Retest (live):** Live: deleting a missing budget (`/client/budget/999999`) returns 404 "This record was not found …"

### C-17 "Subscription required" for a record that is already gone

- **As the client (studio owner with two studios):** I click Delete twice on a service. The second click says "An active subscription or grace period is required for this studio," although both my studios are on active plans.
- **Steps to reproduce:** as Yolanda (Quarry Road and T02 Annex, both on plans), `DELETE /owner/services/999999` returned 403 with that message.
- **Cause:** `EnforceStudioSubscriptionAccess` looks up the studio from the record. When the record no longer exists and the owner has more than one studio, it could not pick a studio and refused every save.
- **Fix:** when no studio can be found for a save, it is allowed only if **every** studio the owner has is on an active plan or grace period, so no unsubscribed studio can be changed this way. The controller then answers its own 404. Pages (GET) keep the existing rule (any studio with access).
- **Test:** `SubscriptionAccessTest::test_an_unresolvable_write_is_allowed_only_when_every_owned_studio_has_access` (one studio on a plan and one without: refused; both on plans: allowed). `test_manage_access_fails_closed_when_studio_cannot_be_resolved` still passes.
- **Retest (local):** the same deletes now answer 404 "This record was not found. It may have been removed."
- **Retest (live):** Live: deleting a missing service (`/owner/services/999999`) returns 404. Yolanda has one studio on live (the plan allows one), so the two-studio case itself was confirmed locally.

### C-18 Profile save fails over a number I did not change (regression from C-10)

- **As the client:** I change only my name on My Profile and press Save. It says "Enter a complete mobile number, for example 0917 123 4567," but I never touched the number.
- **Steps to reproduce:** as Beatriz (stored number `+63918404400`), post the profile form unchanged to `/profile/update`. It returned 422.
- **Cause:** the C-10 rule was applied to every form, including edits. All seeded numbers are a digit short (`+63` and 9 digits), so the rule refused numbers already on file. Found by this test pass before it was pushed.
- **Fix:** `PhilippineMobileNumber` takes the number on file; an unchanged number passes, and a new or changed number must still be complete. The profile form and freelancer profile setup pass the logged-in user's number. Sign-up and the new-employee and new-photographer forms stay strict, because they create new people.
- **Test:** `tests/Feature/Auth/ProfileMobileNumberTest.php`.
- **Retest (local):** an unchanged save returns "Profile updated successfully!"; changing the number to `+63917` is refused. The other profile mistakes give clear messages: a taken email ("This email is already taken."), a wrong current password ("Current password is incorrect."), a mismatched confirmation ("Password confirmation does not match.") and a text file named `.jpg` ("Profile photo must be an image file.").
- **Retest (live):** Live: Beatriz's profile saved unchanged with her stored number `+63918404400` ("Profile updated successfully!").

### C-19 Chat error says "The body field is required."

- **As the client:** I press Send with only spaces in the box and read "The body field is required." I don't know what a "body field" is.
- **Steps to reproduce:** as Beatriz, open the conversation with Quarry Road (`/client/messages/1`), type spaces, press Send. The status line under the box shows the server's message.
- **Cause:** `ChatController::send` used Laravel's default messages, which name the input `body`.
- **Fix:** the chat says "Type a message first." and, over the limit, "A message can be at most 4,000 characters." The page already stops a truly empty box ("Please fill out this field.") and caps typing at 4,000 characters.
- **Also checked (no issue):** a message with `<script>` and `<b>` tags is stored and shown as plain text (`textContent`); messaging a client you have no booking with is refused (403).
- **Test:** `ChatTest` now asserts both messages (failed before).
- **Retest (local):** spaces + Send shows "Type a message first."
- **Retest (live):** Live: in the owner-to-client conversation for booking 76, a message of spaces returns 422 "Type a message first." (no message was sent).

### C-20 Double-clicking the favourite heart undoes itself

- **As the client:** I double-click the heart on a studio card. It fills, then empties again, or I get "Failed to update favorite."
- **Steps to reproduce:** on `/client/dashboard`, double-click a studio's heart while the first request is still on its way (held back 1.5 seconds in the test). Two toggle requests were sent.
- **Cause:** the click handler had no in-flight guard, and the server created the row with `create()`, so a racing second request could hit the `(client_id, studio_id)` unique key.
- **Fix:** the heart ignores clicks while its request is in flight; the server uses `firstOrCreate()`.
- **Retest (local):** a double-click with the first request held 1.5 seconds sent one request and the heart stayed filled; one more click removed the favourite again (test data restored).
- **Retest (live):** Live: a double-click on the first studio heart (first request held 1.5 seconds) sent one request and left it favourited; one more click put it back.

### C-21 Mistakes are reported as "Something went wrong"

- **As the client:** I submit a review with a rating of 0, or a review of two words. On the live site I see "Something went wrong. Please try again." and don't know what to change. The same for staff uploading a text file as a gallery photo.
- **Steps to reproduce:** as Beatriz, post a review for completed booking 64 with `rating = 0`. It returned 500 "Error submitting review: The rating field must be at least 1." With debug off, C-03's protection rewrites any 500 that has a colon to the generic message, so the reason was lost.
- **Cause:** 24 methods call `$request->validate()` inside a `try` whose `catch (\Exception)` also caught the `ValidationException` and answered 500. Among them: studio and freelancer reviews, gallery upload/delete/update for owner, HR, photographer and freelancer, the owner's photographer assignment and booking status change, balance payment and on-site confirmation.
- **Fix:** each of those catch-alls now lets a `ValidationException` through first, so Laravel answers 422 with the field errors. Reviewing your own booking before it is completed now says "You can leave a review once the booking is completed." instead of "not found". `SecurityRegressionTest` now expects 422 for non-image and oversized gallery uploads.
- **Test:** `ReviewLifecycleTest::test_review_mistakes_get_their_reason_not_a_server_error` (debug off; failed before).
- **Retest (local):** rating 0 → 422 "The rating field must be at least 1."; a two-letter review → 422 "The review text field must be at least 10 characters."; booking 63 (not completed) → 422 "You can leave a review once the booking is completed."; another client's booking → 404. Full suite 757 passed.
- **Retest (live):** Live: a review with rating 0 returns 422 "The rating field must be at least 1."

### C-22 Adding a photographer for a category with no service says "Something went wrong"

- **As the client (HR or owner):** I add a new photographer and pick "Food Photography" as the specialisation. The list offers every category, but the studio only has services in three of them, and I get a generic error.
- **Steps to reproduce:** as HR, on `/studio-hr/create/employee` choose Quarry Road, role Photographer and a specialisation with no service in that studio. The server threw `\Exception('No services found for the selected category.')`, which the catch-all returned as 500 "Failed to create employee: …"; on live that becomes "Something went wrong."
- **Cause:** the rule was thrown as a plain `Exception` and answered as a server error, in `StudioHR\EmployeeController`, `StudioOwner\EmployeeController` and `StudioOwner\StudioPhotographersController`.
- **Fix:** all three throw a `DomainException`, and their `store()` rolls back and answers 422: "There is no service in this category yet. Add one under Services first, then add the photographer."
- **Also checked (no issue):** error pop-ups on this form show the server's message (for example "Your Studio Growth Annual plan allows up to 5 staff and this studio has reached it. …"). "Failed to load permissions." found by the crawl is text inside a script, not on the page.
- **Test:** `EmployeeProvisioningTest::test_hr_adding_a_photographer_for_a_category_without_a_service_says_what_to_do` (debug off; failed before). In the local browser the same form stops earlier at Quarry Road's plan staff limit, which is the intended rule.
- **Retest (local):** test passes; full suite passes.
- **Retest (live):** Live: the same HR request stops first at Quarry Road's plan staff limit ("Your Studio Growth Annual plan allows up to 5 staff …"), so this message cannot be reached there without freeing a staff place; it is covered by the automated test with debug off. No employee was created.

### C-23 Admin "delete studio" crashes

- **As the client (admin):** calling the studio delete action shows a server error.
- **Steps to reproduce:** as admin, `DELETE /admin/studio/999999` returned 500: `Call to undefined method App\Http\Controllers\Admin\StudioController::destroy()`.
- **Cause:** the route `admin.studio.destroy` pointed at a method that does not exist. Its only use was a commented-out Delete button script in `admin/pending-studios.blade.php`, and Blade still evaluated the `route()` call inside that comment.
- **Fix:** removed the route and the 49 lines of commented-out script. Admins have no studio delete in the screens (they approve or reject); recorded as a gap like C-06.
- **Retest (local):** the action now answers 405 (no such action); `PendingStudiosReviewTest` passes.
- **Retest (live):** Live: `DELETE /admin/studio/999999` returns 405 (no such action) instead of a server error.

### C-24 Removed leave, overtime and chatbot records answer with a server error

- **As the client (HR, finance, photographer, owner):** I approve, cancel or delete a leave or overtime request that someone already removed; the page shows "Failed to process the leave request." as a server error. The same for deleting or switching off a chatbot answer.
- **Steps to reproduce:** the empty-form crawl: `POST /owner/hr-leave-requests/999999/approve`, `DELETE /studio-hr/leave-requests/999999`, `POST /studio-finance/overtime-requests/999999/cancel`, `DELETE /owner/chatbot/intents/999999` and 12 more all returned 500.
- **Cause:** the C-16 pattern, but the `findOrFail()` sits in a private helper (`getOwnedLeaveRequest()`, `ownedIntent()` and others), which the C-16 sweep did not follow.
- **Fix:** the same `ModelNotFoundException` → `recordNotFound()` catch, applied to the 37 methods that call such a helper inside a JSON try/catch, with a rollback where a transaction is open.
- **Retest (local):** the empty-form and missing-id crawl over every write action of all seven roles (233 requests) has no 500 left; each answers 422 (what to fill in), 404 (not found) or 403 (not allowed). Full suite 760 passed.
- **Retest (live):** Live: `DELETE /studio-hr/leave-requests/999999`, `POST /studio-finance/overtime-requests/999999/cancel` and the owner's approve of a missing HR leave all return 404 "This record was not found …"

### C-25 Clients see the owner's subscription message

- **As the client:** I try to book a studio whose plan has run out and read "An active subscription or grace period is required for this studio." That is the owner's problem; I don't know what to do.
- **Steps to reproduce:** any client booking call (`/client/bookings`, `/check-availability`, `/packages`, `/summary`) for a studio without an active plan or grace period.
- **Cause:** `EnforceStudioSubscriptionAccess` gave every refusal the owner's message and a link to the owner's subscription page.
- **Fix:** client routes get "This studio isn't taking bookings right now. Please choose another studio." and no owner link.
- **Test:** `SubscriptionAccessTest::test_a_client_booking_a_studio_without_a_plan_gets_a_client_message` (failed before).
- **Retest (local):** the client's booking calls without a studio now answer with that message.
- **Retest (live):** Live: booking checks for studios 2, 3, 5 and 6 (plans lapsed) answer "This studio isn't taking bookings right now. Please choose another studio."

### C-26 Duplicate and overlapping leave or overtime requests are accepted

- **As the client (photographer):** I press Submit twice on my vacation request for 1 to 3 December. Two pending requests appear for HR; I can also file 3 to 5 December on top of an approved request.
- **Steps to reproduce:** as Eduardo, post the same leave request twice and an overlapping one (`/studio-photographer/leave-requests`). All three were accepted (requests 16, 17 and 18).
- **Cause:** the leave and overtime forms checked only the dates themselves, never the person's other requests.
- **Fix:** new rule `App\Rules\NoOverlappingTimeOff` refuses a leave request that overlaps one of your own pending or approved requests ("You already have a pending or approved leave request for these dates."), and the same for overtime hours on one day. It is on all 12 store/update forms for HR, finance and photographers; an edit does not clash with itself, and rejected or cancelled requests do not count.
- **Also checked (no issue):** approving twice, rejecting after approval, and deleting or cancelling an approved request are all refused with clear messages ("Only pending leave requests can be processed.", "Approved leave requests cannot be deleted.").
- **Test:** `tests/Feature/StudioEmployee/TimeOffOverlapTest.php` (overlap, edit, rejected, overtime hours, and that all 12 forms use the rule).
- **Retest (local):** the same dates as approved request 16 and an overlapping range are refused with that message; a non-overlapping day is accepted; the same overtime hours twice are refused. Test requests 17, 18, 19 and overtime 21 were cancelled afterwards.
- **Retest (live):** Live: Eduardo filed leave for 21–22 Dec (request 16), the same dates again were refused with "You already have a pending or approved leave request for these dates.", and request 16 was cancelled.

### C-27 Very large procurement amounts give a server error

- **As the client (photographer or finance):** I type an extra zero or two in a quantity or a price and submit. On live I get "Something went wrong" instead of being told the amount is too large.
- **Steps to reproduce:** as Eduardo, submit a purchase request with quantity `999999999999` or unit cost `999999999999999`. Both returned 500 `SQLSTATE[22003] … Out of range value for column 'estimated_total'`.
- **Cause:** quantities are `decimal(10,2)` and amounts and totals `decimal(12,2)`, but the forms only required "a number above 0", and nothing checked quantity × cost.
- **Fix:** every procurement number has a maximum that fits its column: quantities 99,999,999.99; amounts 9,999,999,999.99. This covers request items, the purchase order's approved cost, the invoice amount, received quantity, acquisition cost and reorder threshold. The request forms also refuse a line or request total over ₱9,999,999,999.99, and name the fields in plain words ("quantity", not `items.0.quantity`).
- **Test:** `tests/Feature/Procurement/ProcurementAmountLimitsTest.php`.
- **Also checked end to end (no issue):** request 16 went photographer → finance review → owner approval → purchase order `PO-20261025-2127` → delivery → receipt → payment → Completed. Each wrong step is refused with a plain reason: approving twice, reject or return without a note, a purchase order before approval, an item from another request ("One or more procurement items are invalid."), delivery or payment without the required files, receiving a different quantity ("Partial deliveries are not supported …"), a consumable without a reorder threshold, and completing early or twice. Generating the purchase order twice reuses the same order (one order, one item).
- **Retest (local):** the three oversized requests are refused with 422 and the limit; the invoice amount `99999999999999` is refused. Full suite 764 passed.
- **Retest (live):** Live: HR quantity 999,999,999,999 and photographer unit cost 999,999,999,999,999 are refused with 422 and the limit; no request was saved.

### C-28 First visit opens the template customizer over the login form

- **As the client:** I open the site in a fresh browser and start entering my email and password. An unrelated "Admin Customizer" panel with "Buy Now" opens over the form, so Sign In cannot be clicked until I close it.
- **Steps to reproduce:** use a fresh browser context, visit `/auth/login`, enter credentials and wait at least 1.5 seconds. `#theme-settings-offcanvas` gains `show` without any settings click. Reproduced repeatedly; screenshot: `output/client-resume/login-probe.png`.
- **Cause:** `LayoutCustomizer.init()` in `public/assets/js/app.js` called `openCustomizer()`, which scheduled an automatic opening after one second when local storage lacked the template's first-visit flag. Auth pages include the same customizer as portals.
- **Fix:** removed the automatic-opening call and its unused first-visit methods. Manual Bootstrap offcanvas controls and theme handling remain. The previously single-line script was formatted so this source change is readable.
- **Regression proof:** `tests/Browser/first-visit-login.mjs` failed before the fix (`1 !== 0`, unsolicited panel visible), then passed: fresh visitor signs in successfully, manual settings open, Escape closes them.
- **Retest environment:** local Chrome only. No deployment or live retest claimed.

### C-29 Budget text becomes HTML and quotes break edit fields

- **As the client:** my budget name or notes contain angle brackets or quoted words. The list changes them into formatting; opening Edit can cut my name at the first quote. Saving can then lose part of what I typed.
- **Steps to reproduce:** on `/client/budget`, create a package budget for ₱12,500.50 named `Family <b>portraits</b> "October"` with notes `Keep <i>originals</i> & prints, not HTML.`. The freshly loaded row displayed `Family portraits "October"`, not the saved text. The edit form inserted the same unescaped name into its quoted `value` attribute.
- **Cause:** the AJAX table, details modal and edit modal interpolate user text directly into HTML. Server-rendered Blade rows escape it, but the AJAX rendering did not. Besides corrupting text, this permits stored markup injection.
- **Fix:** encode text at the HTML-rendering boundary for budget names, descriptions, categories, types and range text. Encode edit input/textarea values. Reuse already-rendered category options and select the saved category with `.val()` rather than placing category names inside executable template literals.
- **Regression proof:** `tests/Browser/client-budget.mjs` failed before the fix on the literal-name assertion. After the fix, a complete client interaction passed: create with decimal amount, view exact name/notes, Escape, edit exact quoted name, change amount to ₱13,000, filter inactive/active, cancel delete, confirm delete and verify row disappears.
- **Test data:** only budgets created by this test were deleted; existing budgets were untouched. Failed test attempts also cleaned up their created records.
- **Retest (live, 25 Oct 2026):** created a budget named `QA Live <b>check</b> "October"` with notes `Keep <i>text</i> & prints, not HTML.` on https://lumoraphotography.online. The list row showed that text literally, no bold or italic was created, the Budget Details window showed the same text, and the Edit field contained the exact name including quotes and angle brackets. Both test budgets were then deleted through the view-window Delete action; the list returned to its original two budgets. A delete request returned 200, a follow-up refresh showed the row gone.

### C-30 Filtering forgets the favourite change shown on this page

- **As the client:** I save a studio with the heart, then press Clear All or apply filters. The heart becomes empty again. Reloading restores it, so I cannot trust what the filtered list says.
- **Steps to reproduce:** on `/client/dashboard`, favourite a studio, then press `#clear-filters`. The AJAX result uses the original unfavourited state. `tests/Browser/client-favorites.mjs` failed before the fix (`false !== true`). Original test favourite state was restored after reproduction.
- **Cause:** `favoritedStudioIds` was initialized from the page but never updated by the successful toggle. Re-rendered cards read that stale array. The old per-button busy flag also disappeared when filtering replaced the button.
- **Fix:** successful toggles update the shared favourite IDs and the current matching card's icon. Pending toggles are tracked by studio ID rather than by a replaceable DOM node.
- **Retest (local Chrome):** double-click sends one request (C-20 regression), Clear All preserves the new heart, reload preserves the saved state, and filtering during a delayed toggle still updates the replacement card and ignores the duplicate click. The test restores the original favourite state.
- **Retest (live, 25 Oct 2026):** the client account had no favourites. Clicking the heart on Little Panda Portraits filled it, pressing Clear All kept it filled in the rebuilt card list, and a full page reload still showed it saved with the studio in My Favorites. Clicking the heart again removed it, and after reload no card was filled and My Favorites was gone. Test state fully restored.

### C-31 Filtered cards put a favourite heart under the next card

- **As the client:** after filtering, I try clicking a heart and nothing happens. It has spilled outside its card and the next studio's photograph covers it.
- **Steps to reproduce:** at a 1280px browser viewport, clear discovery filters, then click the studio 6 heart in the rebuilt cards. Browser click repeatedly reports the next card's `Quarry Road Studio` image intercepting pointer events. Found by the delayed-save step in `tests/Browser/client-favorites.mjs`.
- **Cause:** server-rendered card text containers have `min-width: 0`, but the AJAX template omitted it. The flex item's intrinsic width pushed the heart outside its card.
- **Fix:** apply the same existing `min-width: 0` constraint to AJAX-rendered card text containers. No forced clicks or overlay workarounds were used in the passing test.
- **Retest (local Chrome):** the same filtered heart can be clicked normally, including while a delayed save is in progress; the complete favourites interaction test passes.
- **Live:** not deployed or retested.

### C-32 An old filter response overwrites the current discovery results

- **As the client:** I try a low price limit, then clear it while the connection is slow. Studios briefly reappear, then disappear again even though the price box is empty.
- **Steps to reproduce:** submit maximum price ₱1, delay that request by 1.8 seconds, then press Clear All. Let the cleared result arrive first. The delayed empty result replaced all 10 current provider cards. `tests/Browser/client-discovery.mjs` failed with `0 !== 10` before the fix.
- **Cause:** every `applyFilters()` response could replace cards, counts and loading/error state regardless of newer filter requests.
- **Fix:** each filter request gets a sequence number; only the latest response or failure may update the page.
- **Retest (local Chrome):** low-price empty state, Clear All restoration and the delayed-old-response sequence all pass. No data was changed.
- **Live:** not deployed or retested.

### C-33 A delayed package response clears the selected booking package

- **As the client:** I select a package and finish the dates and terms. Proceed to Summary unexpectedly says "Package Required", because another package response silently cleared my choice.
- **Steps to reproduce:** let the budget-based initial package request start, hold its response for two seconds, select a category to load current packages and select a package. Release the original response. `tests/Browser/client-packages-race.mjs` failed with `0 !== 1` selected radios. The ordinary booking test also caught the "Package Required" alert intermittently before this deterministic reproduction.
- **Cause:** every category response rebuilt the package list and reset `selectedPackageId`; the page's delayed automatic category load could also duplicate an immediate user selection.
- **Fix:** sequence package requests so stale success/error callbacks are ignored; clear selected package when changing category, not in a stale callback; skip the delayed automatic load if a category request has already started.
- **Retest (local Chrome):** the delayed initial response no longer clears the selected radio. The full tested booking-to-summary flow passes again, including time changes, closed-hours refusal, stale availability, calendar and summary close.
- **Live:** not deployed or retested.

### C-34 Successful rescheduling loses keyboard focus inside the booking window

- **As the client:** I accept the studio's offered time, then press Escape to close Booking Details. Nothing happens. Keyboard focus left the window when the request form disappeared.
- **Steps to reproduce:** in a paid test booking, request a new time, let the owner offer an alternative, accept it, then press Escape. The test timed out waiting for focus to remain inside `#bookingModal` after the successful update.
- **Cause:** `mountReschedule()` replaces the panel's HTML after saving. This removes the focused submit button and leaves focus on the document body, outside Bootstrap's modal keyboard handler.
- **Fix:** focus the saved-status message inside the updated panel, with `tabIndex = -1` and `preventScroll`. Initial panel loading does not steal focus.
- **Retest (local Chrome):** repeated the owner-offer/customer-accept flow; focus remains inside the booking window, Escape closes it, and balance payment succeeds afterwards.
- **Live:** not deployed or retested.

### C-40 Deployed JavaScript stays stale in returning browsers

- **As the client (live):** after the login-overlay fix was deployed, a normal browser still opened the Admin Customizer because it served the old `app.js` from its cache.
- **Steps to reproduce:** after deployment, clear cookies and local storage but use the normal browser cache. Load `/auth/login` and wait two seconds. The panel still appeared. With Chrome cache disabled, the same page loaded the new script and the panel did not appear.
- **Cause:** every layout linked `assets/js/app.js` without a version. The filename did not change between releases, so browser and CDN caches could keep the old script.
- **Fix:** append the file modification time to the `app.js` URL in the auth layout, the shared portal scripts partial, and the landing page. The test asserts the version expression exists in all three.
- **Test:** `PortalLayoutRegressionTest::test_the_shared_app_script_is_versioned_for_browser_cache_updates` failed before the change, then passed. Full suite: 768 tests, 3,910 assertions. Build passed.
- **Retest (live):** Hostinger deployed commit **b3a3f561**. The live login page now references `assets/js/app.js?v=1792932923`. With ordinary browser caching, cleared cookies and cleared local storage, the fixed page showed no customizer and no Buy Now link.

### C-39 "Expiring Soon" appears on a plan with months left

- **As the studio owner:** my Studio Growth Annual plan is valid until July 23, 2027, but the subscription page shows an orange Expiring Soon badge in October 2026.
- **Steps to reproduce:** open `/owner/view/subscription` for Quarry Road on October 25, 2026. The summary shows 271 days remaining and the badge. `tests/Browser/owner-subscription-badge.mjs` failed with "A plan with 271 days left must not say Expiring Soon".
- **Cause:** Carbon 3 returns a signed difference. `end_date->diffInDays(now())` returned -271 for a future end date, and `-271 <= 7` was true. The warning therefore appeared for every future expiry.
- **Fix:** calculate `now()->diffInDays($end_date, false)` and show the badge only when the result is between 0 and 7 days.
- **Retest (local Chrome):** the same plan shows no Expiring Soon badge with 271 days left. Plan Details and Subscription History both opened and showed the plan limits, prices and history rows.
- **Live:** not deployed or retested.

### C-38 Saving a hardcopy quote loses keyboard focus in Booking Details

- **As the studio owner:** I create a hardcopy quote, then press Escape to close Booking Details. Nothing happens because the focused Save button disappeared when the quote section reloaded.
- **Steps to reproduce:** on the owner's confirmed booking **BK-6ADC6C55376A8**, enter a 500-peso print quote, click Create hardcopy quote, then press Escape. The modal stayed open and `document.activeElement` was `BODY`, outside `#bookingModal`. This was the same focus pattern as C-34 but in the hardcopy AJAX section.
- **Cause:** `partials/hardcopy-script.blade.php` replaced the section and prepended a status message but did not restore focus inside the open modal.
- **Fix:** make the saved-status message focusable and focus it after the section reloads.
- **Retest (local Chrome):** created a new 300-peso test quote **Order #5** on **BK-6ADD9A0FD3B8D**, confirmed focus was on the in-modal "Hardcopy order saved" message, and Escape closed the window. Reopened and cancelled that test quote; its row shows Cancelled. The earlier accepted 500-peso quote remains on **BK-6ADC6C55376A8** as local test data and raised that booking's displayed balance to ₱10,100.00.
- **Live:** not deployed or retested.

### C-37 Clicking an admin user's name does nothing

- **As the client (admin):** I click a person's name in Registered Users because it looks like a link. Nothing opens. The small eye button in Actions opens the profile.
- **Steps to reproduce:** on `/admin/view/users`, click the name link `.link-reset.view-user`. The detail request loads, but `#userModal` never gains `show`. `tests/Browser/admin-user-name-link.mjs` failed waiting for the modal.
- **Cause:** the name link and the Actions eye button shared the `.view-user` data loader, but only the eye button had Bootstrap's `data-bs-toggle="modal" data-bs-target="#userModal"` attributes. The loader changed hidden modal content without opening it.
- **Fix:** give the name link the same modal attributes. No JavaScript or data endpoint changed.
- **Retest (local Chrome):** the name opens the correct profile after its details load, Escape closes it, and the eye action still opens the same modal.
- **Live:** not deployed or retested.
+
+### C-36 Payroll deducts more than the employee earned

- **As the client (HR/finance):** I generate payroll for an employee with no attendance in the period. The generated record shows gross ₱0.00, deductions ₱32,375.00 and net ₱0.00. The deductions include absence plus fixed contributions, and net is silently held at zero.
- **Steps to reproduce:** as HR Zacarias, open Generate Payroll, choose Quarry Road, Regular Employee, October 1–25, load employees, select Zacarias, generate, then view the payroll on the HR or finance page. Before the fix: **PAY-20261025-WRYO6H** showed that mismatch.
- **Cause:** `GeneratePayrollController` calculated deductions independently of gross earnings, summed them for display, then clamped only `net_amount` to zero. The saved deduction breakdown could therefore exceed gross.
- **Fix:** `PayrollDeductionService::capToGross()` now caps absence deduction first, then other deductions to the remaining gross; all saved deduction lines sum to the displayed total and never exceed gross. Used by payroll generation before totals are calculated.
- **TDD evidence:** `tests/Unit/PayrollDeductionCapTest.php` failed with the class missing, then passed after implementation (2 tests, 6 assertions).
- **Retest (local browser):** generated a new payroll for Alfonso, **PAY-20261025-6JOQJT**. It showed gross ₱0.00, deductions ₱0.00 and net ₱0.00. Finance opened details and approved it. Submitting a rejection with a missing reason kept the rejection dialog open and showed the browser's required-field message; no rejection was recorded.
- **Test-data note:** the earlier broken record **PAY-20261025-WRYO6H** remains as local evidence created before the fix; no attempt was made to rewrite its stored history. The new record and approval are test data.
- **Live:** not deployed or retested.
+
+### C-35 Recovery-page controls appear behind the sidebar

- **As the studio owner:** I open the test booking from Refund Queue and enter a refund amount. Save refund target cannot be clicked because the Members navigation item covers it.
- **Steps to reproduce:** at the normal desktop viewport, open the owner's recovery page for booking 68 and click Save refund target. The browser repeatedly reports a `.sidenav-menu` link intercepting the click. No forced clicks were used.
- **Cause:** the owner recovery view lacked the portal's `.content-page` wrapper, so the fixed sidebar overlaid its content. The client photographer-replacement view had the same missing wrapper.
- **Fix:** wrap both recovery pages in the existing `.content-page` layout offset. No sidebar positioning or global styling was changed.
- **Regression proof:** `PortalLayoutRegressionTest::test_cancellation_recovery_pages_keep_actions_clear_of_the_sidebar` failed before the fix, then passed for both templates.
- **Retest (local Chrome):** the owner clicked Save refund target normally. The full refund journey then completed through admin recording and customer confirmation. The client replacement layout is covered by the regression test; active replacement acceptance/decline remains on the broader walkthrough checklist.
- **Retest (live, 25 Oct 2026):** opened owner recovery record 6. The content area starts exactly where the sidebar ends (240px and 240px), no visible control sat under the Members navigation, and nothing intercepted a click at any button centre. The same record loads for the client at `/client/cancellation-recoveries/6`.

### C-41 Recovery pages show "Default Title" in the browser tab

- **As the studio owner or client:** I have several tabs open. The recovery tab says "Default Title", so I cannot tell which page it is.
- **Steps to reproduce:** on live, open `/owner/cancellation-recoveries/6` as the owner or `/client/cancellation-recoveries/6` as that booking's client. The heading is correct ("Photographer cancellation recovery" or "Photographer replacement"), but `document.title` is "Default Title", the layout's fallback.
- **Cause:** both recovery views were the only portal pages that extend a layout and define `@section('content')` without defining `@section('title')`.
- **Fix:** add `Photographer Cancellation Recovery` and `Photographer Replacement` title sections. No other page changed.
- **Regression proof:** new `PortalLayoutRegressionTest::test_every_portal_page_sets_its_browser_title` scans every Blade view that extends a layout, and fails with the list of pages that have no title section. It passes after the fix.
- **Test:** full suite 769 tests, 3,911 assertions; one pre-existing PHPUnit metadata deprecation remains.
- **Retest (live, 25 Oct 2026):** deployed in **865d2429**. The owner page `/owner/cancellation-recoveries/6` now titles its tab "Photographer Cancellation Recovery", and the client page titles "Photographer Replacement". Both return 200.

## Previous agent's final full pass (25 Oct 2026)

Result: **no new issues.** Every issue C-01 to C-27 is Fixed and retested.

- **Automated tests:** 764 passed (3,894 assertions).
- **Local, all 7 roles** (admin, owner, HR, finance, photographer, client, freelancer):
  - Every page without an ID loads with no server error.
  - Every detail page answers 200, or 403/404 for a missing or other user's record. No other user's data is shown: budget 1, conversation 1 and the plan behind `/owner/subscription/1` belong to the signed-in user or are public.
  - Every form action, sent empty or with a missing ID, answers with a plain validation or "not found" message. None gives a server error.
- **Live, all 7 roles:** every page loads with no server error.
  - Client `/client/messages` failed once because of the network, then loaded four times in a row.
- **Live end-to-end checks:** booking times and studio hours, Complete payment, chat, favourites, freelancer service, leave overlap and procurement limits (see each issue above).
- **Not an error:** the profile page of every portal contains the words "Server Error", but only as the title of an error pop-up in the page script.

## Resumed verification (25 Oct 2026)

**Status: In progress.** The previous pass above is historical evidence, not a new claim that every feature has been exercised in this resumed session. C-01 through C-27 retain their recorded historical statuses until new evidence reopens an issue.

- Baseline: commit `554d81a9`. Existing browser scripts, screenshots and uploaded files preserved.
- Scope: resume functional client testing without redesigning the platform. New fixes require reproduction, root-cause analysis, a regression test and a client retest.
- Safety: local testing only for now. No production writes, deployment or Git push authorized by this continuation.
- Browser setup: shared browser tool reported its Chrome profile already in use. Resumed tests use a separate, temporary Chrome context instead of closing another session's browser.
- Fresh checks launched: complete PHPUnit suite (JUnit evidence: `output/client-retest-junit.xml`), production asset build, actual page navigation across all seven portals (`tests/Browser/client-resume.mjs`, evidence: `output/client-resume/pages.json`). Page navigation is only a baseline; it is not a substitute for submitting and completing workflows.
- Completion gate remains open: record client interactions and edge cases for every feature, resolve and retest all new issues, then record a final full pass with zero new issues in the transcript.

### Fresh results recorded during continuation

- **PHPUnit:** 764 passed, 3,894 assertions, after all six resumed fixes. One pre-existing runner deprecation remains: doc-comment metadata in `PortalMiddlewareDownloadTest` (not a client-facing failure).
- **Build:** `npm run build` passed. `node --check public/assets/js/app.js` passed.
- **Page navigation:** first crawl passed 144 URLs; final explicit HTML-page inventory passed 131 pages across all seven roles with no server failures, visible exception pages or uncaught JavaScript errors. The smaller inventory omits JSON/data endpoints from the original list, not failed pages. Evidence: `tests/Browser/portal-pages.json` and `output/client-resume/pages.json`.
- **Client booking interaction:** `tests/Browser/client-booking.mjs` passed package selection, duration calculation, C-11 time-change retest, C-12 opening/closing boundary refusals, end-before-start validation, C-13 delayed-response retest, availability calendar next month/Escape, and priced summary open/close. No booking or payment was submitted. Test-only Escape timing failures were corrected by waiting for Bootstrap modal focus. The separate intermittent "Package Required" failure was a real defect, reproduced and fixed under C-33.
- **Client budget interaction:** full create/view/edit/filter/cancel-delete/delete pass recorded under C-29.
- **First-visit login and manual settings:** passed under C-28.
- **Discovery/favourites:** passed C-20 double-click regression, favourite persistence across filters/reload, normal filtered-card clickability and delayed-save/re-render duplicate protection (C-30 and C-31).
- **All seven roles, theme/mobile smoke checks:** `tests/Browser/portal-controls.mjs` passed fresh-visit login without unsolicited settings, manual settings open/close, dark/light changes, theme persistence after reload, and no horizontal page overflow on each dashboard/profile at 390px. This does not claim a full accessibility or all-page mobile audit.
- **Staff leave requests:** `tests/Browser/staff-leave.mjs` passed create, duplicate-date refusal (C-26), details, edit without self-overlap, dismiss cancellation, confirm cancellation and delete for photographer, HR and finance. Only this test's three leave requests were deleted; existing requests were untouched. Approval chains remain to be repeated in the browser.
- **Discovery and package races:** C-32 and C-33 regression tests passed after failing before their respective fixes.

### Latest verification checkpoint (not the full-platform completion gate)

After the last application fix, all nine browser scripts ran consecutively with exit code 0 and no new issues in their tested coverage: first-visit login, budgets, package-response race, booking form/summary, favourites, discovery-response race, three-role leave workflow, seven-role theme/mobile smoke checks, and the 131-page portal crawl. Per-script transcripts are saved under `output/client-resume/*.mjs.log`; the same results are recorded in the conversation transcript. The full PHPUnit suite also passed again (764 tests, 3,894 assertions). This is a clean pass of the currently implemented browser checks, **not** a claim that every platform feature has been tested end to end.

Browser scripts live in `tests/Browser/`. They expect the running local server and seeded development database, Chrome, and `PLAYWRIGHT_MODULE` pointing to the installed Playwright `index.mjs`. Run each with `node tests/Browser/<script>.mjs`. No new application dependencies were installed.

### Current checkpoint after the direct browser round

- **New issues:** C-34 through C-40. C-28 to C-33 were the earlier resumed fixes. All are recorded above as fixed; live status is listed in the deployment section.
- **Fresh automated verification after C-40:** 768 PHPUnit tests passed, 3,910 assertions; production build passed; `git diff --check` passed apart from an expected CRLF notice. One pre-existing PHPUnit metadata deprecation remains.
- **Fresh Playwright checks after the last fix:** admin user name and eye links open the correct modal and Escape closes it; the subscription badge is absent with 271 days remaining; the gallery ZIP returns 200 with a 231,930-byte attachment. The hardcopy focus fix was retested interactively with a temporary quote, then that quote was cancelled.
- **Direct seven-role browser sweep:** logged in as admin, owner, HR, finance, photographer, client and freelancer, then opened 74 primary pages through Playwright. Result: 74 checked, 0 failures. No server error page or leaked exception text appeared.
- **Scope honesty:** the sweep proves the main pages load for each role. It does not replace the remaining interactive workflows listed above.
- **Direct browser journey coverage now includes:** login/first visit, seven-role page navigation, client discovery/filters/favourites, budgets CRUD, booking creation, checkout abandonment/resume, deposit and balance test payments, rescheduling request/refusal/alternative/acceptance, cancellation, owner target and real Stripe test refunds, admin refund evidence, procurement draft→completion, attendance history, overtime creation/approval, payroll generation/approval/rejection validation, messaging, notifications, gallery approval/view/download, equipment assignment, hardcopy quote creation/acceptance/focus, freelancer services/packages, admin review flag/restore, category create/delete and user details.
- **Test data:** procurement **PR-20261025-3754**, po **PO-20261025-0089**, overtime **OT-20261025195502-WUN8O**, payroll **PAY-20261025-WRYO6H** (pre-fix evidence) and **PAY-20261025-6JOQJT**, hardcopy **Order #4** accepted and **Order #5** cancelled, and the approved seed gallery **FS-BKS-00021**. The cancelled/refunded booking **BK-6ADDE413313F5** remains as transaction evidence. These are local only.
- **Status:** all issues found in this direct browser round are fixed and retested; the platform-wide acceptance gate is still open because the remaining checklist above has not been exercised.

### Remaining acceptance coverage (not yet claimed complete)

- Attendance clock-in/out and late/undertime creation through the live clock, which today is blocked because the selected studio schedule does not include Sunday.
- Employee/invitation creation, role and permission changes, studio-schedule edits, payroll-settings create/update, and the permission-failure paths around them.
- Subscription purchase, renewal, cancellation, trial expiry, and plan change through checkout; only plan details, history and the expiry badge have been exercised.
- Admin studio approve/reject, freelancer management, location create/delete, booking-report moderation, category edit (create/delete passed), and subscription administration.
- New gallery upload to a fresh, unpublished booking; gallery revision request/accept on a booking whose revision window is still open; hardcopy courier/address validation; equipment removal and photographer confirmation; review/report submission and moderation response.
- Chatbot start/message/end/history/helpful flows; the launcher was not present on the tested client dashboard or notifications pages.
- Full interactive cross-role messaging checks beyond the client/owner conversation, plus notification links from each portal.
- A final full pass after the last fix. The current run passed the implemented checks, but this checklist is not empty.

## Continued transaction testing (25 Oct 2026, evening)

The following checks used actual browser controls, not direct writes to booking records. New test booking **68**, **BK-6ADDE413313F5**, belongs to the seeded client Beatriz and Quarry Road Studio. Existing customer bookings were not changed.

- **Safety:** confirmed the local database host and both Stripe test-mode configuration and `sk_test_` key before payment. Only Stripe's 4242 test card was used. No real charge or production write occurred. Mail configuration is not currently `log`, so account creation, studio moderation and other email-sending flows were not attempted during this round.
- **Booking creation:** selected package, January 20, 2027, 10:00–13:00, accepted terms and submitted the booking through its summary.
- **Checkout abandonment:** cancelled the first payment confirmation, opened My Bookings, saw Complete payment, cancelled that dialog once, and then resumed checkout successfully.
- **Deposit:** Stripe test checkout succeeded for ₱2,370. The booking became confirmed and partially paid. Reloading the payment success page did not create another payment.
- **Rescheduling:** customer requested January 21 at 07:00–10:00. Requests can be submitted for provider review, but the owner cannot approve an unavailable time: approving this request showed the studio-hours refusal. The owner offered 10:00–13:00 instead; the customer accepted and saw the new schedule. This is intentional provider-review behavior, not an application defect.
- **Focus regression:** C-34 was found, fixed and retested during that flow.
- **Balance payment:** customer paid the remaining ₱5,530 through Stripe test checkout. The booking became paid, with exactly two succeeded payments totaling ₱7,900.
- **Cancellation:** the dialog explained that the studio decides the refund up to ₱7,900. Keep booking preserved it. A missing reason returned 422. Entering a reason cancelled the booking successfully, and the customer's Refunds page showed the request.
- **Evidence:** state and booking ID are in `output/client-resume/payment-journey.json`; browser scripts are `booking-payment.mjs`, `booking-after-payment.mjs` and `booking-cancellation.mjs` under `tests/Browser/`.

### Resolved environment interruption: local MySQL stopped before refund processing

- **Observed:** `/auth/login` returned HTTP 500. The application log reports MySQL connection refused (`SQLSTATE[HY000] [2002]`) while reading the session. Port 3306 is no longer listening, and no MySQL/MariaDB process or registered Windows service is running. PHP still listens on port 8000.
- **Not an application fix:** no source or database configuration was changed to hide this failure. Both XAMPP and Laragon are installed; the correct previous database instance must be identified before starting one.
- **At interruption:** owner refund target, actual Stripe test refunds, admin evidence recording and customer refunded-status confirmation were pending. `booking-refund.mjs` initially stopped at login before changing a refund target or issuing a refund.
- **Prepared:** `create-test-refunds.php` refuses live keys, non-local databases, unrelated bookings and live/unpaid payment intents. It creates real Stripe test refund references rather than fabricated evidence. Do not call it until the existing local database instance is restored.
- **Resolution:** Laragon's MySQL error log identifies the exact former process ID (26092) observed at the start of testing. Restarted that same MySQL 8.4.3 installation with its existing `my.ini` and `C:/laragon/data/mysql-8.4` directory. No database was recreated, migrated or reseeded. The site resumed working and refund testing continued.
- **Current status:** this environment blocker is resolved. The complete-platform final pass remains outstanding.

### Transaction journey completed locally

- **Owner:** opened the booking from Refund Queue, selected ₱7,900 and saved the target with an ordinary click. This revealed and verified C-35.
- **Provider-side refund:** `create-test-refunds.php` used the stored test checkout sessions to identify their test payment intents, then created two genuine Stripe test-mode refunds, ₱2,370 and ₱5,530. Both returned `succeeded`. Idempotency keys prevent a repeated helper call from issuing another refund. Evidence: `output/client-resume/refund-evidence.json`.
- **Admin:** saw the correct ₱7,900 target, tried reusing one refund reference for both payments and received 422, then entered each genuine refund reference. Recording succeeded, and the booking left the pending refund queue.
- **Customer:** Refunds shows the booking as refunded. Details confirm the booking stays cancelled, its payment status is refunded, and both payments are refunded.
- **Final transaction state:** `BK-6ADDE413313F5` (68) is cancelled and fully refunded in test mode. No real funds or production records were changed. Test booking, payments, recovery history and in-app notifications remain as audit evidence.
- **Coverage completed in this round:** actual booking submission → abandoned checkout → resumed test deposit → owner-reviewed reschedule with an alternative time → balance payment → cancel/keep and missing-reason checks → cancellation → owner refund target → actual test refunds → admin evidence → customer refund confirmation. Photo delivery and the other checklist areas still require separate journeys.
- **Fresh regression verification after C-34/C-35:** 765 PHPUnit tests passed, 3,898 assertions; production asset build passed; `git diff --check` passed. The existing PHPUnit doc-comment deprecation remains. These results do not close the full-platform acceptance checklist.

## Live website verification (25 Oct 2026)

**Host:** https://lumoraphotography.online. **Method:** Playwright browser against the deployed site. **Safety:** read-only checks only. No bookings, payments, messages, invitations, approvals, deletions, account changes or refunds were submitted. No email was sent.

- **Page load:** logged in as the seven seeded roles. Opened 74 primary pages across admin, owner, HR, finance, photographer, client and freelancer. Result: 74 checked, 0 server failures and no leaked exception text.
- **C-28 still present live:** after clearing cookies and local storage, the Admin Customizer panel opened over `/auth/login` by itself and showed the Buy Now link. The deployed site does not contain the local fix.
- **C-37 still present live:** on `/admin/view/users`, clicking the first user's name, **Probe Deploy**, loaded that user's details into the hidden modal but did not show the modal. The fix is local only.
- **C-39 still present live:** the owner subscription page showed **Studio Growth Annual**, valid until **Jul 23, 2027**, with **Expiring Soon**. The deployed site does not contain the local badge fix.
- **C-31 live observation:** after Clear All on client discovery, the first favourite heart measured about 12 pixels beyond its card boundary. It was still clickable at this viewport, unlike the local overlap that blocked clicks. No client write was used to test the full favourites interaction.
- **Not tested live due to safety:** C-29 budget creation cleanup, C-30/C-31 favourite toggling, C-32/C-33 delayed requests, C-34 reschedule actions, C-35 refund targets, C-36 payroll generation, C-38 hardcopy creation. These all require writes to the live database. The local fixes therefore remain unverified on live.
- **Cleanup note confirmed live:** the **Probe Deploy** account is still present in Registered Users. It is the account previously recorded for owner cleanup. It was not deleted during this read-only pass.
- **Deployment status at first pass:** the local fixes were uncommitted and undeployed. Live still behaved like the pre-fix code for C-28, C-37 and C-39.

### Live retest after deployment

Deployment source: commit **2ec9e140** was pushed to `main` and Hostinger deployed it. The read-only login check still showed the customizer because the browser cache kept the old unversioned `app.js`. That exposed **C-40**. Commit **b3a3f561** added a file-version query to the script URL and was pushed. Hostinger deployed it.

- **C-40:** live login HTML now references `assets/js/app.js?v=1792932923`. A normal-cache browser with cookies and local storage cleared loaded the new file.
- **C-28:** after deployment, the fresh live login page showed no Admin Customizer and no Buy Now link after 2.4 seconds. Sign In was usable.
- **C-37:** on live, clicking the first user's name opened the User Profile modal, loaded the correct name, and Escape closed it after the opening transition.
- **C-39:** the live annual plan showed "Valid until Jul 23, 2027" without "Expiring Soon".
- **C-31:** live filtered discovery cards kept the first heart inside its card, with zero horizontal overflow and a working click target.
- **C-32:** live old price-filter response could not replace the cleared results. 3 cards remained after the delayed response, with no empty state.
- **C-33:** live package selection **31** remained selected after the delayed first package response arrived.
- **Seven-role live sweep:** 74 primary pages loaded after deployment with 0 failures.

### Live write tests after approval (25 Oct 2026)

The user approved live write tests on seeded accounts with cleanup, no real money and no email.

- **C-29 (live):** created `QA Live <b>check</b> "October"` with `Keep <i>text</i> & prints, not HTML.`. The list, the details window and the edit field all showed the literal text with quotes intact; no formatting was created. The budget and a second delete probe were both deleted; the original two budgets remain.
- **C-30 (live):** saved Little Panda Portraits, pressed Clear All, the heart stayed filled in the rebuilt list, and a reload kept it in My Favorites. Removing the favourite and reloading returned the account to no favourites.
- **C-31 (live):** with the rebuilt filtered/cleared card list, the first heart stayed inside its card (0px overflow) and its centre hit the heart itself, not the next card.
- **C-35 (live):** owner record 6 and the client view of the same record both use the content-page offset. Content begins at 240px, exactly where the sidebar ends; no control was covered and every tested button centre hit its own button.
- **C-41 (live):** found here. Both recovery pages showed the browser title "Default Title". Fixed and covered by a new scan test; live retest follows deployment.
- **C-34 (live, blocked):** the only reschedulable-looking live booking, BK-6ADCE2471C46E, reports "This booking can no longer be rescheduled", so the accepted-offer focus path cannot be reached on live data.
- **C-36 (live, not run):** generating payroll writes permanent payroll records and there is no delete route. Creating them on the live studio would leave real payroll history and could be approved by finance, so it was not done without explicit consent.
- **C-38 (live, blocked):** the only in-progress live booking already has a client-chosen pending hardcopy order (**Order #9**), which hides the owner quote form. Clearing it would mean cancelling a real client's order, so it was not done.

These three remaining unproven cases are blocked by live data or by an irreversible financial side effect, not by a known defect.

## Direct Playwright browser walkthrough (user-requested, no shell-run tests)

**Status: In progress.** This round uses Playwright browser tools directly to click, type and inspect rendered screens. Previous shell-run browser scripts are not being counted as this requested walkthrough.

### Procurement, photographer Eduardo

- Logged in through the visible login form, expanded Request and clicked Request Procurement.
- Submitted an empty request: a Request Error dialog listed the missing purpose, date, item, quantity, unit and cost. The expected validation request returned 422; this is not a JavaScript crash.
- Entered a local test request for two 128GB SD cards at ₱1,500 each, required November 20. Saved Draft.
- Queue shows **PR-20261025-3754**, request **17**, Draft, ₱3,000. Opened its action menu and clicked View: details show the correct item, quantity, unit price, total and Draft Saved timeline entry.
- Screenshots/snapshots are recorded by the browser tools under `.playwright-mcp/`, including `page-2026-10-25T11-35-51-787Z.yml` for opening details.
- Edited the draft, clicked Add Item, confirmed a second row appeared, removed it, and confirmed one row remained. Submitted the request; queue changed to Pending Finance Review.
- Finance: clicked View, tried Return without a note and saw "A note is required for this action." Cancelled. Approved with a note; queue changed to Pending Owner Approval.
- Owner: clicked View, confirmed finance note, approved with a note; queue changed to Approved.
- Finance: generated PO **PO-20261025-0089** for a local test supplier, changed approved unit cost to ₱1,400 (approved total ₱2,800), then recorded delivery. Saving without a receipt was blocked by the file requirement. Added a test-only marked PNG and delivery note; queue changed to Delivered.
- Photographer: opened details, confirmed the delivery file. Tried receiving 3 of 2 and saw "Partial deliveries are not supported in this workflow. Received quantities must match ordered quantities." Tried receiving equipment without asset fields and saw the required serial/acquisition/location message. Entered serial **TEST-SD-PAIR-001**, acquisition cost, and a local-test cabinet, then confirmed receipt; queue changed to Received.
- Finance: confirmed the payment statement and approved total, tried Start Payment Processing without documents and was blocked by the two required file inputs. Added clearly named test-only invoice/proof files, invoice **TEST-INV-20261025-01**, payment **TEST-PAY-20261025-01**, and started payment processing. Queue changed to Payment Processing. Clicked Complete, confirmed "Complete Procurement?", and the queue changed to Completed.
- Result: procurement completed end to end through rendered browser controls. The uploaded files and records are local test-only; no real supplier, payment or delivery took place. Empty-request, missing-note, partial-receipt and missing-document paths all gave plain, actionable messages.

### Payroll, HR through finance

- HR loaded two regular employees, generated a payroll for each, and saw both new records under View Generated Payrolls.
- This exposed C-36: the first new record showed ₱32,375.00 deductions against ₱0.00 gross. Fixed and recorded above.
- Generated a fresh record after the fix: **PAY-20261025-6JOQJT** showed ₱0.00 gross, ₱0.00 deductions, ₱0.00 net and a complete attendance/deduction breakdown.
- Finance opened the details, approved it, and the row changed to Approved with Alfonso as reviewer. Finance then opened a separate pending record, chose Reject, and submitting without a reason kept the reason dialog open with the browser's required-field message. No rejection was submitted.
- The payroll approval page's loading placeholder was briefly visible after a rapid modal close/reopen; a normal reload and open showed the complete record. The automated/browser interaction that caused the stale open did not reproduce after reload, so no application change is claimed for that transient test sequence.

### Attendance and overtime

- Photographer attendance loaded the selected Quarry Road Studio, showed the live clock, schedule and geofence, kept Check-In/Check-Out disabled because today is not one of the studio's operating days, and showed 23 own history records. Opening the August 13 late record showed check-in 09:12 AM, 12 minutes late, check-out 06:00 PM, and total 08:48:00.
- Initially the page briefly showed "Waiting for selection" while Quarry Road was already selected. A reload showed the correct "Ready" state. It did not persist under a repeat load, so it is recorded as observed test-server timing, not an application fix.
- Photographer submitted a two-hour overtime request (**OT-20261025195502-WUN8O**, March 10, 2027, 6–8 PM). HR opened the detail and approved it after the confirmation; the queue changed to Approved.

### Messaging

- Client Beatriz opened the Yolanda conversation for booking **BK-6ADC6C55376A8**. A message of spaces showed "Type a message first." and was not added. Sent "Browser client test: please confirm the final schedule." and saw "Message sent."
- Logged in as Yolanda and opened the same conversation. All three messages were present, including the client's message. Existing `<script>` and `<b>` test text appeared as plain text, not executed or rendered as markup.
- The send button briefly remained in "Sending..." state before completing; it then updated normally. No duplicate message appeared.

### Admin moderation and category CRUD

- Admin flagged Jasmine Zabala's Quarry Road review; its badge changed to FLAGGED. Clicked Publish and the same row returned to PUBLISHED. This restored the original state.
- Opened Create Category, submitted empty and saw "Category name is required." Created **Browser Test Category 20261025**, saw the success dialog, found it on page 2 of View Categories, clicked Delete, confirmed the irreversible-action dialog, and saw it removed. No seeded category was changed.
- Admin users, studios, freelancers, categories, locations, subscriptions, reports and booking-refund queue pages were covered by the page crawl earlier; deeper CRUD for each is still on the remaining checklist.

### Galleries

- Owner Online Gallery showed published, pending-approval and no-gallery rows. Approved pending gallery **FS-BKS-00021** and saw the row change to Approved. This changed local seeded state; no rejection or deletion was performed.
- Client Beatriz saw three published galleries, including both studio and freelancer delivery. Opened the Quarry Road gallery, confirmed both photos, individual download links and "Download all photos (ZIP)."
- Individual photo download produced a file. The ZIP endpoint was checked with Playwright's request client: 200, `application/zip`, attachment filename `GAL-6ADCA65FEDF96.zip`, 231,930 bytes. An in-page `fetch()` reported 204 because Chrome handles an attachment response differently; this was a test-method artifact, not an application defect. Regression check: `tests/Browser/gallery-download.mjs` passes.
- Photographer tried adding a third photo to an already published gallery and received the intended message: "This gallery is already published to the client. Ask the client to request a revision before uploading new photos." No photo was added.
- Existing published gallery **BK-6ADCA27549033** has no Revision Requested control on the client gallery or its booking-history row because its older record has no active revision deadline. The route and form exist in source; this was not changed, because changing revision eligibility would alter the business rule.

### Freelancer

- Lorena's service list showed two categories and three services in each. Search with real keystrokes narrowed to Wedding; category filter narrowed to one. Opened Wedding service details; C-07 remains fixed (no error), with all three services listed.
- Package list showed four packages with category, price, gallery, time customization, duration, photo count and status. Opened a package edit form, cleared its name and pressed Save Changes: the browser blocked the empty required field and stayed on the same page. No seeded package was saved or changed.

### Admin users

- Searched Registered Users by typing "Quality Tester" and opened the eye action. The User Profile modal showed full name, UUID, email, mobile, location, status and verification date. The name link itself does not open this modal; only the Actions eye does. This is acceptable, but the name is styled as a link while doing nothing, and remains a usability issue to decide later rather than a broken workflow.
- The **Quality Tester** account and the older `probe.deploy.*` account are test-data cleanup items already recorded for the owner; they were not deleted.
- Clicking a user's name exposed C-37. The name link opened no profile. The missing Bootstrap modal attributes are fixed; the Playwright regression test now opens by name and by eye, closes with Escape, and verifies the right person's name in the modal.

### Booking preparation, equipment and hardcopy

- Opened confirmed booking **BK-6ADC6C55376A8**, which already had one photographer and two paid editing add-ons. Assigned the available **Camera MARK VI / NIKON** to the booking. The section changed to "Equipment assigned... Waiting for the photographer to confirm." The photographer and equipment catalog rendered correctly.
- Created a 500-peso, 8x10 hardcopy quote from Booking Details. The quote showed as Order #4, Pending, with the studio pickup suggestion.
- Client opened the same booking, saw the quote and total, chose Studio pickup and accepted. Reopened: total became ₱10,100.00, the client choice is shown as final, and the remaining balance updated to ₱10,100.00. This is deliberate local test data.
- While testing this, C-38 was found and fixed: saving a quote left keyboard focus outside the modal and Escape failed.
- After the fix, a 300-peso quote on **BK-6ADD9A0FD3B8D** was created, focus stayed on the saved-status message, Escape closed the dialog, and the quote was reopened and cancelled as Order #5.
- The older accepted quote remains local test data; no payment was made for the added balance.
