# Issues

> **In plain terms:** This is the current problem that blocks safe follow-on work. It stays open until the named business decision is supplied.

### ISS-001 — Photographer cancellation has no approved remedy workflow

**Status:** Blocked.  
**Impact:** A cancelled assignment has no documented, approved end-to-end response for the studio and client.  
**Blocks:** Any cancellation-remedy implementation.  
**Next action:** Answer [QST-001](../00-overview/open-items.md#qst-001--photographer-cancellation-policy).

### ISS-002 — Owner Services page decodes an already-cast array

**Status:** Open.
**Impact:** `/owner/view/services` returns HTTP 500 for the browser-validated seeded owner instead of rendering the service list.
**Evidence:** `resources/views/owner/view-services.blade.php` calls `json_decode()` on a value already cast to an array.
**Next action:** Handle the cast value directly in a separately approved fix; this defect is unrelated to subscription expiry.
