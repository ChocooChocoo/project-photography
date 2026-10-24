← [Previous: Phase 4](./phase-4.md) | [Next: Phase 6](./phase-6.md) →

# Phase 5: Remove Min/Max Budget and Update the DSS

**Status:** ✅ Done

**Goal:** Remove the minimum and maximum budget fields (as Sir Austin suggested). The preferred budget alone drives package recommendations for the client's event.

## Dependencies
Phase 1.

## Decision
Matching uses `preferred_budget` with a 10% tolerance: perfect match within 10% of preferred, within budget when at or below preferred plus 10%, above budget otherwise. The overspend alert uses `preferred_budget`. The Decision Support System stays rule-based (no Groq).

## Tasks
- [x] DSS logic exists in `app/Http/Controllers/Client/BookingController.php:2411-2469` (`calculateBudgetMatch`) and is shown in `resources/views/client/booking-forms.blade.php:666-1095`.
- [x] Migration: drop `minimum_budget`, `maximum_budget` and their index from `tbl_client_budget` (created in `2026_03_11_051427_create_tbl_client_budget_table.php:29-30, 52`). Guard with `Schema::hasColumn`. Make `preferred_budget` required.
- [x] `app/Models/ClientBudgetModel.php`: remove fillable and casts (`:38-39, 53-54`), `scopeInBudgetRange` (`:105-110`), the min/max accessors (`:117-127`), and `getBudgetRangeAttribute` (`:149-157`).
- [x] `StoreBudgetRequest` and `UpdateBudgetRequest`: remove min/max rules and messages.
- [x] `Client/BudgetController`: remove min/max at store (`:85-86`), update (`:147-148`) and stats (`:257-258`).
- [x] `Client/BookingController`: rewrite `calculateBudgetMatch` for preferred ± 10% (this also fixes the bug where prices below the minimum were labelled `above_budget`); change the overspend check at `:2346`; replace `formatBudgetRange` (`:2477-2495`) and the payload at `:2677-2680`.
- [x] Views: `resources/views/client/set-budget.blade.php` (inputs, modals, JS validation and payloads) and `booking-forms.blade.php:723-752, 896, 1099`.
- [x] Seeders: `database/seeders/CoverageGapsAndAdminSeeder.php:290-291`, `Fresh/FreshMarketplaceSeeder.php:118-119`. Update `database/schema/DB-SCHEMA*.SQL`.
- [x] Update tests that build these columns by hand: `tests/Feature/Client/BudgetStatusControlTest.php:115-116`, `tests/Feature/StudioManagement/DiscountRuleTest.php:405`.
- [x] Add tests for the new matching tiers and the overspend alert.
- [x] Add a production SQL script under `docs/sql/` for the column drop.

## Done when
- [x] No min/max budget input or text remains in the client UI.
- [x] Creating a budget needs only a preferred amount.
- [x] Packages show perfect match, within budget, or above budget from preferred ± 10%.
- [x] Tests pass.

← [Previous: Phase 4](./phase-4.md) | [Next: Phase 6](./phase-6.md) →
