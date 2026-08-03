# Planned Core Studio Management Requirements

> **In plain terms:** This document organizes the requested studio-management improvements for future work. It does not claim that any of them have been built.

**Status:** Planned requirements only. No application behavior is changed by this record.

## Objective

Task 10 turns evaluator feedback into a future requirements baseline for studio registration, verification, administration, employee management, access control, attendance, and client booking. A separate approved delivery task must choose implementation order, data changes, and tests before any requirement is built.

## Requirement groups

1. **Registration and commercial settings:** collect owner, studio, location, role, service, price-range, down-payment, expanded social-link, and other-service details; owners control discount rules.
2. **Administrative security and review:** use a dedicated administrator login with email OTP; review permits in-app, use standardized rejection reasons, count resubmissions, and notify approved owners.
3. **Permit-gated access:** capture permit expiry, prevent full studio setup until verification, and require re-verification after expiry.
4. **Accounts and onboarding:** provide an optional Next/Skip onboarding guide; provision employee accounts with emailed temporary credentials and force a password change at first login.
5. **Roles and records:** support combined dynamic roles, category-level permission selection, and soft deletion or archiving in place of permanent deletion.
6. **Attendance and client experience:** support per-day schedules, live photo and geolocation checks at time-in/out, client favorites, visible package and gallery images, and owner-controlled pricing and discounts.

## Constraints and dependencies

- Email delivery is required for OTP, approval, and employee-account messages.
- Permit validity must be available before permit-gated access can be enforced.
- Live attendance needs device-camera and location-service access plus a configured geolocation radius.
- Employee access depends on successful account creation, secure credential delivery, and first-login password replacement.
- The system must not impose a mandatory automatic discount or permanently delete records.

## Future delivery rule

Future work must preserve existing login-first behavior until an approved implementation explicitly changes it. It must separately define authorization boundaries, data migrations, validation, accessibility, security controls, migration paths, and automated/manual verification before delivery.
