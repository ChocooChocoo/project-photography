# Objective

## Revise Seeder Data, Account Structure, Package Features, and Credential Documentation

---

## Role

You are a software developer responsible for maintaining and updating the application's database seeders and related documentation. Approach this objective with an emphasis on data consistency, uniqueness, role distribution, and preservation of existing seeded data that is not explicitly targeted for modification.

---

## Description

Revise the existing seeders so that every seeded user account has a completely distinct, randomly generated name and email address, eliminating similar names and email patterns that differ only by numbers or minor variations. Maintain the required credential conventions while enforcing the specified number of accounts for each user role and the required employee structure for each studio owner. Update all packages so that they include the Online Gallery feature while preserving existing seeded data that has not been explicitly requested for modification. Finally, document the actual seeded accounts and their access information in both Markdown and PDF formats.

---

## Primary Objective

Update the seeders to produce uniquely identifiable user accounts with the required role distribution, studio relationships, package features, and credential documentation while preserving all existing seeded data except for the specific data and rules identified for modification.

---

## Secondary Objectives

* Ensure every seeded user's name and email address is completely unique and does not use similar naming or email patterns.
* Ensure all seeded email addresses use the `gmail.com` domain.
* Keep the password for every seeded account as `Password_123`.
* Ensure every package includes the Online Gallery feature.
* Limit the seeded studio-owner structure to exactly five existing Studio Owners.
* Assign exactly one HR employee, one Finance employee, and five Photographers to each Studio Owner.
* Seed exactly one Admin and ten Clients.
* Document the actual credentials, studio relationships, roles or purposes, and accessible pages for all seeded accounts.
* Provide the account documentation in both Markdown and PDF formats.

---

## Success Criteria

* Exactly 5 Studio Owner accounts exist in the seeded user data.
* Exactly 5 HR employee accounts exist, with one assigned to each Studio Owner.
* Exactly 5 Finance employee accounts exist, with one assigned to each Studio Owner.
* Exactly 25 Photographer accounts exist, with five assigned to each Studio Owner.
* Exactly 1 Admin account exists.
* Exactly 10 Client accounts exist.
* Every seeded account has a distinct name and unique email address without similar names or emails differentiated only by numbers or minor variations.
* Every seeded email address uses the `gmail.com` domain.
* Every seeded account uses `Password_123` as its password.
* Every package includes the Online Gallery feature.
* A Markdown (`.md`) document containing the actual seeded account information is created inside the `docs` folder.
* A PDF version containing the same account information is also created.
* The documentation includes each account's actual seeded name, email address, password, linked studio, purpose or role, and accessible pages.
* Existing seeded data that is not explicitly targeted for modification remains intact.

---

## Constraints

* Do not change the password convention; all seeded accounts must continue using `Password_123`.
* All seeded email addresses must use the `gmail.com` domain.
* Preserve existing seeded data in tables such as Services, Packages, Notifications, and Locations except for modifications explicitly required by this objective.
* For Packages, modify only what is necessary to ensure that every package includes the Online Gallery feature.
* Only replace or modify seeded data and seeder rules explicitly identified in this objective.

---

## Out of Scope

* Do not remove, replace, or unnecessarily modify existing seeded Services.
* Do not remove or unnecessarily replace existing seeded Packages beyond ensuring that all packages include Online Gallery.
* Do not remove, replace, or unnecessarily modify existing seeded Notifications.
* Do not remove, replace, or unnecessarily modify existing seeded Locations.
* Do not alter other existing seeder data or rules unless required to satisfy the explicitly stated changes.

---

## Context & Dependencies

* The application already contains seeded data, including Services, Packages, Notifications, and Locations.
* Existing user seed data currently contains accounts with similar names and email addresses, including email patterns differentiated primarily by numbers.
* Studio employees must be associated with the appropriate Studio Owner or studio.
* Account documentation must reflect the actual credentials and relationships produced by the revised seeders rather than placeholder or example credentials.

---

## Supporting Tasks

### User Account Uniqueness

* Revise the user seeder so that every account receives a completely distinct name.
* Generate a unique email address for every account without relying on numbered variations of otherwise similar email addresses.
* Ensure every email address ends with `@gmail.com`.
* Keep `Password_123` as the password for every seeded account.
* Ensure the credentials documented later exactly match the accounts created by the seeders.

### Studio and Employee Distribution

* Seed exactly five Studio Owners.
* Assign exactly one HR employee to each Studio Owner, producing five HR employees in total.
* Assign exactly one Finance employee to each Studio Owner, producing five Finance employees in total.
* Assign exactly five Photographers to each Studio Owner, producing twenty-five Photographers in total.
* Maintain the correct studio relationship for each employee.

### Admin and Client Accounts

* Seed exactly one Admin account.
* Seed exactly ten Client accounts.
* Ensure these accounts also have completely distinct names and unique Gmail addresses.

### Package Updates

* Update the package seeding rules so that every seeded package includes the Online Gallery feature.
* Preserve the remainder of the existing package seed data unless a change is necessary to satisfy the Online Gallery requirement.

### Account Documentation

* Create a Markdown (`.md`) file inside the `docs` folder containing all actual seeded accounts.
* For every documented account, include its seeded name, email address, password, linked studio, purpose or role, and the pages that account can access.
* Ensure the documented credentials exactly match the credentials generated by the revised seeders.
* Create a PDF version containing the same account information as the Markdown document.

### Existing Seeder Preservation

* Retain existing seeded data for Services, Packages, Notifications, Locations, and other unaffected seeders.
* Modify only the seeded data or rules explicitly identified in this objective.

---

## Detailed Breakdown

### Required Account Distribution

The final seeded account structure must contain exactly five Studio Owners, five HR employees, five Finance employees, twenty-five Photographers, one Admin, and ten Clients. Each of the five Studio Owners must be associated with exactly one HR employee, one Finance employee, and five Photographers.

### Credential Uniqueness

Every account must have a clearly distinct identity. Names and email addresses must not be reused or generated as minor variations of one another, such as identical base names with different numeric suffixes. Every email address must remain within the `gmail.com` domain, while every account password must remain `Password_123`.

### Package Feature Requirement

All existing seeded packages must include the Online Gallery feature. Other package data should remain unchanged unless modification is directly necessary to satisfy this requirement.

### Credential and Access Documentation

The Markdown document in the `docs` folder must serve as an accurate reference for the actual seeded accounts. It must identify each account's name, email address, password, associated studio, role or purpose, and accessible pages. A PDF containing the same information must also be produced.

### Preservation of Existing Seed Data

Existing seeded records, including Services, Packages, Notifications, and Locations, must be retained. Changes should be limited to the user/account rules, required role counts and studio assignments, the Online Gallery package requirement, and the requested account documentation.
