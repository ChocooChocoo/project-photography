# 11 - PARTS IN DETAIL

[Back to start](00%20-%20START%20HERE.md) · Previous: [10 - WORD LIST](10%20-%20WORD%20LIST.md) · Next: [12 - SCREENS BY ROLE](12%20-%20SCREENS%20BY%20ROLE.md)

## What this covers

These pages explain what each substantial part does, who may use it, what information it handles, what it checks, and where uncertainty remains. [05 - SYSTEM ARCHITECTURE](05%20-%20SYSTEM%20ARCHITECTURE.md) says where a part sits. [07 - DEVELOPMENT ROADMAP](07%20-%20DEVELOPMENT%20ROADMAP.md) says when it is built. These pages explain the part itself.

## The parts with a page

| # | The part | What it is for | Built by | Status | Page |
|---|---|---|---|---|---|
| P-01 | Accounts and access | Recognizes people and limits portal actions. | R-04, R-12 | 🔵 | [P-01](PARTS/P-01%20-%20ACCOUNTS%20AND%20ACCESS.md) |
| P-02 | Studio onboarding and approval | Creates, reviews, and scopes studios. | R-09 | 🟨 | [P-02](PARTS/P-02%20-%20STUDIO%20ONBOARDING%20AND%20APPROVAL.md) |
| P-03 | Marketplace, services, and discovery | Connects clients to provider offerings. | R-13, R-14 | 🟨 | [P-03](PARTS/P-03%20-%20MARKETPLACE%20SERVICES%20AND%20DISCOVERY.md) |
| P-04 | Booking and payment | Records the shared client transaction. | R-02, R-05, R-08 | 🟨 | [P-04](PARTS/P-04%20-%20BOOKING%20AND%20PAYMENT.md) |
| P-05 | Photographer assignment and cancellation | Assigns work and handles exceptional cancellation decisions. | R-06, R-20 | ✅ | [P-05](PARTS/P-05%20-%20PHOTOGRAPHER%20ASSIGNMENT%20AND%20CANCELLATION.md) |
| P-06 | Galleries and reviews | Delivers and moderates photographic results. | R-07 | 🔵 | [P-06](PARTS/P-06%20-%20GALLERIES%20AND%20REVIEWS.md) |
| P-07 | Studio people and permissions | Manages staff, roles, permissions, and studio scope. | R-09, R-12 | 🟨 | [P-07](PARTS/P-07%20-%20STUDIO%20PEOPLE%20AND%20PERMISSIONS.md) |
| P-08 | Attendance, leave, overtime, and payroll | Supports staff administration and payroll setup. | R-10 | 🔵 | [P-08](PARTS/P-08%20-%20ATTENDANCE%20LEAVE%20OVERTIME%20AND%20PAYROLL.md) |
| P-09 | Procurement and equipment | Moves purchasing work through controlled states. | R-11, R-15 | 🔵 | [P-09](PARTS/P-09%20-%20PROCUREMENT%20AND%20EQUIPMENT.md) |
| P-10 | Subscriptions | Controls studio subscription state and access. | R-19 | ✅ | [P-10](PARTS/P-10%20-%20SUBSCRIPTIONS.md) |
| P-11 | Notifications | Carries operational messages and read state. | R-16 | 🔵 | [P-11](PARTS/P-11%20-%20NOTIFICATIONS.md) |
| P-12 | AI assistant | Provides bounded photography help. | R-17 | 🔵 | [P-12](PARTS/P-12%20-%20AI%20ASSISTANT.md) |

## What writing these turned up

Writing the part pages makes four gaps easy to miss in the broad analysis: the exact permission matrix for every cross-portal action, the transaction boundary between booking and room-independent payment states, the financial consequence of photographer cancellation, and the point at which subscription renewal becomes a new access decision. They remain Q-01, Q-02, Q-07, Q-09, and Q-11 in [00 - START HERE](00%20-%20START%20HERE.md#open-questions).
