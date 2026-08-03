# Developer Context

## Project

Platinum is a Laravel 12 photography studio platform. It is a server-rendered Blade application with Tailwind CSS and Vite; it is not an SPA. The durable project record is [docs/README.md](docs/README.md). Technical documents in [ANALYSIS - TECHNICAL](docs/ANALYSIS%20-%20TECHNICAL/00-overview/START%20HERE.md) are authoritative; [ANALYSIS - PLAIN](docs/ANALYSIS%20-%20PLAIN/00-overview/START%20HERE.md) is the matching non-technical view.

## Commands

```powershell
composer dev
composer test
php artisan test --compact
php artisan route:list
npm run build
```

## Architecture rules

- `tbl_` is the application-table naming convention. `BookingModel` is the cross-portal booking aggregate.
- Portals are selected by `UserModel.role`: admin, owner, client, freelancer, studio HR, studio finance, and studio photographer.
- Use existing portal controllers, middleware, Blade layouts, models, and services. Do not introduce an SPA or duplicate a workflow.
- Store public uploads on the explicit `public` disk and persist relative paths only. Do not add a storage symlink.
- Payment gateway configuration and the Groq API key remain server-side environment configuration.

## Documentation rules

- Begin with [docs/README.md](docs/README.md); the supplied [technical](prompt/engineering/SYSTEM%20ANALYSIS/WORKFLOW%20-%20TECHNICAL%20V2/00%20-%20START%20HERE.md) and [plain-language](prompt/engineering/SYSTEM%20ANALYSIS/WORKFLOW%20-%20PLAIN%20V2/00%20-%20START%20HERE.md) System Analysis Workflow v2 files are the documentation methodology and remain synchronized.
- Record only evidence-backed current behavior. Put unapproved work in linked open items, risks, issues, or decisions—not as shipped functionality.
- `prompt/tasks/` contains user-authored task prompts. Never rename, move, split, or invent task prompts; see [task index](docs/ANALYSIS%20-%20TECHNICAL/04-tasks/index.md).
