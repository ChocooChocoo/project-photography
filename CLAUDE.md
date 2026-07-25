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
| `studio-hr` / `studio-hr-*` | `StudioHRMiddleware` | `/studio-hr` | `StudioHR\` | `studio-hr/` |
| `studio-finance` / `studio-finance-*` | `StudioFinanceMiddleware` | `/studio-finance` | `Finance\` | `studio-finance/` |
| `studio-photographer` | `StudioPhotographerMiddleware` | `/studio-photographer` | `StudioPhotographer\` | `studio-photographer/` |

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
- `ChatbotService` — photography AI assistant (Groq); orchestrates guard → model → guard
- `Ai/GroqClient` — Groq chat-completions transport; the only class that reads the API key
- `Ai/ChatbotGuard` — input/output security guardrails and fallback copy
- `Ai/GroqRateLimiter` — request/token budget windows over the cache

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

### Media storage

**There is no storage symlink, and `php artisan storage:link` is not part of deployment.** The `public` disk's root is `public_path('storage')` — uploads land directly in the directory the web server serves, so the write path and the read path are the same on Windows, Linux, and shared hosting alike. `config/filesystems.php` keeps `links` empty and `serve => false` on the private `local` disk (that flag registers a `GET /storage/{path}` route which would shadow the media namespace).

Conventions: write with `Storage::disk('public')` or `->store($dir, 'public')` — always name the disk, never rely on the default (`FILESYSTEM_DISK=local` points at the private disk). Read with `asset('storage/'.$path)`; DB columns store the relative path only (`brand-logos/xxx.png`). `tests/Feature/MediaStorageTest.php` guards these invariants.

Two caveats: `public/storage` is both git-tracked (it ships seed media) and the live upload target, so a deploy that recreates the tree (`git clean -fd`, fresh-clone-and-swap) would delete production uploads — deploy by `git pull` into a persistent directory. And the `public` disk has `throw => false`, so a permission-denied write returns `false` silently; verify `public/storage` is writable by the PHP user after any deploy.

Background: [prompt/output/05.md](prompt/output/05.md).

### Payment integrations

- **PayMongo** (`paymongo/paymongo-php`) — primary PH gateway; config in `config/services.php` under `paymongo`
- **Stripe** (`stripe/stripe-php`) — secondary; same config block

### AI assistant

A Groq-powered assistant restricted to photography-service conversations replaces the old fixed-response chatbot. Model id comes from `config('services.groq.model')` (default `qwen/qwen3.6-27b`, override with `GROQ_MODEL`); the key is `GROQ_API_KEY` and is server-side only.

Pipeline in `ChatbotService::processMessage()`: sanitize → owner moderation (`evaluateMessage`) → `ChatbotGuard::inspectInput()` (prompt injection, credential probes) → `GroqRateLimiter::attempt()` → `GroqClient::chat()` → `ChatbotGuard::inspectOutput()` (off-topic marker, secret/instruction leaks). Any layer that trips returns fixed fallback copy and never calls the provider needlessly.

Security rules live in the `ChatbotService::SECURITY_RULES` constant — not the DB — so owners cannot edit them and history cannot override them. Owner-editable rows in `tbl_chatbot_intents` are studio knowledge facts injected as `<untrusted_data>`, not literal replies.

Endpoints are cross-portal: `/chatbot/*` (`chatbot.*` route names, `App\Http\Controllers\ChatbotController`), mounted in the client booking-details page and the owner / studio-photographer layouts via `resources/views/partials/chatbot-widget.blade.php`. Owner-facing config UI is `ChatbotConfigController`; defaults seeded by `ChatbotDefaultConfigSeeder`.

Full reference: [docs/AI ASSISTANT INTEGRATION.md](docs/AI%20ASSISTANT%20INTEGRATION.md).
