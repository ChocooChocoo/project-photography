# 11 - PARTS IN DETAIL

[[00 - START HERE|Back to start]] · Previous: [[10 - WORD LIST]] · Next: [[12 - SCREENS BY ROLE]]

## What this covers

These pages explain what each substantial part does, who may use it, what information it handles, what it checks, and where uncertainty remains. [[05 - SYSTEM ARCHITECTURE]] says where a part sits. [[07 - DEVELOPMENT ROADMAP]] says when it is built. These pages explain the part itself.

## The parts with a page

| # | The part | What it is for | Built by | Status | Page |
|---|---|---|---|---|---|
| P-01 | Accounts and access | Recognizes people and limits portal actions. | R-04, R-12 | 🔵 | [[PARTS/P-01 - ACCOUNTS AND ACCESS|P-01]] |
| P-02 | Studio onboarding and approval | Creates, reviews, and scopes studios. | R-09 | 🟨 | [[PARTS/P-02 - STUDIO ONBOARDING AND APPROVAL|P-02]] |
| P-03 | Marketplace, services, and discovery | Connects clients to provider offerings. | R-13, R-14 | 🟨 | [[PARTS/P-03 - MARKETPLACE SERVICES AND DISCOVERY|P-03]] |
| P-04 | Booking and payment | Records the shared client transaction. | R-02, R-05, R-08 | 🟨 | [[PARTS/P-04 - BOOKING AND PAYMENT|P-04]] |
| P-05 | Photographer assignment and cancellation | Assigns work and handles exceptional cancellation decisions. | R-06, R-20 | ❌ | [[PARTS/P-05 - PHOTOGRAPHER ASSIGNMENT AND CANCELLATION|P-05]] |
| P-06 | Galleries and reviews | Delivers and moderates photographic results. | R-07 | 🔵 | [[PARTS/P-06 - GALLERIES AND REVIEWS|P-06]] |
| P-07 | Studio people and permissions | Manages staff, roles, permissions, and studio scope. | R-09, R-12 | 🟨 | [[PARTS/P-07 - STUDIO PEOPLE AND PERMISSIONS|P-07]] |
| P-08 | Attendance, leave, overtime, and payroll | Supports staff administration and payroll setup. | R-10 | 🔵 | [[PARTS/P-08 - ATTENDANCE LEAVE OVERTIME AND PAYROLL|P-08]] |
| P-09 | Procurement and equipment | Moves purchasing work through controlled states. | R-11, R-15 | 🔵 | [[PARTS/P-09 - PROCUREMENT AND EQUIPMENT|P-09]] |
| P-10 | Subscriptions | Controls studio subscription state and access. | R-19 | 🟨 | [[PARTS/P-10 - SUBSCRIPTIONS|P-10]] |
| P-11 | Notifications | Carries operational messages and read state. | R-16 | 🔵 | [[PARTS/P-11 - NOTIFICATIONS|P-11]] |
| P-12 | AI assistant | Provides bounded photography help. | R-17 | 🔵 | [[PARTS/P-12 - AI ASSISTANT|P-12]] |

## What writing these turned up

Writing the part pages makes four gaps easy to miss in the broad analysis: the exact permission matrix for every cross-portal action, the transaction boundary between booking and room-independent payment states, the financial consequence of photographer cancellation, and the point at which subscription renewal becomes a new access decision. They remain Q-01, Q-02, Q-07, Q-09, and Q-11 in [[00 - START HERE#Open questions]].
