# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Full dev environment (server + queue + logs + vite hot reload)
composer dev

# Run all tests
composer test

# Run a single test file
php artisan test tests/Feature/ChatbotFeatureTest.php

# Run a specific test method
php artisan test --filter=test_method_name

# Fresh seed (wipes DB)
php artisan migrate:fresh --seed

# Run specific seeder
php artisan db:seed --class=MultiStudioBundleSeeder

# Lint/format PHP
./vendor/bin/pint

# Build frontend assets
npm run build
```

Tests use SQLite in-memory (`DB_DATABASE=:memory:`), so they run without MySQL.

## Architecture

This is a **multi-role photography studio platform** (Laravel 12 + Blade + Tailwind via Vite). The DB is `platinum` (MySQL), with the `tbl_` prefix convention on all tables.

### Roles & Portals

`UserModel.role` is the portal identifier. Each role maps to a middleware + route prefix + controller namespace + view directory:

| Role | Middleware | Route prefix | Controller ns | View dir |
|---|---|---|---|---|
| `admin` | `AdminMiddleware` | `/admin` | `Admin\` | `admin/` |
| `owner` / `owner-super-admin` | `OwnerMiddleware` | `/owner` | `StudioOwner\` | `owner/` |
| `client` | `ClientMiddleware` | `/client` | `Client\` | `client/` |
| `freelancer` | `FreelancerMiddleware` | `/freelancer` | `Freelancer\` | `freelancer/` |
| `studio-hr` / `studio-hr-*` | `StudioHRMiddleware` | `/hr` | `StudioHR\` | `studio-hr/` |
| `studio-finance` / `studio-finance-*` | `StudioFinanceMiddleware` | `/finance` | `Finance\` | `studio-finance/` |
| `studio-photographer` | `StudioPhotographerMiddleware` | `/photographer` | `StudioPhotographer\` | `studio-photographer/` |

### RBAC (Studio Owner context)

Studio staff permissions are managed via `tbl_roles` / `tbl_permissions` / `tbl_user_roles` (pivot includes `studio_id`). Methods live on `UserModel`: `hasPermission()`, `getAllPermissions()`, `syncRoles()`. The `CheckPermissionMiddleware` gates controller actions. Roles and permissions are seeded via `RbacSeeder` + `StudioOwnerRolePermissionSeeder`.

### Booking flow

`BookingModel` (`tbl_bookings`) is the central entity. `booking_type` is either `studio` or `freelancer`. Status constants: `pending → confirmed → in_progress → completed | cancelled`. Payment: `PaymongoService` (GCash/card via PayMongo API) and `StripeService`. `BookingPackageModel` holds the selected package snapshot. `BookingAssignedPhotographerModel` links photographers assigned by the studio owner.

### Services layer

`app/Services/` contains non-trivial business logic extracted from controllers:
- `Dashboard/` — one service class per portal (`AdminDashboardService`, `OwnerDashboardService`, etc.), all extending `BaseDashboardService`
- `PaymongoService` / `StripeService` — payment gateway wrappers
- `PhotographerAvailabilityService` — checks photographer schedule conflicts
- `ProcurementWorkflowService` — multi-step procurement state machine
- `AttendanceGeolocationService` — validates employee check-in location
- `ChatbotService` — BotMan-powered FAQ chatbot

### Models namespace convention

Models are namespaced by their owner portal:
- `App\Models\UserModel` — single users table, all roles
- `App\Models\StudioOwner\*` — studios, packages, services, roles, photographers, schedules, payroll, procurement
- `App\Models\Client\*` — client profile, budgets
- `App\Models\Freelancer\*` — freelancer profile, services, packages
- `App\Models\StudioHR\*` — attendance, payroll generation, procurement requests
- `App\Models\BookingModel`, `PaymentModel`, `BookingPackageModel` — root namespace (cross-portal)

### Frontend

Blade templates only (no SPA). Layouts in `resources/views/layouts/`. Partials in `resources/views/partials/`. Tailwind CSS via `@tailwindcss/vite`. No component framework — plain JS in `resources/js/`.

### Payment integrations

- **PayMongo** (`paymongo/paymongo-php`) — primary PH gateway; config in `config/services.php` under `paymongo`
- **Stripe** (`stripe/stripe-php`) — secondary; same config block

### Chatbot

BotMan (`botman/botman` + `botman/driver-web`) powers an FAQ chatbot configurable per studio via `ChatbotConfigController`. Default config seeded by `ChatbotDefaultConfigSeeder`.
