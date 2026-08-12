# Developer Context

## Project

Platinum is a Laravel 12 photography studio platform. It is a server-rendered Blade application with Tailwind CSS and Vite; it is not an SPA. The durable project record starts at [docs/00 - START HERE.md](docs/00%20-%20START%20HERE.md) and continues in [ANALYSIS - PLATINUM STUDIO PLATFORM](docs/ANALYSIS%20-%20PLATINUM%20STUDIO%20PLATFORM/00%20-%20START%20HERE.md).

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

- Begin with [docs/00 - START HERE.md](docs/00%20-%20START%20HERE.md). Workflow Version 3 in [prompt/WORKFLOW v3](prompt/WORKFLOW%20v3/00%20-%20START%20HERE.md) governs the documentation structure, traceability, plain-language rules, diagrams, roadmap, task tracker, parts, and role screens.
- Record only evidence-backed current behavior. Put unapproved work in linked open items, risks, issues, or decisions—not as shipped functionality.
- `docs/ANALYSIS - PLATINUM STUDIO PLATFORM/MATERIAL/LEGACY DOCUMENTATION/tasks/` freezes the current user-authored task prompts for provenance; `09 - TASK TRACKER.md` is the canonical task index.
