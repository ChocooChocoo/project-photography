# Test Results

> **In plain terms:** This is the recorded result of the latest full automated check. It proves only the behavior covered by that test run at the recorded time.

## 2026-08-01

**Command:** `php artisan test --compact`  
**Result:** 70 passed, 383 assertions, exit code 0.  
**Scope:** Existing automated PHPUnit suite.

The documentation reset also requires Markdown-link, identifier, terminology, and source-only-diff validation before it is marked complete.

## 2026-08-03

**Command:** `php artisan test --compact`
**Result:** 74 passed, 399 assertions, exit code 0.
**Focused result:** `tests/Feature/SubscriptionLifecycleTest.php` — 4 passed, 16 assertions.
**Browser scope:** Owner, client, studio-photographer, and administrator pages; live trial creation; forced expiry; expired status display; and re-subscription availability.

The browser pass also confirmed an unrelated existing defect: the owner Services page returns HTTP 500 when its Blade view passes an already-cast array to `json_decode()`. It is recorded as ISS-002 and was not changed in the subscription task.
