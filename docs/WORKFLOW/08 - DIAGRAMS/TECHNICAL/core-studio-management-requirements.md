# Core Studio Management Requirements Reference

> **In plain terms:** This reference gathers the studio-management requirements supplied in Task 10. It describes future work and does not claim current implementation.

**Source:** `prompt/tasks/10.md`.
**Status:** Planned future requirements; none are claimed as implemented by this record.

## Functional and security requirements

- Registration must capture complete owner, studio, location, service, pricing, organizational-role, down-payment, and social-link information; service categories include an Other option.
- Administrators require a dedicated login interface and email-based OTP. Sensitive administrative functions require the stronger flow.
- Permit verification gates full studio configuration and operation. Permit records include expiry dates, valid/expired status, and re-verification handling.
- Administrators can view pending applications and uploaded documents, select standardized rejection reasons, record resubmission counts, and notify owners after approval.
- New users receive an optional onboarding flow with Next and Skip controls. Employee provisioning creates a valid account, sends temporary credentials securely, and forces a password replacement at first login.
- RBAC supports combined responsibilities, granular permissions, and category-level Select All controls.
- Deletion uses soft deletion or archiving. Ordinary actions must not permanently remove operational records.
- Schedules support distinct daily start/end times. Attendance requires live camera capture plus geolocation-radius validation for both time-in and time-out.
- Clients can save favorites and see package/gallery images while browsing. Owners control prices, down payments, and discount rules.

## Explicit exclusions

- No hardcoded or mandatory system-level discount, including an automatic full-payment discount.
- No permanent hard deletion.

## Dependencies

Email delivery, permit-expiry data, built-in document review, account provisioning, camera permission, location services, and configured attendance radii are prerequisites for their respective requirements.

## Delivery status

This reference is a requirements baseline for design, development, testing, and evaluation. It neither authorizes implementation nor asserts that the listed capabilities exist today.
