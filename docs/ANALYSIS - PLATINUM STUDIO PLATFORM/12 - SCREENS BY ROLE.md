# 12 - SCREENS BY ROLE

[Back to start](00%20-%20START%20HERE.md) · Previous: [11 - PARTS IN DETAIL](11%20-%20PARTS%20IN%20DETAIL.md)

## Read this first

The source material describes portal needs and the current code exposes routes and Blade views. It does not provide one approved screen inventory. The table below is therefore a derived map: each row is grounded in a current route, view, or stated role need, and any future screen not named by that evidence stays in the missing table. `S-` numbers identify screens; they are not build order.

## The seven portal levels

| Level | What they come here to do | Evidence |
|---|---|---|
| Administrator | Oversee platform users, studios, freelancers, categories, locations, plans, reviews, and reports. | `routes/web.php`, administrator group; frozen role requirements |
| Studio owner | Operate studios, services, people, bookings, galleries, subscriptions, payroll, procurement, and permissions. | `routes/web.php`, owner group; frozen core-studio requirements |
| Client | Discover, book, pay, follow bookings, view galleries, and review. | client controllers/routes and frozen plain analysis |
| Freelancer | Maintain services, accept work, perform bookings, and deliver galleries. | freelancer controllers/routes and frozen roadmap |
| Studio HR | Manage employees, schedules, leave, overtime, payroll settings, and HR reports. | `routes/web.php`, studio HR group |
| Studio finance | Review procurement, deliveries, returns, payments, payroll, and finance reports. | finance controllers/routes and procurement service |
| Studio photographer | Follow assignments, attendance, gallery delivery, and permitted procurement actions. | photographer controllers/routes and role requests |

`owner-super-admin` is shown within the owner level because current role records treat it as an owner variant.

## Administrator

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-01 | Administrator dashboard | Platform counts, filters, exports, and administrative summaries. | User, studio, location, freelancer, review, or plan screens. | P-01, P-02, P-11 | `routes/web.php`, lines 72–74 |
| S-02 | Studio and user review | Pending studios, users, freelancers, approvals, rejections, and details. | Back to dashboard or record list. | P-01, P-02, P-07 | `routes/web.php`, lines 83–98 |
| S-03 | Platform configuration | Categories, locations, subscription plans, and review moderation. | Back to dashboard or selected record. | P-03, P-06, P-10 | `routes/web.php`, lines 101–125 |

## Studio owner

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-04 | Owner dashboard | Studio summaries, filters, exports, alerts, and operational links. | Studio, booking, gallery, people, or finance work. | P-01, P-07, P-11 | `routes/web.php`, lines 136–138 |
| S-05 | Studio and people management | Studio profile, members, photographers, employees, roles, permissions, and schedules. | Selected record or back to owner dashboard. | P-02, P-07, P-08 | `routes/web.php`, lines 143–153 and 185–197 |
| S-06 | Bookings and galleries | Booking details, availability, assignments, status, completion, uploads, publish, and portfolio. | Assignment, gallery, or booking history. | P-04, P-05, P-06 | `routes/web.php`, lines 156–176 |
| S-07 | Commercial and operations | Services, packages, subscription status, payroll settings, procurement, and assistant configuration. | Selected work area or back to dashboard. | P-03, P-09, P-10, P-12 | `routes/web.php`, lines 200–303 |

## Client

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-08 | Provider and service discovery | Provider, category, location, service, package, and availability information. | Booking details. | P-03 | Client controllers and frozen marketplace requirements |
| S-09 | Booking and payment | Chosen service, dates, booking details, payment state, and confirmation or failure. | My bookings. | P-04 | Booking controllers and payment routes |
| S-10 | My bookings and gallery | Booking state, payment result, published gallery, and review entry. | Booking detail, gallery, or review. | P-04, P-06, P-11 | Frozen plain analysis and client routes |

## Freelancer

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-11 | Freelancer dashboard and profile | Work summary, profile, service, and availability controls. | Services or booking work. | P-01, P-03 | Freelancer controllers and dashboard service |
| S-12 | Assigned booking | Booking details, assignment state, availability, and permitted updates. | Gallery delivery or dashboard. | P-04, P-05 | Freelancer booking routes and frozen roadmap |
| S-13 | Gallery delivery | Upload, update, publish-related hand-off, and work history. | Assigned booking. | P-06, P-11 | Freelancer gallery routes |

## Studio HR

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-14 | HR dashboard | Employee, attendance, leave, overtime, and payroll summaries. | Selected HR area. | P-07, P-08, P-11 | `routes/web.php`, lines 315–317 |
| S-15 | Employees and schedules | Employee records, roles, schedules, and status. | Employee detail or request review. | P-07, P-08 | `routes/web.php`, lines 342–350 |
| S-16 | Leave, overtime, and payroll | Requests, approvals, payroll settings, and state changes. | Employee or dashboard. | P-08, P-11 | `routes/web.php`, lines 320–363 |

## Studio finance

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-17 | Finance dashboard | Finance summaries and studio-scoped records. | Procurement, payroll, or reports. | P-07, P-08, P-09, P-11 | Finance dashboard service and routes |
| S-18 | Procurement review | Request state, approval, purchase order, delivery, return, replacement, and payment actions. | Request details or dashboard. | P-09, P-11 | `ProcurementWorkflowService.php` |
| S-19 | Payroll and payment records | Payroll settings or finance records within assigned studio scope. | Dashboard or selected employee/request. | P-08, P-09 | Finance controllers and requests |

## Studio photographer

| # | Screen | What is on it | Where it leads | Parts behind it | Where the need came from |
|---|---|---|---|---|---|
| S-20 | Photographer dashboard | Assigned work, attendance, notifications, and operational summary. | Assignment or attendance. | P-01, P-05, P-11 | Photographer dashboard and middleware |
| S-21 | Assignment and attendance | Assigned booking, status, check-in/out, and permitted requests. | Gallery or dashboard. | P-05, P-08 | Photographer routes and request classes |
| S-22 | Gallery and procurement work | Gallery delivery and permitted procurement actions. | Assigned booking or request. | P-06, P-09 | Photographer routes and procurement service |

## Where the levels overlap

| Screen or area | Administrator | Owner | Client | Freelancer | HR | Finance | Photographer |
|---|---|---|---|---|---|---|---|
| Dashboard | Platform | Studio | Client | Work | HR | Finance | Assigned work |
| Booking | Oversight | Manage | Own | Assigned | No | Financial view | Assigned |
| Gallery | Moderate | Manage | Published own result | Deliver own work | No | No | Deliver assigned work |
| People and permissions | Platform users | Studio scope | Own profile | Own profile | Assigned staff | Assigned finance staff | Own profile |
| Procurement | Oversight | Approve or report | No | Permitted requests only | Permitted requests | Review and pay | Permitted requests |

The source and current routes do not make every overlap cell equivalent to full CRUD. Permission middleware, request authorization, and studio assignment still decide the actual action. That is why the matrix describes screen reach and the part pages describe authority separately.

## What is missing

| What is missing | Why it is not above |
|---|---|
| One approved final screen inventory and navigation design | The material gives needs and routes, not an approved wireframe or navigation contract. |
| A dedicated cancellation-remedy screen | The policy and financial outcome are unresolved under Q-01 and Q-11. |
| A dedicated renewal and failed-payment recovery screen | Renewal, card-on-file, and reactivation decisions remain open under Q-02. |
| A final public landing-page screen | Task 09 is documentation-only until Q-03 is answered. |
