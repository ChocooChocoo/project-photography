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
