# Planning Test Strategy

> **In plain terms:** Use the smallest relevant checks while work is in progress, then run the full existing suite before reporting completion. Documentation-only work is checked for links, labels, and consistency instead of application behavior.

Use the smallest existing relevant test group first, then run `php artisan test --compact` before reporting completion. Add tests only when a code change introduces logic that existing coverage does not exercise. Documentation-only work requires link, identifier, and terminology checks rather than application-code tests.
