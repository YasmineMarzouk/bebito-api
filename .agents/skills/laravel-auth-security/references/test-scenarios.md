# Complete Authentication and Security Test Scenarios

Adapt paths and response formats to the actual repository. Tests must assert both status and absence of unauthorized side effects/data.

## Test setup principles

- Use real middleware and guards in feature tests; do not mock away the boundary under test.
- Use factories/seeders that reflect actual `user_type` and RBAC relations.
- Use two staff accounts with different permissions and two customers with distinct owned resources.
- For browser/session tests, use separate browser contexts and retain cookies independently.
- Assert database changes, events, jobs, files, and response payloads—not only HTTP status.
- Never use production credentials or production data.
- Keep test-only security bypasses isolated from production configuration.

## A. Login and identity

| ID | Scenario | Expected |
|---|---|---|
| AUTH-01 | Valid staff credentials on admin login | Staff authenticated only in admin context |
| AUTH-02 | Customer credentials on admin login | Denied; no admin session/token |
| AUTH-03 | Staff credentials on customer login | Denied; no customer session/token |
| AUTH-04 | Valid customer credentials on customer login | Customer authenticated only in customer context |
| AUTH-05 | Invalid password | Generic failure; no session/token |
| AUTH-06 | Unknown email | Same externally observable failure as invalid password |
| AUTH-07 | Disabled/deleted/unverified account according to policy | Denied consistently |
| AUTH-08 | Unknown or malformed user_type | Denied |
| AUTH-09 | Login session fixation attempt | Session identifier rotates; old identifier unusable |
| AUTH-10 | Login rate limit exceeded | Throttled; no authentication |
| AUTH-11 | Login response | No password/hash/token/session secret leakage |
| AUTH-12 | Repeated login | Session lifecycle follows documented policy |

## B. Context isolation

| ID | Scenario | Expected |
|---|---|---|
| AUTH-13 | Admin login while customer is logged in | Both contexts remain authenticated |
| AUTH-14 | Customer login while admin is logged in | Both contexts remain authenticated |
| AUTH-15 | Admin logout with both contexts active | Admin revoked; customer remains active |
| AUTH-16 | Customer logout with both contexts active | Customer revoked; admin remains active |
| AUTH-17 | Admin session expires | Customer remains valid |
| AUTH-18 | Customer session expires | Admin remains valid |
| AUTH-19 | Admin revocation/password reset | Customer context unaffected |
| AUTH-20 | Customer revocation/password reset | Admin context unaffected |
| AUTH-21 | Admin cookie/session presented to customer route | Rejected as customer |
| AUTH-22 | Customer cookie/session presented to admin route | Rejected as admin |
| AUTH-23 | Cross-context CSRF token | Rejected unless explicitly designed and justified |
| AUTH-24 | Cookie deletion after logout | Correct context cookie cleared; other cookie untouched |
| AUTH-25 | Concurrent tabs and simultaneous requests | No cross-context session mutation |
| AUTH-26 | Multiple backend instances | Isolation persists with shared configured storage |

## C. Route authentication

| ID | Scenario | Expected |
|---|---|---|
| AUTH-27 | Anonymous request to protected admin route | 401/redirect per API contract; no data |
| AUTH-28 | Anonymous request to protected customer route | 401/redirect per API contract; no data |
| AUTH-29 | Customer to admin endpoint | Denied |
| AUTH-30 | Staff to customer-only endpoint | Denied unless explicitly shared |
| AUTH-31 | Shared route with anonymous principal | Public behavior only |
| AUTH-32 | Shared route with staff principal | Defined staff behavior |
| AUTH-33 | Shared route with customer principal | Defined customer behavior |
| AUTH-34 | Route registered outside intended middleware group | Inventory/test detects it |
| AUTH-35 | Alternate HTTP method/route alias | Same authorization protection |

## D. RBAC and policies

| ID | Scenario | Expected |
|---|---|---|
| AUTH-36 | Staff with required permission | Allowed |
| AUTH-37 | Staff lacking required permission | 403; no side effect |
| AUTH-38 | Permission removed while session active | Access revoked according to cache policy |
| AUTH-39 | Unknown permission/role | Denied |
| AUTH-40 | Multiple roles | Exactly documented combined permissions |
| AUTH-41 | Super-admin behavior | Only documented bypass; audit event |
| AUTH-42 | Role assignment endpoint without permission | Denied |
| AUTH-43 | Customer tries role/permission manipulation | Denied; no DB change |
| AUTH-44 | Policy exception/missing relationship | Fail closed |

## E. Ownership / IDOR

| ID | Scenario | Expected |
|---|---|---|
| AUTH-45 | Customer accesses own order | Allowed |
| AUTH-46 | Customer accesses another customer's order | 403/404; no data |
| AUTH-47 | Customer changes owner/customer ID in payload | Ignored/rejected; ownership unchanged |
| AUTH-48 | Customer accesses nested item belonging to another order | Denied |
| AUTH-49 | Customer accesses another user's address/cart/upload | Denied |
| AUTH-50 | Staff accesses customer resource without permission | Denied |
| AUTH-51 | Staff with explicit permission accesses customer resource | Allowed and audited if sensitive |
| AUTH-52 | Guessable/sequential IDs | No cross-customer disclosure |

## F. CSRF, CORS, and cookies

| ID | Scenario | Expected |
|---|---|---|
| AUTH-53 | Cookie-authenticated POST without CSRF | Rejected |
| AUTH-54 | Invalid CSRF token | Rejected |
| AUTH-55 | Trusted frontend origin with credentials | Allowed as configured |
| AUTH-56 | Untrusted origin | No credentialed access |
| AUTH-57 | Wildcard origin + credentials configuration | Configuration test fails |
| AUTH-58 | Secure cookie in production HTTPS | Secure, HttpOnly, intended SameSite |
| AUTH-59 | SameSite=None cookie | Secure required |
| AUTH-60 | Cookie domain/path | No unnecessary sibling-subdomain exposure |
| AUTH-61 | Logout cookie clearing | Same name/domain/path; intended cookie only |
| AUTH-62 | CSRF bootstrap per frontend | Correct independent behavior |
| AUTH-63 | Proxy HTTPS headers | Secure cookie behavior correct |

## G. Password reset, verification, MFA

| ID | Scenario | Expected |
|---|---|---|
| AUTH-64 | Customer reset flow | Only customer identity/context affected |
| AUTH-65 | Staff reset flow | Only staff identity/context affected |
| AUTH-66 | Reset token used by wrong user type | Rejected |
| AUTH-67 | Expired/used reset token | Rejected |
| AUTH-68 | Reset response/email | No account enumeration |
| AUTH-69 | Password change | Revokes/rotates sessions per policy |
| AUTH-70 | Email verification token wrong context | Rejected |
| AUTH-71 | Admin without MFA (if required) | Cannot access privileged routes |
| AUTH-72 | MFA replay/invalid code | Rejected and rate-limited |
| AUTH-73 | MFA recovery code reuse | Rejected |
| AUTH-74 | Step-up auth for sensitive action | Required and enforced |

## H. Ecommerce regressions

| ID | Scenario | Expected |
|---|---|---|
| ORDER-01 | Anonymous browsing/catalog | Public catalog remains available |
| ORDER-02 | Customer login | Cart/account context preserved as designed |
| ORDER-03 | Customer checkout | Auth/guest policy works |
| ORDER-04 | Customer reads own orders | Allowed |
| ORDER-05 | Customer reads another customer's orders | Denied |
| ORDER-06 | Customer edits own profile/address | Allowed |
| ORDER-07 | Customer edits another customer's profile/address | Denied |
| ORDER-08 | Admin manages products with permission | Allowed |
| ORDER-09 | Admin without product permission | Denied |
| ORDER-10 | Admin manages orders with permission | Allowed |
| ORDER-11 | Admin without order permission | Denied |
| ORDER-12 | Customer session logout | Admin context remains |
| ORDER-13 | Admin session logout | Customer context remains |
| ORDER-14 | Customer password reset | Admin session remains |
| ORDER-15 | Admin password reset | Customer session remains |
| ORDER-16 | Payment/webhook callback | Does not depend on browser session; signature validated |
| ORDER-17 | Upload/download ownership | Enforced |
| ORDER-18 | Return/refund action | Customer ownership or staff permission |
| ORDER-19 | Guest cart merge | Cannot merge into another customer's cart |
| ORDER-20 | Existing frontend auth/CSRF flow | Build and browser integration pass |

## I. Security and regression

- Session fixation and replay
- Brute force and credential stuffing rate limits
- Account enumeration
- Timing/response consistency where relevant
- Mass assignment of `user_type`, roles, permissions, owner IDs
- Open redirect after login/logout/reset
- Token leakage in URLs, logs, analytics, error traces
- Insecure direct object references on all resource families
- Authorization cache stale after revocation
- Disabled account with active session
- Logout from one context while another is active
- Exceptions in auth middleware do not fall through
- Failures do not expose stack traces/secrets in production
- Security headers and HTTPS deployment assumptions

## Evidence and reporting

For each test, record:
- test ID and title
- exact command or test name
- expected and actual result
- side-effect/data assertions
- status: Passed / Failed / Not run / Blocked
- evidence (sanitized logs, screenshot, or test output)
- defect/issue reference if failed

Passing automated tests is evidence, not proof of complete security.
