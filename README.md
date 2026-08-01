# Platinum Studio Platform

Platinum is a web platform for photography studios, freelancers, clients, and studio staff. It supports service discovery, bookings, payments, assigned photographers, galleries, reviews, studio operations, and a photography-focused help assistant.

## Start here

- [Plain-language system analysis](docs/ANALYSIS%20-%20PLAIN/00-overview/START%20HERE.md)
- [Technical system analysis](docs/ANALYSIS%20-%20TECHNICAL/00-overview/START%20HERE.md)
- [Documentation index](docs/README.md)
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
