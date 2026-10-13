← [Previous: Phase 11](./phase-11.md) | [Next: Phase 13](./phase-13.md) →

# Phase 12: User-to-User Chat

**Status:** ✅ Complete, locally enabled and verified (2026-10-13). Full suite: 642 PHP tests / 3162 assertions; 7 JavaScript regressions passed. Authenticated MySQL chat acceptance passed. Implementation included in Phase 12-only commit; production is unverified.

**Goal:** Users can send direct messages to each other (for example client and studio, client and photographer), using database storage and AJAX polling.

## Dependencies
Phase 8 (clear booking participants).

## Decision
Polling every few seconds, no websockets (matches `resources/views/owner/view-bookings.blade.php:1683`).

## Investigation and proposed design
- Phase 11 committed independently as `6b6fdc88` (24 files); fresh precommit verification passed **628 PHP tests / 3056 assertions and 5 JavaScript regressions**. Unrelated edits, deletions and uploads excluded.
- No existing user-to-user conversation/message models or controller found. Chatbot tables remain separate. `Notifiable::notifyNewMessage()` exists but references missing `messages.show`; route must target the recipient's real chat portal.
- Proposed persistence: canonical ordered user pair plus booking scope; non-null scope key for nullable booking uniqueness, following Phase 11's demonstrated MySQL NULL-index constraint. Store plain-text messages, timestamps and recipient read time. No files, rich text, group chats or websocket dependency.
- Proposed access: clients contact booking's owner/direct freelancer, assigned photographers and active HR/finance staff with that studio's booking-view/manage permission. Permanent staff and owner can chat internally only while sharing active studio affiliation. No self-messaging, global user directory, unrelated clients/studios or implicit admin access.
- Every inbox, thread, poll, send, read and unread endpoint rechecks participation and current eligibility, not merely conversation ownership. Temporary fallback participants require live Phase 11 assignment/grant and cannot obtain unscoped staff chat.
- Approved policy: client/owner/permanent-staff conversations remain usable after normal booking completion. Temporary fallback freelancer loses chat access when assignment/grant expires or is revoked. Remaining authorized participant may retain history but cannot send to an ineligible participant.
- Proposed UI: shared portal-consistent Messages page/partial, sidebar unread badge and booking-context Message buttons. Poll open visible thread every 5 seconds; cancel polling when hidden/closed, resume on visibility. Explicit empty/loading/error states, keyboard labels, escaped output and preserved unsent draft on failure.
- Proposed limits/proof: text-only body up to 4000 characters, send throttle, notification at most once per conversation/minute. Test disallowed pairs, foreign conversation IDs, expired fallback, duplicate conversation creation, incremental polling, sender-aware read receipts/unread counts, escaped malicious text and hidden-tab polling behavior. Run focused/full PHP, JS regression, build and authenticated two-user clone acceptance. Local rollout needs fresh backup and approval.

## Execution checkpoints
- [x] Add chat tables/models and participant policy; prove allowed/disallowed pairs and uniqueness.
- [x] Add authenticated portal endpoints; prove incremental messages, send validation/throttle, read cursors and unread counts.
- [x] Add shared inbox/thread, sidebar badge, booking Message links and hidden-tab-aware polling. Browser acceptance pending.
- [x] Verify focused/full tests, build, formatting, mobile/light/dark and authenticated two-user clone flow.
- [x] Request backup/migration approval before local enablement; preserve unrelated data and changes.

## Verified slices
- Test-first missing chat schema/flow and missing shared view/poller reproduced before implementation.
- Focused chat + freelancer suite: **27 passed / 159 assertions**. Chat tests cover regular completed-booking pairs, direct freelancer and assigned photographer, active HR booking permission, shared-studio internal chat, self/foreign-thread denial, canonical scoped conversation reuse, plain-text length/empty validation, sender spoofing prevention, incremental messages/read receipts, read-cursor boundary, unread counts, 30 sends/minute, one notification/conversation/minute, fallback expiry with client read-only history, portal rendering and migration rollback.
- Send/open throttles initially shared generic Laravel throttle bucket; regression revealed premature send blocking. Separate `chat-open` / `chat-send` prefixes fixed it.
- Pre-rollout unread badge regression reproduced missing-table 500; unread now returns zero while chat migration is pending.
- Node: **7 passed**, including new visibility/abort/no-overlap polling and text-node rendering/cursor regression, plus existing delivery/fallback regressions. New PHP files pass Pint after formatting.
- UI preserves portal fonts, palette and focus styles; separate inbox/thread regions reflect actual conversation tasks. Plain text uses `textContent`, never `innerHTML`. Composer retains unsent draft on failure; only fetched cursor advances read marking, not immediate own-send rendering.
- Final full suite: **642 passed / 3162 assertions**. Node: **7 passed**. Build, Blade compilation, new-file Pint and scoped diff checks passed. Impeccable detector on chat views/partial/script returned `[]`.
- Added regression for sender-name HTML injection through existing notification renderer; chat notification escapes the name and does not embed message bodies. Fresh reset chain explicitly clears chat children before booking parents; no reseeding executed.
- Disposable MySQL migration up/down/up preserved **66 clone bookings**. Authenticated owner/client: opened booking conversation, sent client text and owner reply, observed five-second polling, unread count 1 then 0, and Sent-to-Read receipts. HTML-looking body remained literal text; thread contained zero image nodes.
- Browser fallback: live grant allowed client/freelancer exchange; after expiry freelancer poll returned 403 and visible thread cleared, client retained history with disabled composer, client send also returned 403. No cleanup dependency.
- Browser polling visibility: simulated hidden document yielded **0 thread requests over 6.5 seconds**; restoring visibility yielded **1 immediate poll**. Node proves abort/no-overlap scheduling independently.
- Browser failure recovery: fixture 503 retained draft, focused error status and re-enabled Send; removing failure allowed retry (201), clearing draft only after success.
- Owner/client 1280px and 390px, light/dark: no horizontal overflow, 44px primary controls, visible 3px keyboard focus. Mobile screenshots inspected in approved temp directory. No new JavaScript exceptions recorded during batched chat inspection; unrelated existing template translation/demo asset errors remain.
- User approved fresh backup and local enablement. Applied only `2026_10_13_160000_add_user_chat.php` to `platinum`. Original **71 tables / 2461 rows** matched row counts and SHA-256 before/after; migration ledger excluded. New chat tables empty, no fixture messages seeded locally.
- Backup: `C:\Users\marka\AppData\Local\Temp\opencode\phase9-before-migrations-20261013-184241.sql` (utility retains Phase 9 prefix), 856527 bytes; SHA-256 `da6e4c5b0f1726cc9310e951532591d3bae07ab9d62202a2604e1fb821d69aac`.

## Tasks
- [x] The AI chatbot exists and is separate (`tbl_chatbot_*`, `ChatbotController.php`). Do not reuse its tables.
- [x] Notification helpers exist (`app/Traits/Notifiable.php`).
- [x] Tables: `tbl_conversations` and `tbl_messages`, canonical pair + non-null `booking_scope` uniqueness, last-message/notification timestamps, message/unread indexes, models and relations.
- [x] Rules: booking participants or currently affiliated studio colleagues only; self and foreign access denied. `ChatAccess` rechecks every operation, including Phase 11 expiry; no admin bypass or global directory.
- [x] `ChatController`: inbox, open/reuse, incremental poll (100 messages/page), send, cursor-bound mark-read and unread count. Separate open/send rate buckets, 30/minute.
- [x] All seven portal prefixes use auth/password-changed plus active-account portal guard; participant policy remains mandatory. Chat preserves access for existing booking communication without requiring unrelated owner permit/subscription management routes.
- [x] Shared `chat/inbox-thread.blade.php` renders inside each role's existing layout. Shared sidebar link/unread badge; visible-thread/inbox polling every 5 seconds, badge polling every 30 seconds. Hidden/pagehide abort and stop timers; resume on visibility.
- [x] Message links added to client booking/history details, owner booking details, photographer assignment table and fallback freelancer assignment page.
- [x] Text-only 4000-character body, text-node rendering, escaped notification sender name, no message body in notification; notification at most once/conversation/minute under conversation lock.
- [x] Tests and authenticated browser acceptance cover authorization, sending/polling, read receipts, counts, expiry, output escaping, migration/reset integration and failure retry.

## Done when
- [x] Two allowed users can exchange messages, with new messages appearing within the poll interval.
- [x] Disallowed pairs get 403.
- [x] Unread counts update.

## Files and boundaries
- New backend: `app/Models/ConversationModel.php`, `MessageModel.php`; `app/Services/ChatAccess.php`; `app/Http/Controllers/ChatController.php`; `app/Http/Middleware/ChatPortalMiddleware.php`; `database/migrations/2026_10_13_160000_add_user_chat.php`.
- New UI: `resources/views/chat/index.blade.php`, `chat/inbox-thread.blade.php`, `partials/chat-sidebar-link.blade.php`; `public/assets/js/user-chat.js`.
- Modified: `routes/web.php`, message helper in `app/Traits/Notifiable.php`, explicit fresh reset table order, seven portal sidebars, shared portal scripts, client/owner booking details and photographer/fallback assignment Message links.
- Tests: `tests/Feature/Chat/ChatTest.php`, `tests/Integration/UserChatPolling.test.cjs`. This file tracks scope/evidence.
- No attachments, rich text, group chat, typing indicators, websocket dependency or AI-chatbot table reuse. User authorized Phase 12-only commit; unrelated changes preserved. Fresh precommit verification repeated: **642 PHP tests / 3162 assertions and 7 JavaScript regressions passed**. Phase 13 not started.

← [Previous: Phase 11](./phase-11.md) | [Next: Phase 13](./phase-13.md) →
