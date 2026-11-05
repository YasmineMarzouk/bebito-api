# Task: Refactor Laravel Authentication into Strictly Isolated Admin and Customer Authentication Contexts

## Role

Act as a senior Laravel security engineer specializing in authentication architecture, session security, authorization, and automated security testing.

Your task is to audit, refactor, and secure the existing Laravel API authentication system so that the Admin Dashboard and Customer Store have completely independent authentication contexts.

This is a security-sensitive task. Do not implement a superficial solution based only on renaming cookies or adding authentication guards.

You must inspect the existing application, understand its authentication and authorization architecture, implement the changes, and create a comprehensive automated test suite.

---

# 1. Existing Application Context

The project contains:

- One Laravel API.
- One Admin Dashboard frontend.
- One Customer Store frontend.
- Both frontends authenticate against the same Laravel API.
- Authentication currently uses Laravel Sanctum, cookies, and sessions.
- Both applications currently authenticate using the `web` guard.
- Logging in through the Admin Dashboard currently causes the Customer Store to become logged out, or otherwise produces authentication conflicts.

The database already contains the following authorization schema:

- `users`
    - Contains both staff and customers.
    - Includes a `user_type` column with values such as `staff` and `customer`.
- `roles`
- `user_role`
- `sections`
- `role_section`
- `permissions`
- `role_section_permission`

Existing roles, sections, permissions, and relationships must be preserved.

Do not introduce a new authorization system or replace the existing RBAC implementation unless a concrete technical limitation makes it necessary.

Do not create separate admin/customer user tables unless explicitly justified and approved.

The objective is to retain the existing users and RBAC database while establishing separate authentication and authorization boundaries.

---

# 2. Mandatory Initial Audit

Before modifying any code, inspect the project and identify:

1. Laravel version.
2. PHP version and supported dependencies.
3. Sanctum version and current configuration.
4. Current authentication guards and providers.
5. Current `config/auth.php`.
6. Current `config/sanctum.php`.
7. Current `config/session.php`.
8. Session driver and session storage configuration.
9. Authentication middleware.
10. Login/logout controllers and services.
11. Authentication routes.
12. Admin API route groups.
13. Customer API route groups.
14. Password reset and account recovery flows.
15. User model and authentication-related traits.
16. User model relationships to roles.
17. Existing authorization middleware, policies, gates, and permission services.
18. Existing frontend authentication contracts.
19. Current cookie names, domains, paths, SameSite, Secure, and HttpOnly settings.
20. CORS configuration and trusted frontend origins.
21. Existing tests and test conventions.
22. Existing authentication-related packages.
23. Existing session invalidation and regeneration behavior.
24. Existing remember-me functionality.
25. Any shared authentication helpers using `Auth::user()`, `auth()`, `Auth::guard('web')`, or `request()->user()`.

Search the entire repository for authentication and authorization references.

Use the actual discovered architecture rather than assuming Laravel defaults.

Document the root cause of the current authentication conflict.

Do not start implementing until the audit has identified the actual session and guard collision mechanisms.

---

# 3. Target Authentication Architecture

Implement two independent authentication contexts.

## Admin authentication context

- Guard: `admin`
- Provider: existing `users` table/provider, restricted to staff accounts.
- Authentication middleware: dedicated admin authentication middleware.
- Login endpoint: dedicated admin login.
- Logout endpoint: dedicated admin logout.
- Authentication state: independent from customer authentication.
- Session cookie: dedicated admin cookie.
- Session storage: independent admin session namespace or store.
- Authorization: existing RBAC system, restricted to staff users.

## Customer authentication context

- Guard: `customer`
- Provider: existing `users` table/provider, restricted to customer accounts.
- Authentication middleware: dedicated customer authentication middleware.
- Login endpoint: dedicated customer login.
- Logout endpoint: dedicated customer logout.
- Authentication state: independent from admin authentication.
- Session cookie: dedicated customer cookie.
- Session storage: independent customer session namespace or store.
- Authorization: customer ownership policies and customer-specific permissions.

Both guards may use the same underlying User model and database table, but must authenticate only their permitted user types.

## Critical isolation requirement

Admin and customer authentication must never interfere with one another.

The following must all be possible simultaneously:

- An admin is authenticated in the Admin Dashboard.
- A customer is authenticated in the Customer Store.
- Both sessions exist in the same browser.
- Both sessions remain authenticated after login to the other application.
- Logging out from one application does not log out the other.
- Session regeneration in one authentication context does not invalidate the other.
- Session expiration in one context does not affect the other.
- Password changes and account revocation follow explicit context-specific policies.
- Neither guard can authenticate a user of the wrong user type.
- Neither application can use the other's authentication context to access protected routes.

---

# 4. Separate Authentication Guards

Implement dedicated guards in `config/auth.php`.

Expected conceptual configuration:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'admin' => [
        'driver' => 'session',
        'provider' => 'admins',
    ],

    'customer' => [
        'driver' => 'session',
        'provider' => 'customers',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],

    'admins' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],

    'customers' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
```

This is an architectural illustration, not necessarily the final implementation.

Implement appropriate user-type restrictions for the admin and customer providers.

A staff account must never authenticate through the customer guard.

A customer account must never authenticate through the admin guard.

Do not rely exclusively on frontend restrictions or request parameters to enforce this.

Do not allow a guard to authenticate a user merely because the credentials are valid.

Inspect and correctly implement Laravel guard/provider behavior.

## Explicit guard usage

All admin authentication and authorization must use the admin context explicitly.

All customer authentication and authorization must use the customer context explicitly.

Avoid accidental use of the default `web` guard.

Audit and eliminate inappropriate uses of:

```php
Auth::user();
auth()->user();
Auth::guard('web')->user();
$request->user();
```

Replace these with explicitly selected authentication contexts where required.

Be careful: `$request->user()` and default `auth()` behavior can depend on the middleware and configured default guard.

Do not blindly replace every occurrence. Determine the correct context for each route, service, and operation.

---

# 5. Session Isolation — Critical

Separate cookie names alone are insufficient.

The implementation must provide independent authentication session state.

## Required properties

1. Admin authentication state is stored independently.
2. Customer authentication state is stored independently.
3. Admin login does not overwrite customer authentication state.
4. Customer login does not overwrite admin authentication state.
5. Admin logout does not invalidate customer authentication.
6. Customer logout does not invalidate admin authentication.
7. Session regeneration in one context does not invalidate the other.
8. Session expiration is independently enforced.
9. Session revocation is independently supported.
10. Session fixation protection remains enabled.
11. Authentication cookies are independently managed.
12. Session storage is protected from unauthorized access.

## Implementation requirements

Investigate whether the existing Laravel session mechanism can safely support the desired isolation.

If standard Laravel session guards share the same underlying session and cannot provide the required independent authentication state, implement an appropriate isolated session architecture.

Evaluate the following approaches:

- Separate session middleware and cookie configuration.
- Separate session namespaces.
- Separate session stores.
- Dedicated authentication services.
- Separate Laravel session contexts.

Choose the simplest secure architecture that satisfies every isolation requirement.

Do not introduce unnecessary complexity.

Do not use a custom authentication implementation that bypasses Laravel's session security protections without a compelling reason.

Do not disable session regeneration, CSRF protection, or session invalidation to make the two contexts work.

Do not use the same session identifier for both authentication contexts.

Do not store raw session IDs in logs or frontend storage.

## Session configuration

Implement explicit configuration for:

- Admin session cookie name.
- Customer session cookie name.
- Session domain.
- Cookie path.
- Secure.
- HttpOnly.
- SameSite.
- Idle timeout.
- Absolute session lifetime.
- Session storage.
- Session revocation.

Prefer host-only cookies where feasible.

If frontend domains require parent-domain cookies, justify the configuration and assess the security implications.

The configuration must work in development, testing, staging, and production.

---

# 6. Separate Login and Logout Flows

Implement dedicated authentication services/controllers/routes for each context.

Expected conceptual endpoints:

```text
POST /api/admin/auth/login
POST /api/admin/auth/logout
GET  /api/admin/auth/me

POST /api/customer/auth/login
POST /api/customer/auth/logout
GET  /api/customer/auth/me
```

Adapt these paths to the existing API conventions.

Do not create duplicate business logic unnecessarily.

Shared internal services may be used if the authentication context is explicitly supplied and validated.

## Login requirements

- Validate credentials.
- Validate the account's permitted user type.
- Apply rate limiting.
- Prevent account enumeration.
- Regenerate the appropriate session identifier after login.
- Preserve session fixation protection.
- Establish authentication only in the intended context.
- Never overwrite the other context.
- Return only safe user information.
- Never expose password hashes or internal authentication data.
- Do not return session IDs in JSON.
- Handle disabled accounts.
- Handle account status restrictions.
- Preserve existing login response contracts where possible.

## Logout requirements

- Logout only from the intended guard.
- Invalidate only the intended authentication context.
- Clear only the intended authentication cookie.
- Do not invalidate the other application's session.
- Do not clear shared session data belonging to the other authentication context.
- Protect logout against CSRF.
- Reject unsafe GET logout operations.
- Ensure revoked sessions cannot be reused.

Do not call a shared session invalidation operation that destroys both authentication contexts.

---

# 7. Strictly Separated Authorization Policies

Preserve the existing RBAC database.

## Admin authorization

Use the existing relationships:

```text
users
  |
user_role
  |
roles
  |
role_section
  |
sections
  |
role_section_permission
  |
permissions
```

Inspect the existing implementation and identify how permissions are resolved.

Implement explicit admin authorization using the admin authentication context.

Requirements:

- Admin routes require admin authentication.
- Admin accounts must have `user_type = staff`.
- Admin roles and permissions are evaluated server-side.
- Staff without required permission cannot access privileged endpoints.
- Customers cannot access admin endpoints.
- Customers cannot obtain admin permissions through request parameters.
- Customers cannot assign themselves staff roles.
- Role changes and permission changes are protected.
- Permission checks must not rely on frontend state.
- Admin authorization must not accidentally use the customer guard.
- Existing permission semantics must be preserved.

Do not silently change existing permission behavior.

## Customer authorization

Requirements:

- Customer routes require customer authentication where applicable.
- Customer accounts must have `user_type = customer`.
- Customers can access only their own resources.
- Customer ownership is checked server-side.
- Customers cannot access staff management.
- Customers cannot access admin-only permissions.
- Customer account data cannot be retrieved using another customer's ID.
- Customer ownership checks must be enforced in policies or equivalent server-side authorization.
- Customer authorization must not accidentally use the admin guard.

## Authorization audit

Search all routes and controllers for:

- Missing authentication middleware.
- Missing authorization middleware.
- Default guard usage.
- Incorrect user type assumptions.
- IDOR/BOLA vulnerabilities.
- Mass assignment.
- Direct role or permission assignment.
- Unprotected resource access.

Fix identified vulnerabilities within the task scope.

---

# 8. CSRF, CORS, and Cookie Security

Preserve and correctly implement cookie-based authentication security.

Requirements:

- CSRF protection for state-changing requests.
- Login-CSRF protection.
- Logout-CSRF protection.
- Appropriate CSRF token initialization.
- CSRF token validation.
- Separate authentication context handling.
- Explicit allowed origins.
- Credentialed CORS only for approved origins.
- Never combine wildcard origins with credentials.
- Correct cookie Secure attribute.
- Correct cookie HttpOnly attribute.
- Appropriate SameSite policy.
- HTTPS enforcement in production.
- HSTS in production.
- No authentication cookies accessible through JavaScript.
- No authentication secrets in localStorage/sessionStorage.
- No authentication tokens or session IDs in URLs.
- No secrets in API responses or logs.

Do not disable CSRF protection to fix authentication conflicts.

Do not weaken CORS to make login work.

---

# 9. Password, Recovery, and Session Revocation

Audit and secure:

- Password hashing.
- Login throttling.
- Password reset.
- Email verification.
- Account recovery.
- Password change.
- Email change.
- Account disablement.
- Admin MFA.
- Session revocation.
- Remember-me behavior.

Requirements:

- Password reset tokens are single-use and expire.
- Admin and customer account recovery cannot cross contexts.
- Disabled accounts cannot authenticate.
- Password changes follow a documented session revocation policy.
- Admin account recovery is protected.
- Admin MFA is enforced if supported by the existing project or required by the security design.
- Authentication failures do not disclose whether an account exists.
- Rate limits are enforced server-side.

Do not silently introduce a new MFA dependency or change an existing account recovery contract without documenting the implementation.

---

# 10. Automated Test Suite

Implement the tests, not merely a test plan.

Use the existing testing framework and conventions.

Prefer PHPUnit feature tests for backend authentication and authorization.

Use unit tests for isolated services, guard/provider behavior, permission resolution, and session isolation components.

Use Laravel HTTP tests for middleware and route behavior.

Use real browser E2E tests if the project already supports Playwright or Cypress.

Do not claim that backend unit tests alone prove browser cookie isolation.

## Test naming

Use descriptive test names and group tests logically.

Suggested suites:

```text
tests/
  Unit/
    Authentication/
      AdminAuthenticationServiceTest.php
      CustomerAuthenticationServiceTest.php
      AuthenticationContextIsolationTest.php
      AdminUserProviderTest.php
      CustomerUserProviderTest.php
      SessionIsolationTest.php
      SessionRevocationTest.php
    Authorization/
      AdminPermissionResolverTest.php
      AdminPolicyTest.php
      CustomerPolicyTest.php
      CustomerOwnershipTest.php

  Feature/
    Authentication/
      AdminLoginTest.php
      AdminLogoutTest.php
      AdminSessionTest.php
      CustomerLoginTest.php
      CustomerLogoutTest.php
      CustomerSessionTest.php
      AuthenticationIsolationTest.php
      AuthenticationGuardIsolationTest.php
      PasswordResetIsolationTest.php
      AccountStatusAuthenticationTest.php
      CsrfAuthenticationTest.php

    Authorization/
      AdminAuthorizationTest.php
      CustomerAuthorizationTest.php
      RolePermissionTest.php
      ResourceOwnershipTest.php
      AdminCustomerBoundaryTest.php

  Browser/
    Authentication/
      AdminCustomerIsolation.spec.ts
```

Adapt the structure to the existing repository rather than creating redundant test conventions.

---

# 11. Required Authentication Isolation Tests

Implement the following test scenarios.

| ID | Scenario | Expected |
|---|---|---|
| AUTH-01 | Admin login | Admin authenticated |
| AUTH-02 | Customer login | Customer authenticated |
| AUTH-03 | Admin login while customer authenticated | Customer remains authenticated |
| AUTH-04 | Customer login while admin authenticated | Admin remains authenticated |
| AUTH-05 | Admin logout | Customer remains authenticated |
| AUTH-06 | Customer logout | Admin remains authenticated |
| AUTH-07 | Admin session expires | Customer session unaffected |
| AUTH-08 | Customer session expires | Admin session unaffected |
| AUTH-09 | Admin login with customer credentials | Rejected |
| AUTH-10 | Customer login with admin credentials | Rejected |
| AUTH-11 | Admin session used against customer endpoint | Rejected |
| AUTH-12 | Customer session used against admin endpoint | Rejected |
| AUTH-13 | Both sessions active simultaneously | Both remain valid |
| AUTH-14 | Both users refresh their sessions | Both remain valid |
| AUTH-15 | Admin login from another browser | Defined concurrency policy enforced |
| AUTH-16 | Customer login from another browser | Defined concurrency policy enforced |
| AUTH-17 | Admin password changed | Admin sessions follow revocation policy |
| AUTH-18 | Customer password changed | Customer sessions follow revocation policy |
| AUTH-19 | Admin account disabled | Admin authentication denied |
| AUTH-20 | Customer account disabled | Customer authentication denied |

AUTH-03 through AUTH-06 are mandatory regression tests.

Do not simulate these scenarios using only mocked guards.

Use actual configured guards, middleware, session mechanisms, and authentication services.

---

# 12. Required Login Tests

Implement tests for:

- Valid admin credentials.
- Valid customer credentials.
- Invalid password.
- Invalid email.
- Unknown account.
- Empty credentials.
- Missing password.
- Malformed email.
- SQL injection payload.
- Extremely long email/password.
- Unicode input.
- Case sensitivity rules.
- Disabled admin.
- Disabled customer.
- Unverified customer.
- Admin account attempting customer login.
- Customer account attempting admin login.
- Login rate limit.
- Repeated login failures.
- Login session regeneration.
- Session fixation attempt.
- Remember-me behavior.
- Login response does not expose sensitive fields.

---

# 13. Required Logout Tests

Implement tests for:

- Admin logout clears admin authentication.
- Customer logout clears customer authentication.
- Admin logout does not clear customer authentication.
- Customer logout does not clear admin authentication.
- Logout without authentication.
- Logout with expired session.
- Logout with invalid CSRF token.
- Logout using GET is rejected.
- Logout invalidates the server-side session as designed.
- Logout clears remember-me cookie if applicable.
- Reuse of logged-out session is rejected.
- Logout response does not expose session details.

---

# 14. Required Session Tests

Implement tests for:

- Session identifier changes after login.
- Session identifier changes after privilege elevation.
- Session fixation is prevented.
- Expired session is rejected.
- Idle timeout works.
- Absolute timeout works.
- Session revocation works.
- Invalid session cookie rejected.
- Tampered session cookie rejected.
- Session regeneration does not affect the other context.
- Admin session renewal does not affect customer session.
- Customer session renewal does not affect admin session.
- Session storage isolation works.
- Session cleanup works.
- Session data cannot be read across contexts.

Where Laravel's test environment cannot realistically reproduce browser cookie behavior, implement the appropriate integration or browser test instead.

---

# 15. Required CSRF Tests

Implement tests for:

- Missing CSRF token.
- Invalid CSRF token.
- Expired CSRF token.
- Valid CSRF token.
- Login-CSRF attempt.
- Logout-CSRF attempt.
- Cross-origin POST.
- Cross-origin PUT.
- Cross-origin DELETE.
- Cross-origin checkout.
- Cross-origin password change.
- Cross-origin address modification.
- CSRF token from another session.
- Admin CSRF token used in customer context.
- Customer CSRF token used in admin context.

Ensure the tests execute the real CSRF middleware where possible.

Do not use test helpers that silently disable middleware for security-sensitive scenarios.

---

# 16. Required Authorization Tests

Implement tests for:

- Anonymous user accesses protected endpoint.
- Customer accesses admin endpoint.
- Admin accesses customer-only endpoint.
- Admin without permission accesses privileged endpoint.
- Admin with permission accesses privileged endpoint.
- Admin attempts role escalation.
- Admin attempts unauthorized permission modification.
- Customer modifies another customer's profile.
- Customer reads another customer's order.
- Customer modifies another customer's address.
- Customer cancels another customer's order.
- Customer accesses another customer's invoice.
- Customer accesses private file belonging to another customer.
- Customer changes order ownership.
- Customer supplies another `customer_id`.
- Customer supplies another `user_id`.
- Customer modifies protected fields.
- Unauthorized requests return appropriate errors.
- Resource authorization remains enforced regardless of frontend.

Use existing RBAC fixtures/factories.

Ensure tests verify actual role-section-permission relationships rather than mocking permission resolution.

---

# 17. Required Password and Account Recovery Tests

Implement tests for:

- Password reset request for existing account.
- Password reset request for nonexistent account.
- Password reset token expiration.
- Password reset token reuse.
- Password reset token tampering.
- Password reset for admin.
- Password reset for customer.
- Customer reset token used for admin.
- Admin reset token used for customer.
- Password reset revokes relevant sessions.
- Password change requires current password.
- Email change requires verification.
- MFA enrollment.
- MFA verification.
- MFA brute-force protection.
- MFA recovery.
- MFA removal requires authorization.
- MFA cannot be bypassed.

If a feature is not currently implemented, clearly identify whether it is a security gap, implement it when within scope, or document it as an unresolved requirement.

---

# 18. Required Browser E2E Tests

If Playwright is available, implement browser tests using a real browser context shared between both frontend applications.

Use actual deployed or locally running frontend applications and API.

Required scenarios:

| ID | Scenario | Expected |
|---|---|---|
| E2E-01 | Login customer | Customer authenticated |
| E2E-02 | Open admin in another tab | Customer remains authenticated |
| E2E-03 | Login admin | Customer remains authenticated |
| E2E-04 | Refresh customer page | Customer remains authenticated |
| E2E-05 | Refresh admin page | Admin remains authenticated |
| E2E-06 | Logout admin | Customer remains authenticated |
| E2E-07 | Logout customer | Admin remains authenticated |
| E2E-08 | Admin session expires | Customer remains authenticated |
| E2E-09 | Customer session expires | Admin remains authenticated |
| E2E-10 | Login another admin | Expected admin concurrency policy |
| E2E-11 | Login another customer | Expected customer concurrency policy |
| E2E-12 | Close and reopen browser | Expected persistent/session behavior |
| E2E-13 | Open admin and store in separate tabs | Both remain authenticated |
| E2E-14 | Open store in another tab | Customer state remains consistent |
| E2E-15 | Admin login while checkout is open | Checkout remains correctly authenticated |
| E2E-16 | Customer logout during checkout | Checkout requires authentication if applicable |
| E2E-17 | Admin logout while customer is placing order | Customer order flow unaffected |
| E2E-18 | Customer login while admin modifies an order | Admin session unaffected |

## Browser cookie tests

Verify:

- Admin session cookie name is correct.
- Customer session cookie name is correct.
- Cookies do not overwrite each other.
- Cookies have correct domain.
- Cookies have correct path.
- Cookies have Secure.
- Session cookies have HttpOnly.
- SameSite is correct.
- No duplicate session cookies with conflicting scopes.
- No unintended cookies are sent to admin.
- No unintended cookies are sent to customer store.
- Session cookies are not accessible through JavaScript.
- Cookies are cleared appropriately after logout.
- Cookie behavior is correct after session regeneration.
- Cookie behavior is correct after expiration.
- Authentication works across refreshes.
- Authentication works across tabs.
- Authentication works with browser back/forward navigation.

---

# 19. Required Ecommerce Security Tests

Implement tests for:

| ID | Scenario | Expected |
|---|---|---|
| ORDER-01 | Customer creates order | Order belongs to authenticated customer |
| ORDER-02 | Customer submits another customer ID | Ignored/rejected |
| ORDER-03 | Customer modifies another order | Rejected |
| ORDER-04 | Customer reads another order | Rejected |
| ORDER-05 | Customer cancels another order | Rejected |
| ORDER-06 | Customer modifies order price | Rejected |
| ORDER-07 | Customer modifies discount | Rejected |
| ORDER-08 | Customer modifies stock quantity | Rejected |
| ORDER-09 | Customer submits duplicate checkout | No unintended duplicate order |
| ORDER-10 | Customer logs out during checkout | Defined secure behavior |
| ORDER-11 | Customer session expires during checkout | Reauthentication required |
| ORDER-12 | Admin logs out during customer checkout | Customer checkout unaffected |
| ORDER-13 | Admin modifies order | Only authorized admins |
| ORDER-14 | Admin changes order ownership | Restricted and audited |
| ORDER-15 | Customer accesses invoice URL | Ownership enforced |
| ORDER-16 | Customer accesses private order file | Ownership enforced |
| ORDER-17 | Payment webhook replay | No duplicate payment/order processing |
| ORDER-18 | Payment callback without verification | Rejected |
| ORDER-19 | Customer changes payment status | Rejected |
| ORDER-20 | Customer accesses admin order API | Rejected |

Reuse existing order/payment logic and factories.

Do not unnecessarily refactor unrelated business logic.

---

# 20. Security Test Implementation Requirements

The tests must:

- Use real application routes.
- Use real configured authentication guards.
- Use real middleware.
- Use actual authorization logic.
- Use realistic staff/customer fixtures.
- Use realistic roles, sections, and permissions.
- Verify actual HTTP responses.
- Verify actual authenticated identities.
- Verify session state.
- Verify that one authentication context remains valid while the other changes.
- Verify cookies when supported.
- Verify that authentication failures do not expose sensitive information.
- Verify that unauthorized actions do not modify database state.
- Verify that session revocation actually prevents subsequent access.

Avoid:

- Mocking the authentication guard in isolation for integration scenarios.
- Disabling middleware globally.
- Using `actingAs()` as the only proof of session isolation.
- Testing only successful login.
- Testing only HTTP response codes without verifying state changes.
- Using fixtures that bypass actual RBAC relationships.
- Claiming browser cookie isolation from PHPUnit tests alone.

---

# 21. Security Engineering Requirements

Throughout the implementation:

- Follow Laravel security best practices.
- Follow OWASP ASVS principles.
- Preserve existing application functionality.
- Use secure defaults.
- Avoid custom cryptography.
- Avoid custom session implementations unless necessary.
- Never store plaintext passwords.
- Never expose session IDs.
- Never log credentials.
- Never disable CSRF.
- Never weaken CORS.
- Never rely on frontend authorization.
- Never rely on hidden UI elements for security.
- Never trust user_type, user_id, role_id, permission_id, or customer_id supplied by the frontend.
- Never permit unauthorized mass assignment.
- Never silently ignore authorization failures.
- Avoid introducing unnecessary dependencies.
- Avoid breaking existing API contracts without documenting migration requirements.

If an implementation choice has significant security tradeoffs, document them.

If an architectural decision cannot be safely implemented without additional information, explain the blocker instead of guessing.

---

# 22. Execution Workflow

Execute the task in the following order.

## Phase 1 — Audit

- Inspect the project.
- Identify the current authentication conflict.
- Document the current architecture.
- Identify affected files.
- Identify existing tests.
- Identify security gaps.

## Phase 2 — Design

- Define the two authentication contexts.
- Define the guard/provider architecture.
- Define session isolation.
- Define cookie configuration.
- Define route boundaries.
- Define authorization boundaries.
- Define compatibility requirements.

## Phase 3 — Implementation

- Implement admin authentication context.
- Implement customer authentication context.
- Implement isolated session handling.
- Implement separate login/logout flows.
- Implement user-type restrictions.
- Implement explicit authentication middleware.
- Implement authorization boundaries.
- Update frontend-facing authentication contracts.
- Preserve existing business logic.

## Phase 4 — Automated tests

- Implement unit tests.
- Implement feature tests.
- Implement authentication isolation tests.
- Implement authorization tests.
- Implement session tests.
- Implement CSRF tests.
- Implement recovery tests.
- Implement ecommerce security tests.
- Implement browser E2E tests if supported.

## Phase 5 — Verification

Run:

- Existing test suite.
- New authentication tests.
- New authorization tests.
- Static analysis if available.
- Code formatting checks.
- Dependency security audit if available.
- Browser E2E tests if configured.

Do not claim tests passed unless they were actually executed.

## Phase 6 — Security review

Review the implementation for:

- Guard confusion.
- Session collisions.
- Cookie collisions.
- Session fixation.
- CSRF.
- IDOR/BOLA.
- Privilege escalation.
- Authentication bypass.
- Account recovery vulnerabilities.
- Unauthorized role/permission assignment.
- Sensitive information exposure.

---

# 23. Deliverables

At completion, provide:

## A. Architecture report

- Root cause of the original authentication conflict.
- Existing authentication architecture.
- New authentication architecture.
- Guard/provider design.
- Session isolation design.
- Cookie configuration.
- Admin/customer authorization boundaries.
- Security tradeoffs.

## B. Implementation report

List:

- Files created.
- Files modified.
- Middleware added.
- Guards/providers configured.
- Authentication services/controllers changed.
- Routes changed.
- Policies/middleware changed.
- Session configuration changes.
- Frontend contract changes.
- Migration requirements, if any.

## C. Test report

Provide:

- Tests implemented.
- Tests executed.
- Number of tests passed.
- Number of tests failed.
- Number of tests skipped.
- Test commands.
- Any limitations in test coverage.
- Any browser tests not executed.

## D. Security checklist

Provide a checklist with:

- Implemented.
- Tested.
- Not implemented.
- Requires manual verification.
- Requires production configuration.
- Known limitations.

## E. Remaining risks

List all identified risks and unresolved security issues.

Do not mark the system fully secure merely because tests pass.

---

# 24. Final Acceptance Criteria

The task is complete only when:

1. Admin and customer authentication use separate guards.
2. Admin and customer authentication contexts are isolated.
3. Session cookies are separate.
4. Session authentication state is isolated.
5. Admin login does not log out customers.
6. Customer login does not log out administrators.
7. Admin logout does not log out customers.
8. Customer logout does not log out administrators.
9. Session regeneration does not affect the other context.
10. Session expiration does not affect the other context.
11. Admin credentials cannot authenticate customers.
12. Customer credentials cannot authenticate administrators.
13. Admin routes require admin authentication.
14. Customer routes require customer authentication where appropriate.
15. Existing RBAC remains functional.
16. Customer ownership policies are enforced.
17. CSRF protection remains enabled.
18. Cookie security is correctly configured.
19. Session fixation protection is preserved.
20. Session revocation works.
21. Authentication and authorization test suites pass.
22. Existing application tests pass or any regression is explicitly explained.
23. Security-critical browser tests are implemented or their absence is documented.
24. No known critical/high security vulnerability remains unresolved without explicit justification.
25. Documentation explains the architecture and operational configuration.

**Start by auditing the repository and identifying the root cause. Then implement the architecture and tests. Do not stop after producing a plan or recommendations.**

If you encounter an architectural limitation, explain it, propose the safest solution, and implement it when possible without compromising security.
