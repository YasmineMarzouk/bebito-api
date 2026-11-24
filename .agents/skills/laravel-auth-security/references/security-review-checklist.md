# Security Review Checklist

Mark each item **Pass / Fail / N/A / Not verified** and attach evidence. N/A requires rationale.

## Architecture and isolation
- [ ] Admin and customer contexts are explicitly defined.
- [ ] Context isolation is implemented at server-side session/token storage—not only cookie names or guard labels.
- [ ] Login in one context does not alter the other context.
- [ ] Logout/revocation/expiry affects only intended context.
- [ ] Cookies, session IDs, tokens, and CSRF state are handled independently as designed.
- [ ] Session ID rotates after authentication and sensitive privilege changes.
- [ ] Multi-instance deployment preserves isolation.
- [ ] Existing active sessions and rollout behavior are documented.

## Identity and user types
- [ ] Admin provider/guard rejects customer accounts.
- [ ] Customer provider/guard rejects staff accounts.
- [ ] Unknown/null user_type fails closed.
- [ ] Disabled/deleted accounts cannot authenticate or retain prohibited access.
- [ ] Password hashes and user identities are preserved.
- [ ] No frontend-only user-type enforcement.

## Routes and middleware
- [ ] Route inventory covers all routes, aliases, dynamic routes, and HTTP methods.
- [ ] Every protected route has explicit intended authentication context.
- [ ] No accidental default-guard dependency remains.
- [ ] Shared routes have documented principal-specific behavior.
- [ ] Public routes expose only intended public data.
- [ ] Middleware order is correct for sessions, CSRF, authentication, and authorization.
- [ ] Unauthenticated responses match API contract and do not leak data.

## RBAC and ownership
- [ ] Existing RBAC schema is preserved.
- [ ] Staff permissions are checked server-side for each sensitive action.
- [ ] Super-admin bypass is narrow, explicit, and audited.
- [ ] Customers can only access owned resources.
- [ ] Nested resources verify parent ownership.
- [ ] Staff access to customer data requires explicit permission.
- [ ] Missing/failed permission lookup denies access.
- [ ] Permission cache invalidation works.
- [ ] Mass assignment cannot set user_type, role, permission, or owner fields.

## Cookies, CSRF, CORS
- [ ] Secure/HttpOnly/SameSite/domain/path/lifetime are appropriate.
- [ ] Secure is enabled in production HTTPS.
- [ ] SameSite=None is paired with Secure.
- [ ] Cookie deletion attributes match issuance.
- [ ] CORS origins are explicit and trusted.
- [ ] Credentialed CORS never uses wildcard origin.
- [ ] CSRF is enforced on cookie-authenticated mutations.
- [ ] Cross-context CSRF behavior is tested.
- [ ] Reverse proxy TLS configuration is verified.

## Account lifecycle
- [ ] Login throttling and generic failure behavior are present.
- [ ] Password reset and verification are scoped to user type/context.
- [ ] Password change/reset session revocation policy is implemented.
- [ ] Admin MFA is required or documented as an approved risk.
- [ ] Sensitive actions require reauthentication/step-up where appropriate.
- [ ] Remember-me and multi-device behavior are tested.
- [ ] Account disable/revoke invalidates sessions according to policy.

## Data and operations
- [ ] No secrets/tokens/session IDs/password hashes in logs or reports.
- [ ] Audit logs cover privileged actions and avoid sensitive payloads.
- [ ] No destructive migrations/session wipes without approval.
- [ ] Rollback and deployment sequencing are documented.
- [ ] Config/environment changes are documented without exposing values.
- [ ] No production deploy or data mutation occurred without approval.

## Tests and evidence
- [ ] Authentication negative cases pass.
- [ ] Context isolation matrix passes in real browser/session tests.
- [ ] Route authorization and IDOR tests pass.
- [ ] RBAC revocation/cache tests pass.
- [ ] CSRF/CORS/cookie tests pass.
- [ ] Password reset/MFA tests pass or gaps are documented.
- [ ] Ecommerce/admin/customer regressions pass.
- [ ] Tests were not weakened or bypassed.
- [ ] Independent review completed.
- [ ] Critical/High findings are resolved or release is blocked.
