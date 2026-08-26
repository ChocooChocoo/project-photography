# Objective
## Resolve Booking Payment, Subscription, and Studio Creation Issues

---

## Role
You are a software engineer responsible for diagnosing and resolving application defects. Approach this objective with a focus on payment accuracy, subscription state management, and reliable studio creation workflows.

---

## Description
Resolve three issues affecting booking payments, subscription cancellations, and studio creation. Ensure that the booking summary accurately reflects the selected down-payment option and applies the correct refund behavior for customer and provider cancellations. Ensure canceled subscriptions are no longer treated as active and do not prevent users from subscribing to another plan. Fix the Barangay field error in the Studio Creation form so that barangays load correctly under Location Information.

---

## Primary Objective
Fix the booking payment and refund logic, subscription cancellation status, and Barangay field loading error.

---

## Secondary Objectives
- Ensure the booking summary displays the correct payment option selected by the customer.
- Apply the appropriate refund rules based on the payment method and the party that cancels.
- Update subscription status correctly after cancellation.
- Restore barangay loading functionality during studio creation.

---

## Success Criteria
- Selecting full payment displays full payment in the booking summary instead of a 30% down payment.
- Customers who cancel under the 30% down-payment option receive no refund.
- Customers who cancel after making full payment receive the applicable percentage refund.
- Customers receive a full refund when the studio or photographer cancels.
- Canceled subscriptions no longer appear as active.
- Users can subscribe to another plan after canceling an existing subscription.
- The Barangay field loads successfully without displaying “Error loading barangays.”

---

## Supporting Tasks

### Booking Payments and Refunds
- Correct the booking summary so it reflects whether the customer selected a 30% down payment or full payment.
- Apply the no-refund rule when a customer cancels under the 30% down-payment option.
- Apply the specified partial refund when a customer cancels after making full payment.
- Issue a full refund when the studio or photographer cancels.

### Subscription Management
- Update the subscription state to canceled immediately after cancellation.
- Prevent canceled subscriptions from being treated as active.
- Allow users to subscribe to another plan after canceling a previous subscription.

### Studio Creation
- Fix the Barangay field under the Location Information section so barangay data loads correctly.
- Remove the “Error loading barangays.” failure during studio creation.