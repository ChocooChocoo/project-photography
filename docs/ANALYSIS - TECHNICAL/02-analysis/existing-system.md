# Existing System

> **In plain terms:** Platinum is a photography booking and studio-management website with separate work areas for each type of user.

### ANL-001 — Laravel server-rendered application

**Area:** Stack and structure.  
**Observation:** The application uses PHP 8.2+, Laravel 12, Blade views, Tailwind CSS, and Vite. `composer.json` and `package.json` are the authoritative manifests.  
**Evidence:** `composer.json`, `package.json`, `resources/views/`, and `resources/js/`.

### ANL-002 — Multi-role portal system

**Area:** Features and authorization.  
**Observation:** The route layer separates administrator, owner, client, freelancer, studio HR, studio finance, and studio photographer portals.  
**Evidence:** `routes/web.php`; portal middleware and controllers under `app/Http/Controllers/`.

### ANL-007 — Public media storage

**Area:** Configuration.  
**Observation:** Public uploads use the explicit `public` disk and are served from `public/storage`; no storage symlink is declared. Database values are relative media paths.  
**Evidence:** `config/filesystems.php`, `tests/Feature/MediaStorageTest.php`.

See the full [detailed technical analysis](technical-analysis.md), [revision checklist](revision-checklist.md), [architecture](architecture.md), [database](database.md), [security](security.md), and [process flows](process-flows.md).
