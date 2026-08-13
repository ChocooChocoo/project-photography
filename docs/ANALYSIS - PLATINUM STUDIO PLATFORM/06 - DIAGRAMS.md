# 06 - DIAGRAMS

[Back to start](00%20-%20START%20HERE.md) · Previous: [05 - SYSTEM ARCHITECTURE](05%20-%20SYSTEM%20ARCHITECTURE.md) · Next: [07 - DEVELOPMENT ROADMAP](07%20-%20DEVELOPMENT%20ROADMAP.md)

These pictures use Mermaid. Each is followed by a plain-language reading. They show the current arrangement unless they are explicitly labelled proposed.

## 1. The big picture

```mermaid
flowchart LR
    people["Clients, providers, owners, and staff"]
    portals["Role-specific portal pages"]
    rules["Middleware and permission checks"]
    booking["Shared booking record"]
    operations["Studio operations and procurement"]
    gallery["Gallery and review records"]
    payments["Payment providers"]
    messages["Notifications and assistant"]
    people --> portals
    portals --> rules
    rules --> booking
    booking --> payments
    booking --> gallery
    portals --> operations
    operations --> booking
    portals --> messages
    messages --> people
```

**Reading this:** People enter through role-specific pages. Middleware and permissions decide which actions reach the shared booking and operational records. Payments, galleries, reviews, notifications, and the assistant connect around that shared core.

## 2. How a booking gets done

```mermaid
flowchart TD
    start(["Client chooses a provider"])
    details["Client sends booking details"]
    record["Application creates booking record"]
    pay["Application starts payment"]
    provider{"Provider confirms payment"}
    confirmed["Booking and payment become confirmed"]
    review["Provider or owner continues assignment and delivery"]
    failed["Payment remains failed or needs attention"]
    start --> details --> record --> pay --> provider
    provider -->|"yes"| confirmed --> review
    provider -->|"no"| failed
```

**Reading this:** The client supplies a booking, the application creates the shared record, and a payment provider message decides whether the booking can continue as confirmed. The exact financial and retry policy is outside what the current material settles.

## 3. Who talks to whom during payment

```mermaid
sequenceDiagram
    participant client as Client
    participant pages as Portal pages
    participant app as Laravel application
    participant provider as Payment provider
    client->>pages: Sends booking request
    pages->>app: Passes validated booking details
    app->>provider: Starts or verifies payment
    provider-->>app: Returns confirmation or failure
    app-->>pages: Shows booking state
    pages-->>client: Shows next action
```

**Reading this:** The client begins the exchange. The application talks to the provider and then updates the shared records and portal response. A provider response is not itself a final product policy; the business outcome still has to be defined for unresolved cases.

## 4. The order of the phases

```mermaid
flowchart LR
    phase1["Phase 1 - Foundations dependable"]
    phase2["Phase 2 - Booking and media journeys"]
    phase3["Phase 3 - Role daily work"]
    phase4["Phase 4 - Discovery and operations"]
    phase5["Phase 5 - Automation and protection"]
    phase6["Phase 6 - Cancellation and subscriptions"]
    phase1 --> phase2 --> phase3 --> phase4 --> phase5 --> phase6
```

**Reading this:** The order starts with evidence, defects, and boundaries. Later work depends on the booking and role foundations. Phase 6 now records the approved photographer-recovery and subscription lifecycle outcomes; ordinary cancellation and other policy questions remain separate.

## 5. The life story of a booking

```mermaid
stateDiagram-v2
    [*] --> Requested
    Requested --> PaymentPending : payment starts
    PaymentPending --> Confirmed : provider confirms
    PaymentPending --> PaymentFailed : provider rejects
    Confirmed --> Assigned : owner assigns photographer
    Assigned --> InProgress : work begins
    InProgress --> GalleryPending : work is completed
    GalleryPending --> Published : owner or provider publishes
    Published --> Completed : client receives result
    Confirmed --> Cancelled : approved cancellation
    PaymentFailed --> [*]
    Completed --> [*]
    Cancelled --> [*]
```

**Reading this:** A booking can fail before confirmation, move through assignment and gallery delivery, or enter the approved photographer-recovery path. Rejection, escalation, or deadline expiry leads to a queued full manual refund; ordinary cancellation remains separate.

## 6. The proposed arrangement

```mermaid
flowchart LR
    current["Current booking, role, payment, and operations"]
    policy["Approved cancellation and renewal policy"]
    records["State, financial, notification, and audit records"]
    checks["Regression and permission checks"]
    outcome["Reliable operational outcome"]
    current --> policy --> records --> checks --> outcome
```

**Reading this:** The proposed work adds policy-backed outcomes around the existing core. It does not replace the current architecture. It first requires decisions, then records their consequences, then verifies that the role and studio boundaries still hold.
