# Platinum Studio Platform

Platinum is a web platform for photography studios, freelancers, clients, and studio staff. It supports service discovery, bookings, payments, assigned photographers, galleries, reviews, studio operations, and a photography-focused help assistant.

## Start here

- [Workflow v3 documentation front door](docs/00%20-%20START%20HERE.md)
- [Platinum Studio Platform analysis](docs/ANALYSIS%20-%20PLATINUM%20STUDIO%20PLATFORM/00%20-%20START%20HERE.md)
- [Migration coverage ledger](docs/ANALYSIS%20-%20PLATINUM%20STUDIO%20PLATFORM/MATERIAL/LEGACY%20COVERAGE.md)
- [Developer and agent context](CLAUDE.md)

## Running locally

```powershell
composer dev
```

Run the automated checks with:

```powershell
composer test
```

The project uses Laravel 12, PHP 8.2 or later, Blade templates, Tailwind CSS, and Vite. Configuration belongs in `.env`; never commit credentials.
