# Planned Bootstrap Landing Page

> **In plain terms:** This document describes a possible future public first page. The website still opens on the existing login flow because no landing-page implementation is approved.

**Status:** Planned documentation only. This is not an implemented feature.

## Current state

The unauthenticated root route currently redirects visitors to the existing login page at `/auth/login`. No route, controller, view, stylesheet, script, configuration, authentication behavior, or test was changed for this plan.

## Future public entry page

After a separate implementation task is approved, the landing page should become the public interface at `/`. It will introduce the Platinum Studio Platform before authentication and must preserve the existing login flow, registration flow, protected routes, and role-based dashboard redirects.

The page navigation and calls to action should use the existing public destinations:

- Login: `/auth/login`
- Registration: `/auth/register`

## Planned page structure

The selected Bootstrap template should provide one consistent responsive page containing:

1. A navigation bar with brand identity, section links, and login/registration actions.
2. A hero section that explains the platform and directs visitors to login or registration.
3. An about section describing the platform's photography-studio purpose.
4. A services section that presents existing platform capabilities without inventing new products or workflows.
5. A testimonials section using approved content available at implementation time.
6. Supporting calls to action that lead to the existing login or registration routes.
7. A footer with the template's appropriate navigation and contact or policy links.

## Bootstrap and responsive requirements

Bootstrap is the primary future framework for layout, components, responsive behavior, spacing, typography, navigation, cards, buttons, containers, rows, columns, and utilities. The page must remain usable on desktop, tablet, and mobile breakpoints through Bootstrap's responsive classes.

Custom CSS is permitted only when a necessary template-consistent refinement cannot be achieved with Bootstrap. Any such CSS must be minimal, organized, maintainable, and extend rather than conflict with the selected template.

## Future implementation and verification

A future implementation must first replace the unauthenticated root redirect with the landing-page view while retaining `/auth/login` and `/auth/register` unchanged. It must verify public navigation, both authentication calls to action, responsive layouts, authenticated dashboard redirects, protected-route behavior, accessibility basics, and absence of regressions in existing login and registration flows.

No landing-page code has been added, and no system testing has been performed by this documentation-only task.
