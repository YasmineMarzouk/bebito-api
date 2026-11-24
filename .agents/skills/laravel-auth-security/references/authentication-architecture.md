# Authentication Architecture Reference

## Intended context model

The admin and customer applications are distinct trust zones, even if they share:
- one Laravel backend,
- one `users` table,
- one `User` model,
- a common identity provider,
- shared domain services.

Typical principals:
- Staff: `users.user_type = staff`
- Customer: `users.user_type = customer`

The user type is an identity classification, not a complete authorization system. Staff still need role/permission checks; customers still need ownership checks.

## Required boundaries

| Boundary | Admin context | Customer context |
|---|---|---|
| Login | Staff-only | Customer-only |
| Identity endpoint | Staff-only | Customer-only |
| Logout | Revokes admin context only | Revokes customer context only |
| Route middleware | Explicit admin guard/context | Explicit customer guard/context |
| Authorization | RBAC permission + policy | ownership + policy; optional customer permissions |
| Session/cookie | Independent | Independent |
| CSRF | Correct for admin origin/context | Correct for customer origin/context |
| Rate limiting | Admin-specific limits | Customer-specific limits |
| MFA | Required or approved risk | Policy based on risk |

## Existing RBAC schema to preserve

Unless explicitly approved otherwise, retain:
- `users` (`user_type` column)
- `roles`
- `user_role`
- `sections`
- `role_section`
- `permissions`
- `role_section_permission`

Do not infer a permission schema from names alone. Inspect relationships, pivot keys, scopes, soft deletes, and existing behavior. Document how a role maps to sections and permissions, and how multiple roles combine.

## Laravel concepts to inspect

- `config/auth.php`: guards, providers, defaults, password brokers
- User model: authenticatable traits, casts, relations, scopes, custom methods
- Sanctum: stateful domains, guard configuration, token abilities, middleware
- Route middleware: auth guard, user-type, permission, policy, ownership
- Session: driver, cookie, domain, path, lifetime, SameSite, encryption, secure
- Request identity: `$request->user()` and which guard it resolves
- Facades/helpers: `Auth::user()`, `Auth::guard()`, `auth()`
- Login: credential validation, user-type filter, session regeneration
- Logout: guard logout, session invalidation, cookie clearing, CSRF behavior
- Password reset and verification: broker/provider consistency, token scope, user type
- Frontend: credentialed fetch/axios, CSRF bootstrap, origin, API host

## Route inventory schema

Create a CSV/Markdown inventory with at least:

| Method | URI/name | Classification | Guard/context | User type | Permission/policy | Ownership | Mutates? | Sensitive? | Rate limit | Tests | Gap |
|---|---|---|---|---|---|---|---|---|---|---|

Classify every route, including nested resources, uploads, exports, webhooks, health endpoints, and routes registered dynamically. For shared routes, explain why sharing is safe and how the principal is selected.

## Authentication flow trace

For each frontend, trace:

1. Frontend origin and request credentials.
2. Cookie sent and accepted.
3. CSRF token bootstrap and validation.
4. Route middleware and guard selection.
5. Provider lookup and user-type enforcement.
6. Session/token creation and rotation.
7. Policy/permission/ownership checks.
8. Controller/service data access.
9. Response serialization and leakage risk.
10. Logout, expiration, and revocation.

## Design questions

- Are both frontends on same origin, sibling subdomains, or unrelated domains?
- Does the backend use Sanctum SPA cookie auth, bearer tokens, or both?
- Are sessions stored in database, Redis, files, or another driver?
- Is the API stateless for any routes?
- Do multiple backend instances share session storage and encryption keys?
- Are there multiple browser tabs, subdomains, or local development ports?
- Do admin and customer password reset flows share brokers or notification templates?
- Is staff MFA already available?
- Are external integrations/webhooks incorrectly routed through browser auth?

## Avoid these assumptions

- Different guard names automatically mean different session namespaces.
- Different cookie names automatically isolate session guards.
- Sanctum's stateful SPA mechanism automatically separates two apps.
- `Auth::user()` always resolves the intended principal.
- A `user_type` check in a controller protects all routes.
- CORS is authorization.
- Hiding an admin menu prevents admin API access.
- A valid login proves resource ownership.
