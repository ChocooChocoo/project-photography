# Database

> **In plain terms:** The system stores people, studios, bookings, payments, work assignments, and photos in related tables. Its sample-data rules preserve the protected records named by the user.

### ANL-004 — Seed-data contracts

**Area:** Database and test coverage.  
**Observation:** The fresh-seed chain preserves `tbl_users` and `tbl_locations`, rebuilds supported operational data, and forbids media references in the fresh seed source. Tests also check Cavite location integrity and seed identity rules.  
**Evidence:** `database/seeders/Fresh/`, `tests/Feature/FreshSeedContractTest.php`, `tests/Feature/SeedIntegrityTest.php`.

### ANL-010 — Application table convention

**Area:** Schema.  
**Observation:** Application tables use the `tbl_` prefix. Migrations define relational records for users, studios, service/package data, bookings, payments, galleries, reviews, subscription plans, payroll, attendance, procurement, and chatbot data.  
**Evidence:** `database/migrations/`.

The diagram in [ERD](../07-diagrams/erd.md) shows the principal relationships rather than every column.
