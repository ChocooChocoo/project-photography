---
# Visual plan: open https://plan.agent-native.com/plans/plan-32b8cbf281364f7a in a browser for the canvas and review UI.
visualUrl: "https://plan.agent-native.com/plans/plan-32b8cbf281364f7a"
title: "T-02: Resolve the post-T-01 defects"
brief: "Twelve reported defects, four shared causes, and eight parallel workstreams with disjoint file ownership."
version: 1
---

## Objective

Resolve the twelve defects reported after task T-01, and answer two questions about product scope. Eight workstreams run at the same time on one build. Each workstream owns its own files, so two editors never touch the same path.

### Success criteria

1. Granting or editing employee permissions never returns the name-field error.
2. A valid email address never shows the valid-email message on the login screen.
3. Neither the owner nor a photographer can mark a booking completed before the online gallery is published.
4. Cancelling a booking shows neither the CSRF mismatch message nor the booking-status prompt.
5. A booking more than 24 hours before its event can be cancelled while a photographer is assigned to it.
6. The recovery banner and the refund queue render while a recovery has no deadline.
7. A photographer can be assigned again after an accepted assignment is cancelled.
8. The bookings table renders five rows, and the footer matches the data set, after a live refresh.
9. The gallery approval columns exist in the deployed database, and submitting a gallery for approval succeeds.
10. Clicking a notification opens the record it is about.
11. Update Studio saves the form and reports the result.
12. The budget Status control is proven present by a test, or the missing control is restored.
13. `php artisan test` passes.
14. A written answer states whether a new portal and access area can be added, and lists the steps.

### Scope and non-goals

In scope: the defects above, their regression tests, the deployed-schema step, and the two written answers.

Out of scope: new product features, portal redesigns, payment gateway work, the AI assistant, refund percentage rules, and building a new portal as code.

### Execution model

The workstreams run concurrently rather than in sequence. Two or three sub-agents work at once on disjoint files: one edits, one explores the next defect, one runs tests. A file never appears in two workstreams, so no merge step is needed inside a workstream.

## The audit

Every cause below was verified in the current tree by reading the code path. Each entry states the cause and the change this plan commits to.

### Booking and staffing

- **A photographer cannot be replaced after declining.** The Assign action and both endpoints refuse **in_progress** (BookingController:353, :509, :885), and the duplicate guard counts cancelled rows. Fix: allow assignment while a slot is free and the booking is not completed or cancelled, and exclude cancelled rows from the duplicate check and the slot count.
- **A booking more than 24 hours out cannot be cancelled.** The status gate allows only pending and confirmed (MyBookingsController:297). Accepting an assignment moves the booking to in_progress. Fix: add in_progress to the cancellable statuses and keep the 24-hour gate.
- **Completion is blocked for a photographer but allowed for the owner.** The owner keeps a private duplicate that disagrees with the model (BookingController:254-276 against BookingModel:444). Fix: delete the duplicate and call the model method from both portals.
- **The refund queue crashes on a null deadline.** Client cancellation writes no deadline, and both recovery views call format on it (owner/cancellation-recovery.blade.php:6, client/cancellation-recovery.blade.php:7). Fix: render a not-set state and write the deadline where it is known.

### Booking screens

- **CSRF mismatch when cancelling.** A render-time \_token in the request body is read before the live header token (VerifyCsrfToken:151), so the token that session-token.js keeps fresh never wins. Fix: read the meta token at request time in the owner and client cancel paths.
- **Please select booking status, after a failed save.** The available_statuses list is empty for a terminal booking, so the select holds only its placeholder and the confirm action can never succeed. Fix: disable the confirm action and state the real reason, and refresh the booking after a failed save.
- **The table shows more than five rows.** The 25-second live refresh appends rows and the table instance never re-paginates (custom-table.js initialises anonymously). Fix: expose the table instance and re-render the current page after every merge.

### Access, schema, and settings

- **The name field is required (and 1 more error).** RoleController::update:264 requires name and status, and a permissions-only PUT cannot satisfy them. Fix: accept a partial payload and fill the status select from the current role.
- **Unknown column approval_status.** The migration exists and ran on the local database; the deployed database never ran it. Fix: apply the pending migrations on the deployed database and keep the idempotent SQL fallback.
- **Update Studio does nothing.** owner_profile_photo stays required on update (StudiosModel:197, StudioController:526-534), so validation fails and the failure is silent. Fix: relax the rule for update and report the result.
- **A valid email address still shows an error.** The login input handler calls show() on the static invalid-feedback sibling on every keystroke. Fix: hide the feedback, remove is-invalid, and route the client check through window.PlatinumEmail.
- **The budget Status control is reported missing.** It renders in the create form (set-budget.blade.php:196) and the edit template (:712), and nothing hides it. Fix: pin both with a render test; change code only if the test fails.

## Four shared causes

**C1 - A render-time CSRF token in the request body.** `session-token.js` attaches the live token as a header, and `VerifyCsrfToken` reads a body `_token` before the header. Any AJAX body that still contains `{{ csrf_token() }}` therefore overrides the live token and fails after a login or session refresh.

**C2 - `in_progress` is set by assignment acceptance and never restored.** Two reported defects are the same status value: cancellation refuses `in_progress`, and staffing refuses `in_progress`, so one accepted-then-cancelled photographer blocks the whole booking.

**C3 - A duplicated business rule.** The owner booking controller keeps a private copy of the gallery completion rule that disagrees with the model method, so the same booking is completable in one portal and blocked in the other.

**C4 - Client-side pagination against a server-side live refresh.** The bookings table paginates in the browser, and the 25-second refresh appends rows underneath it without re-paginating.

<Diagram id="b-diagram" caption="One booking status value drives three reported symptoms." frame="show">

```html
<div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr) minmax(0,1fr);gap:14px">
<div class="diagram-panel"><div class="diagram-pill">Trigger</div>
<div class="diagram-box">Owner assigns a photographer</div>
<div class="diagram-box">Photographer accepts</div>
<div class="diagram-box">Booking status becomes in progress</div>
<div class="diagram-box">Photographer cancels</div>
<div class="diagram-box">Assignment becomes cancelled, booking status stays in progress</div></div>
<div class="diagram-panel"><div class="diagram-pill">Today</div>
<div class="diagram-box">Cancellation refused: only pending and confirmed are cancellable</div>
<div class="diagram-box">Assign action hidden and both endpoints refuse in progress</div>
<div class="diagram-box">Duplicate guard counts the cancelled row</div>
<div class="diagram-muted">Three reported symptoms, one status value.</div></div>
<div class="diagram-panel"><div class="diagram-pill">After the fix</div>
<div class="diagram-box">Cancel allowed while the event is more than 24 hours away</div>
<div class="diagram-box">Assign allowed while a slot is free and the booking is not completed or cancelled</div>
<div class="diagram-box">Cancelled rows no longer block a new assignment</div>
<div class="diagram-muted">Booking status keeps its meaning; staffing is governed by the slot count.</div></div></div>
```

</Diagram>

## Workstreams

Each workstream owns the files it edits and writes its own regression tests. No file appears in two workstreams.

| Workstream                          | Owned files                                                                                                                                                                                                                                                                           | Defects                                                                                          |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| W1 Owner booking UI                 | `resources/views/owner/view-bookings.blade.php`, `public/assets/js/pages/custom-table.js`                                                                                                                                                                                             | CSRF token on the status save, status modal empty state, Assign action gate, table re-pagination |
| W2 Booking domain                   | `app/Models/BookingModel.php`, `app/Http/Controllers/StudioOwner/BookingController.php`                                                                                                                                                                                               | Gallery rule consolidation, assignment gates, duplicate guard, recovery deadline write           |
| W3 Client cancellation and recovery | `app/Http/Controllers/Client/MyBookingsController.php`, `app/Services/BookingCancellationRecoveryService.php`, `resources/views/client/view-my-bookings.blade.php`, `resources/views/client/cancellation-recovery.blade.php`, `resources/views/owner/cancellation-recovery.blade.php` | Cancellable statuses, CSRF token on the client cancel call, null deadline render                 |
| W4 Auth email                       | `resources/views/auth/login.blade.php`                                                                                                                                                                                                                                                | Email false positive                                                                             |
| W5 Notifications                    | `app/Http/Controllers/NotificationController.php`, `app/Traits/Notifiable.php`, `public/assets/js/pages/notifications.js`                                                                                                                                                             | Notification click-through                                                                       |
| W6 Roles and studio settings        | `app/Http/Controllers/StudioOwner/RoleController.php`, `resources/views/owner/view-roles.blade.php`, `app/Http/Controllers/StudioOwner/StudioController.php`, `app/Models/StudioOwner/StudiosModel.php`                                                                               | Permission save, Update Studio                                                                   |
| W7 Deployed schema                  | `database/migrations/`, `docs/sql/`                                                                                                                                                                                                                                                   | Missing gallery approval columns                                                                 |
| W8 Verification and answers         | `tests/Feature/Client/`, `docs/`                                                                                                                                                                                                                                                      | Budget Status control, portal feasibility answer                                                 |

### Order of operations

W1 and W3 share the CSRF cause but not a file, so they run together. W2 owns the model method that W1 and W3 call, so W2 publishes the method signature first and the other two call it without editing the model. W7 runs first because it unblocks any real gallery test. W8 runs last and owns the whole-suite run.

<TabsBlock
  id="b-code"
  tabs={[
    {
      id: "t1",
      label: "Owner booking UI",
      blocks: [
        {
          id: "c1",
          type: "annotated-code",
          data: {
            filename: "resources/views/owner/view-bookings.blade.php",
            language: "blade",
            code: "@if(!['completed', 'cancelled'].includes(booking.status) && data.current_assigned_count < data.max_photographers)\n    <button class=\"btn btn-primary btn-sm\" id=\"assignPhotographerBtn\">Assign Photographer</button>\n@endif\n\n$.ajax({\n    url: statusUrl,\n    type: 'PUT',\n    data: { status: status, cancellation_reason: reason, _token: $('meta[name=\"csrf-token\"]').attr('content') },\n});",
            annotations: [
              {
                lines: "1",
                label: "Slot gate, not status gate",
                note: "Booking status must not hide the action while a photographer slot is free. Exclude only completed and cancelled.",
              },
              {
                lines: "8",
                label: "Stale token",
                note: "The body token is read before the header token. Read the meta value at request time instead of at render time.",
              },
            ],
          },
        },
        {
          id: "c2",
          type: "code",
          data: {
            code: "document.addEventListener('DOMContentLoaded', () => {\n    window.PlatinumTable = new CustomTable();\n});\n\n// After a live-refresh merge:\nfunction refreshTableRows() {\n    window.PlatinumTable.tables.forEach(function (t) {\n        t.rows = Array.from(t.tbody.querySelectorAll('tr'));\n        t.filteredRows = t.rows.slice();\n        t.update();\n    });\n}",
            language: "javascript",
            filename: "public/assets/js/pages/custom-table.js",
          },
        },
      ],
    },
    {
      id: "t2",
      label: "Booking domain",
      blocks: [
        {
          id: "c3",
          type: "annotated-code",
          data: {
            filename: "app/Http/Controllers/StudioOwner/BookingController.php",
            language: "php",
            code: "if (in_array($booking->status, ['completed', 'cancelled'])) {\n    return response()->json(['success' => false, 'message' => 'Photographers cannot be assigned to a completed or cancelled booking.'], 422);\n}\n\n$active = $booking->assignedPhotographers()\n    ->whereIn('status', ['assigned', 'confirmed', 'on_site', 'in_progress'])\n    ->count();",
            annotations: [
              {
                lines: "1",
                label: "Blocks the replacement",
                note: "Drop in_progress from this guard in all three places: lines 353, 509, and 885.",
              },
              {
                lines: "5-7",
                label: "Cancelled rows excluded",
                note: "Use the same list for the duplicate check and the slot count so a cancelled row never consumes a slot.",
              },
            ],
          },
        },
        {
          id: "c4",
          type: "code",
          data: {
            code: "public function isGalleryReadyForCompletion(): bool\n{\n    if (! $this->requiresOnlineGalleryUpload()) {\n        return true;\n    }\n\n    return $this->hasUploadedGalleryContent()\n        && $this->onlineGalleryIsPublished();\n}",
            language: "php",
            filename: "app/Models/BookingModel.php",
          },
        },
      ],
    },
    {
      id: "t3",
      label: "Roles and settings",
      blocks: [
        {
          id: "c5",
          type: "code",
          data: {
            code: "$validated = $request->validate([\n    'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('tbl_roles', 'name')->whereNull('deleted_at')->ignore($id)],\n    'description' => 'nullable|string',\n    'status' => ['sometimes', 'required', 'in:active,inactive'],\n    'is_system' => 'nullable|boolean',\n]);\n\n$role->update($validated);",
            language: "php",
            filename: "app/Http/Controllers/StudioOwner/RoleController.php",
          },
        },
        {
          id: "c6",
          type: "code",
          data: {
            code: "$rules['studio_logo'] = 'nullable|image|mimes:jpg,jpeg,png|max:3072';\n$rules['business_permit'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:3072';\n$rules['owner_id_document'] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:3072';\n$rules['owner_profile_photo'] = 'nullable|image|mimes:jpg,jpeg,png|max:3072';\n$rules['permit_expiry_date'] = 'nullable|date';",
            language: "php",
            filename: "app/Http/Controllers/StudioOwner/StudioController.php",
          },
        },
      ],
    },
    {
      id: "t4",
      label: "Deployed schema",
      blocks: [
        {
          id: "c7",
          type: "annotated-code",
          data: {
            filename:
              "database/migrations/2026_09_22_090100_add_gallery_approval_columns.php",
            language: "php",
            code: "Schema::table('tbl_studio_online_gallery', function (Blueprint $table) {\n    $table->string('approval_status')->default('pending');\n    $table->unsignedBigInteger('submitted_by')->nullable();\n    $table->timestamp('submitted_at')->nullable();\n});",
            annotations: [
              {
                lines: "1-5",
                label: "Already written",
                note: "The file exists and ran on the local database. The deployed database is the gap; confirm with migrate:status before adding anything.",
              },
            ],
          },
        },
        {
          id: "c8",
          type: "code",
          data: {
            code: "-- Idempotent fallback for a database that cannot run artisan migrate.\nALTER TABLE tbl_studio_online_gallery\n    ADD COLUMN IF NOT EXISTS approval_status VARCHAR(255) NOT NULL DEFAULT 'pending';",
            language: "sql",
            filename: "docs/sql/2026-09-22-t01-schema-additions.sql",
          },
        },
      ],
    },
  ]}
/>

<Callout id="b-decision" tone="decision">

**Settled: staffing is governed by the slot count, not by booking status.** A booking that lost its only photographer keeps the `in_progress` status it earned from the acceptance, and the owner may assign a replacement while a slot is free and the booking is not completed or cancelled. Reverting the booking status was rejected: it rewinds a state other features read, and it does not cover a booking with two photographers where only one cancels.

**Settled: the budget Status control is verified, not rewritten.** The label and the select both render in the current tree. A render test pins the control in the create form and the edit template, so the report is answered with evidence and any real regression fails the suite.

**Settled: the deployed database gets the migration; the application gets no runtime schema guard.** A schema check inside the request path would hide the real problem, which is an unapplied migration.

</Callout>

## Answer: a new portal and access area

Access areas and their four actions already live in data. A tbl_permissions row carries a portal, a resource, and an action, so adding an access area is a seed operation and needs no schema change. The portal itself is code driven, and the four existing portals are hard coded in four places. A new portal, such as Marketing or Maintenance, is supported with a bounded set of changes:

- **Role and portal key.** UserModel.role and the role name select the portal. Add the new role value and its label map entry.
- **Route group.** routes/web.php holds one group per portal. Add the group and its route names.
- **Middleware.** bootstrap/app.php:35-42 holds one alias per portal. Add the alias, the middleware class, and the model scope it reads.
- **Portal allow-lists.** PermissionModel holds PORTAL_LABELS and KNOWN_PORTALS (line 33). Add the portal to both so permission strings normalise and display.
- **Access areas.** Seed the tbl_permissions rows for the portal and its resources. No migration is needed.
- **Shell.** Add the Blade layout and sidebar for the portal, and a dashboard route with its landing view.
- **Seeder and tests.** Add the role and its permissions to the seeder, and a test that a user of the new portal reaches its dashboard and no other portal.

Nothing in the twelve defects depends on this work, so the plan delivers the answer and leaves the code unchanged.

## Notification click-through

The payload already exists. Every creating controller stores a relative route in the notification data object, for example `route('owner.booking.details', ['id' => $booking->id], false)` in BookingController:778 and `route('client.my-bookings.index', [], false)` in Notifiable.php:116. Three creation sites still omit it, so the plan adds a fallback that maps the notification type to a portal index when no route is present.

The change is three parts: expose the stored route in the controller payload, render it as the row target in `notifications.js`, and navigate to it after the mark-as-read call resolves. No schema change and no new column.

## Verification

The suite currently passes: `php artisan test --compact` reports 471 passed and 2214 assertions in about 40 seconds, on in-memory SQLite. Each workstream adds its own regression test, then the whole suite runs once on the frozen build.

<Checklist
  id="b-checklist"
  items={[
    {
      id: "v1",
      label: "php artisan test --compact",
      note: "Full suite green, including the new regression tests.",
    },
    {
      id: "v2",
      label: "php artisan migrate:status on the deployed database",
      note: "Every migration reports Ran, including add_gallery_approval_columns.",
    },
    {
      id: "v3",
      label: "Owner: assign, accept, decline, reassign",
      note: "Assign a photographer, accept as the photographer, cancel the assignment, then assign a replacement from the bookings table.",
    },
    {
      id: "v4",
      label: "Client: cancel a booking 72 hours out",
      note: "No CSRF message. The booking cancels and the recovery row renders.",
    },
    {
      id: "v5",
      label: "Owner: open Update Status on a cancelled booking",
      note: "The confirm action is disabled and the reason is stated. No booking-status prompt.",
    },
    {
      id: "v6",
      label:
        "Owner and photographer: attempt completion without a published gallery",
      note: "Both portals refuse with the same message.",
    },
    {
      id: "v7",
      label: "Owner: grant permissions, then save Update Studio",
      note: "No name-field error, and the studio form reports success or names the failing field.",
    },
    {
      id: "v8",
      label: "Owner: bookings table while a new booking arrives",
      note: "The live refresh adds the booking, five rows stay visible, and the footer count matches.",
    },
    {
      id: "v9",
      label: "Click a notification",
      note: "The record opens and the notification is marked read.",
    },
    {
      id: "v10",
      label: "Login screen",
      note: "Type a valid email. No valid-email message appears before submit.",
    },
  ]}
/>

### Open Questions

<QuestionForm
  id="b-questions"
  questions={[
    {
      id: "q1",
      title: "When a notification is clicked, where should the record open?",
      mode: "single",
      options: [
        {
          id: "q1a",
          label: "Same tab",
          detail:
            "Mark the notification read, then navigate the current tab. Simplest and matches the other in-app links.",
          recommended: true,
        },
        {
          id: "q1b",
          label: "New tab",
          detail:
            "Open the record in a new tab so the current list stays in place.",
        },
      ],
    },
    {
      id: "q2",
      title: "How should a booking with no active photographer be cancellable?",
      mode: "single",
      options: [
        {
          id: "q2a",
          label: "Allow cancellation while more than 24 hours out",
          detail:
            "Add in_progress to the cancellable statuses and keep the 24-hour gate. Fixes the reported case with one condition.",
          recommended: true,
        },
        {
          id: "q2b",
          label: "Also allow inside 24 hours when no photographer is active",
          detail:
            "Wider change to the cancellation policy and it needs a decision on the refund percentage.",
        },
      ],
    },
    {
      id: "q3",
      title: "Should the new portal work be done in this task?",
      mode: "single",
      options: [
        {
          id: "q3a",
          label: "Answer only",
          detail:
            "Deliver the written checklist and keep the code unchanged. Nothing in the twelve defects depends on it.",
          recommended: true,
        },
        {
          id: "q3b",
          label: "Move the portal lists into configuration now",
          detail:
            "Smaller diff per future portal, but it touches routing and middleware during a defect-fix build.",
        },
      ],
    },
    {
      id: "q4",
      title: "Anything else that must hold for this release?",
      mode: "freeform",
      placeholder: "Add constraints, dates, or acceptance conditions.",
    },
  ]}
  submitLabel="Submit answers"
/>
---
Live plan: /plans/plan-32b8cbf281364f7a