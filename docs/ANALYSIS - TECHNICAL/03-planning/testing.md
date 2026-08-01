# Planning Test Strategy

Use the smallest existing relevant test group first, then run `php artisan test --compact` before reporting completion. Add tests only when a code change introduces logic that existing coverage does not exercise. Documentation-only work requires link, identifier, and terminology checks rather than application-code tests.
