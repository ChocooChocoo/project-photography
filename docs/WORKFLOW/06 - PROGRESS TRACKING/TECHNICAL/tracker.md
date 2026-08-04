# Progress Tracker

> **In plain terms:** This is the single current view of every roadmap phase. A phase is marked Completed only when implementation evidence exists; Planned and Blocked work is not presented as finished.

**Evidence date:** 2026-08-04.
**Automated baseline:** `php artisan test --compact` — 89 passed, 447 assertions.

| Phase | Status | Current position | Evidence or next condition |
| --- | --- | --- | --- |
| 1 — Stabilize | In Progress | Most repair items completed; follow-up verification remains. | [Detailed history](delivery-history.md) |
| 2 — Complete | In Progress | Most workflow-completion items delivered; portfolio past-work remains partial. | [Detailed history](delivery-history.md) |
| 3 — Core New Features | In Progress | Gallery review and revision window delivered; trial work remains partial. | [TASK-002](../../04%20-%20TASK%20TRACKING/TECHNICAL/records/task-002.md) |
| 4 — Workflow Improvements | Completed | 4.1–4.5 delivered: photographer visibility, direct freelancer response, Featured disclosure, and landmark directions. | [TASK-011](../../04%20-%20TASK%20TRACKING/TECHNICAL/records/task-011.md) |
| 5 — Advanced Features | Planned | No approved implementation evidence. | Start after Phase 4 scope is stable. |
| 6 — Automation | Planned | Subscription lifecycle notices are delivered in Phase 10; the remaining automation items are not. | Start after Phase 5 is stable. |
| 7 — Resource Authorization & Test Coverage | Planned | No phase-wide authorization/test delivery recorded. | Add incrementally with future features. |
| 8 — AI Assistant | Completed | Photography assistant, guardrails, credential protection, and documentation delivered. | `ChatbotFeatureTest`, `ChatbotAiGuardrailsTest` |
| 9 — Cancellation Contingency | Blocked | Policy-dependent remedy work is not approved; only universal items may proceed. | [QST-001](../../00%20-%20START%20HERE/TECHNICAL/open-items.md#qst-001--photographer-cancellation-policy) |
| 10 — Subscription Lifecycle | In Progress | 10.1–10.6 completed; 10.7–10.9 remain planned. | `SubscriptionLifecycleTest`, `SubscriptionNotificationTest` |
| 11 — Public Landing Page | Planned | Documentation exists; no application implementation is approved. | [Landing-page plan](../../03%20-%20PLANNING/TECHNICAL/landing-page.md) |
| 12 — Core Studio Management | Planned | Requirements documentation exists; no implementation order is approved. | [Core-management plan](../../03%20-%20PLANNING/TECHNICAL/core-studio-management.md) |

## Delivery checklist

`[x]` completed; `[ ]` not complete, including partial and blocked work.

### Phase 1 — Stabilize
- [x] 1.1 Barangay JSON encoding
- [x] 1.2 Online gallery image delivery
- [ ] 1.3 UI alignment and overflow (targeted fix only)
- [x] 1.4 Required owner profile photo
- [x] 1.5 Payment webhooks
- [x] 1.6 Procurement escalation schedule

### Phase 2 — Complete
- [x] 2.1 Service starting price
- [x] 2.2 Owner income report
- [x] 2.3 Photographer assignment deadline
- [x] 2.4 Client booking calendar
- [x] 2.5 Studio-side booking cancellation
- [ ] 2.6 Independent portfolio gallery (past-work view incomplete)
- [x] 2.7 Pending booking expiry
- [x] 2.8 Notification coverage
- [x] 2.9 Booking-payment budget updates
- [x] 2.10 Review moderation

### Phase 3 — Core New Features
- [ ] 3.1 Package/service selling media (partial)
- [x] 3.2 Gallery draft, publish, and review
- [x] 3.3 Post-completion revision window
- [ ] 3.4 Subscription free trial (partial)

### Phase 4 — Workflow Improvements
- [x] 4.1 Assigned photographer profile visibility
- [x] 4.2 Direct freelancer booking flow
- [x] 4.3 Featured premium listing badge
- [x] 4.4 On-location landmark directions
- [x] 4.5 Subscription-rank transparency

### Phase 5 — Advanced Features
- [ ] 5.1 Geolocation studio discovery
- [ ] 5.2 Equipment assignment per booking
- [ ] 5.3 Long-term or recurring bookings
- [ ] 5.4 Photo-role quality assurance

### Phase 6 — Automation
- [ ] 6.1 Pending-expiry notification
- [ ] 6.2 Gallery-upload deadline notification
- [ ] 6.3 Studio-verification queue notification
- [x] 6.4 Subscription-expiry reminders (delivered in Phase 10)
- [ ] 6.5 Payroll-generation trigger
- [ ] 6.6 Photographer assignment suggestion

### Phase 7 — Resource Authorization and Test Coverage
- [ ] 7.1 Resource-level policies
- [ ] 7.2 Core feature test coverage

### Phase 8 — AI Assistant
- [x] 8.1 AI-assisted chatbot
- [x] 8.2 Photography-only scope
- [x] 8.3 Input and output guardrails
- [x] 8.4 Credential protection, failures, and limits
- [x] 8.5 User surfaces and documentation

### Phase 9 — Cancellation Contingency
- [ ] 9.1 Owner recovery from deadlocked booking
- [ ] 9.2 Photographer-cancellation notifications
- [ ] 9.3 Photographer substitution
- [ ] 9.4 Reschedule path
- [ ] 9.5 Refund execution
- [ ] 9.6 Booking credit ledger
- [ ] 9.7 Cancellation record
- [ ] 9.8 Freelancer emergency pool
- [ ] 9.9 Value-gap refund
- [ ] 9.10 Late-cancellation restriction
- [ ] 9.11 Backup photographer

### Phase 10 — Subscription Lifecycle
- [x] 10.1 Trial end-date alignment
- [x] 10.2 Trial expiry
- [x] 10.3 Paid subscription expiry
- [x] 10.4 Grace period
- [x] 10.5 Expiry access restriction
- [x] 10.6 Subscription notification ladder
- [ ] 10.7 Reactivation
- [ ] 10.8 Recurring billing and card on file
- [ ] 10.9 Cancellation and upgrade lifecycle

### Phase 11 — Public Landing Page
- [ ] 11.1 Bootstrap public page
- [ ] 11.2 Responsive layouts
- [ ] 11.3 Login and registration calls to action
- [ ] 11.4 Approved public-root replacement
- [ ] 11.5 Minimal custom styling

### Phase 12 — Core Studio Management
- [ ] 12.1 Registration, pricing, categories, and social links
- [ ] 12.2 Administrator access and permit review
- [ ] 12.3 Permit-gated access and re-verification
- [ ] 12.4 Onboarding and employee credential flow
- [ ] 12.5 Roles, permissions, and archiving
- [ ] 12.6 Schedules, attendance, favorites, media, and discounts

See the full [delivery history](delivery-history.md), [plain-language status](status-plain.md), [risks](risks.md), [issues](issues.md), and [change log](change-log.md).
