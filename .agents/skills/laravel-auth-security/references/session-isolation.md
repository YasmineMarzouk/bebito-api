# Session and Cookie Isolation Reference

## Critical distinction

Laravel's standard session guards persist authentication state in the session. Merely defining `admin` and `customer` guards does **not** guarantee isolation if both guards use the same session payload/store and can coexist or overwrite state.

The design must demonstrate that:
- Admin authentication cannot mutate or replace customer authentication state.
- Customer authentication cannot mutate or replace admin authentication state.
- Admin logout leaves customer authentication usable.
- Customer logout leaves admin authentication usable.
- Expiration, rotation, and revocation operate independently.
- Session fixation and replay are mitigated.

## Architecture choices to evaluate

Select based on installed framework version, frontend topology, deployment, and threat model. Do not assume one approach is universally correct.

### Option A — Separate browser session contexts
Use independently configured session cookies and genuinely separate session namespaces/stores or a custom session/auth mechanism that isolates payloads. Verify framework lifecycle behavior, middleware, CSRF, cookie clearing, and session regeneration.

Potential implementation details to investigate:
- distinct cookie names
- distinct cookie paths/domains where compatible with routing
- distinct session storage namespace/table/Redis prefix or separate stores
- middleware ordering and session manager configuration
- guard state persistence keys and logout behavior
- CSRF token/session coupling
- shared load balancers and backend instances

Cookie path/domain can restrict where cookies are sent, but it does not by itself prove independent server-side authentication state.

### Option B — Separate token authentication contexts
Use separate token issuance/validation boundaries, token abilities/audiences, and revocation rules. Evaluate Sanctum or another installed mechanism. Ensure:
- admin tokens cannot be used on customer routes
- customer tokens cannot be used on admin routes
- token audience/context is enforced server-side
- revocation and rotation work
- browser token storage does not introduce XSS exposure
- CSRF protections are appropriate if tokens are stored in cookies
- admin MFA and short lifetime are addressed

Do not casually store bearer tokens in localStorage/sessionStorage; explain XSS implications and alternatives.

### Option C — Separate backend deployments or auth services
Consider only when justified by threat model and operational requirements. This increases deployment, secret, observability, and maintenance complexity. It may still share identity data but must preserve isolation.

## Cookie controls

For each auth cookie document and verify:
- Name is distinct where needed
- `HttpOnly` enabled unless a justified design requires otherwise
- `Secure` enabled in HTTPS production
- deliberate `SameSite` (`Lax`, `Strict`, or `None`) with rationale
- `SameSite=None` paired with Secure
- domain is as narrow as feasible
- path is as narrow as feasible
- lifetime matches session policy
- encryption/signing behavior is understood
- cookie deletion uses matching name/domain/path attributes
- no secrets in cookie logs or URLs

Avoid broad parent-domain cookies unless required. Sharing `.example.com` cookies can widen exposure to sibling subdomains.

## CSRF and CORS

- Cookie-based state-changing requests must have CSRF protection.
- CSRF bootstrap endpoints and token handling must be tested separately for each context.
- CORS origins must be explicit and trusted.
- Never combine `Access-Control-Allow-Origin: *` with credentialed requests.
- `supports_credentials` must be deliberate.
- CORS is not an authentication or authorization control.
- Verify preflight, credential inclusion, error responses, and logout behavior.
- Confirm reverse proxy HTTPS detection so Secure cookies are correctly emitted.

## Lifecycle

Test and document:
- session ID regeneration after successful login
- regeneration after privilege elevation or sensitive reauthentication
- invalidation on logout
- independent session expiry
- session revocation on account disable/password reset as required
- remember-me behavior, if enabled
- concurrent tabs and multiple devices
- stale cookies after deployment
- rotation of signing/encryption secrets and consequences

## Middleware and guard correctness

Inspect middleware order: session start, CSRF, authentication, user-type validation, authorization. Confirm the selected guard is available at the point request identity is read.

Do not globally change the default guard without auditing every consumer. Do not set a guard globally for a request and assume downstream services always use it safely; use explicit identity dependencies where appropriate.

## Isolation proof matrix

| Action | Admin remains authenticated? | Customer remains authenticated? |
|---|---:|---:|
| Admin login | Yes | Yes |
| Customer login | Yes | Yes |
| Admin logout | No (admin only) | Yes |
| Customer logout | Yes | No (customer only) |
| Admin session expires | No | Yes |
| Customer session expires | Yes | No |
| Admin password reset/revoke | Revoke per policy | Yes |
| Customer password reset/revoke | Yes | Revoke per policy |

The test must use independent browser contexts or actual cookies/session state, not just direct calls with mocked users.

## Red flags

- One session ID or cookie used by both contexts without proven separation.
- Logout calls `$request->session()->invalidate()` and clears a shared session, unintentionally logging out both contexts.
- Guard logout is assumed to clear only one context but session regeneration/invalidation affects the whole payload.
- Session data is shared across Redis prefixes or session table namespaces without isolation analysis.
- Cookie clearing attributes differ from cookie creation.
- CSRF token from one context is accepted in the other without intentional design.
- User type is checked only after protected controller logic begins.
