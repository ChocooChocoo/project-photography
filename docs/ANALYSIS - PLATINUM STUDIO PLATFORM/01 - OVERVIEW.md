# 01 - OVERVIEW

[Back to start](00%20-%20START%20HERE.md) · Next: [02 - DOCUMENT FINDINGS](02%20-%20DOCUMENT%20FINDINGS.md)

## What it is

Platinum is a web platform for photography businesses. A client can discover a studio or freelancer, request a service, make a booking, pay, and receive gallery work. Studio owners and staff operate the business through separate portal areas.

## Who uses it

| Role | Main work |
|---|---|
| Administrator | Oversees users, studios, freelancers, categories, locations, subscription plans, reviews, and platform reporting. |
| Studio owner | Manages studios, services, packages, staff, bookings, assignments, galleries, subscriptions, payroll settings, procurement, and permissions. |
| Client | Finds services, books, pays, follows bookings, views galleries, and leaves reviews where allowed. |
| Freelancer | Maintains a profile and services, accepts work, performs bookings, and delivers galleries. |
| Studio HR | Manages employees, schedules, attendance-related records, leave, overtime, payroll settings, and HR dashboards. |
| Studio finance | Handles finance dashboards, procurement review, deliveries, returns, payments, and finance-related records. |
| Studio photographer | Handles assigned work, attendance, gallery delivery, and permitted operational actions. |

These role names come from the current middleware, controllers, views, and role records. `owner-super-admin` is a studio-owner variant, not a separate portal.

## What it does

The application starts with registration, sign-in, email verification, and role-specific routing. Clients and providers meet through discovery and service records. A booking connects the client, chosen service, payment, assigned photographer, gallery, review, and notifications. Owners and staff manage the studio around that booking: people, schedules, attendance, leave, overtime, payroll settings, procurement, subscriptions, and operational dashboards. The assistant provides a photography-focused help conversation with stored history and owner configuration.

## What state it is in

This is a partly complete working application, not a blank proposal. Current code contains all seven portal route groups, a relational `tbl_` record model, payment-provider webhook routes, gallery publication paths, studio operations, procurement, subscription expiry and grace handling, and assistant guardrails. The old roadmap still contains unfinished or policy-blocked work, especially photographer cancellation outcomes, renewal and reactivation, and some core studio-management decisions.

## What it does not do

The current record does not prove a complete end-to-end remedy after a paid booking loses its photographer. It also does not prove card-on-file renewal, failed-renewal webhooks, or reactivation. A historical requirement or proposal is not treated as shipped merely because it appears in a roadmap.

## Where the details are

[05 - SYSTEM ARCHITECTURE](05%20-%20SYSTEM%20ARCHITECTURE.md) explains the parts and hand-offs. [06 - DIAGRAMS](06%20-%20DIAGRAMS.md) shows the main journeys. [07 - DEVELOPMENT ROADMAP](07%20-%20DEVELOPMENT%20ROADMAP.md) gives the build order, while [04 - COMBINED FINDINGS](04%20-%20COMBINED%20FINDINGS.md) explains why the order is needed.
