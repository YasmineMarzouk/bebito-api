---
name: laravel-auth-security
description: Audit, redesign, harden, implement, and test isolated Laravel authentication for separate admin and customer applications. Use for authentication/session isolation, Sanctum/cookie/session guard changes, RBAC authorization, security reviews, or related regression work. Always audit first and follow the mandatory phased workflow; never make risky auth changes without explicit approval.
---

# Laravel Authentication Security

## Purpose

Safely audit and, when explicitly requested, implement production-oriented authentication and authorization for Laravel applications that serve separate administrative and customer-facing frontends.

Typical target:
- Admin Dashboard frontend
- Customer Store frontend
- One existing `users` table and `User` model
- `users.user_type` values such as `staff` and `customer`
- Existing database RBAC schema that must remain intact

This skill is security-sensitive. Authentication changes can lock out administrators, expose customer data, invalidate sessions, or break checkout and account workflows. **Audit before modifying. Make changes incrementally. Preserve existing behavior unless a change is justified and approved.**

## Trigger conditions

Use this skill when the task involves:
- Admin/customer login, logout, or current-user endpoints
- Laravel guards, providers, middleware, Sanctum, sessions, cookies, CSRF, CORS
- Separating admin and customer browser authentication contexts
- Authorization policies, permissions, roles, or user-type enforcement
- Auth-related migrations, password reset, MFA, session revocation
- Security testing or review of any of the above

## Project invariants (confirm against repository; do not silently change)

The intended design normally preserves:
- `users` table with `user_type` distinguishing `staff` and `customer`
- Existing `roles`, `user_role`, `sections`, `role_section`, `permissions`, and `role_section_permission` tables
- Existing role/permission semantics and business data
- Existing user identities and password hashes
- Separate admin and customer authentication contexts

Do not create a second user table, replace the RBAC schema, rename `user_type`, or redesign roles unless the repository evidence shows it is necessary and the user explicitly approves the change.

## Mandatory phased execution workflow

**Do not skip, merge, or reorder these phases.** Work phase-by-phase and report the outcome of each. If the user requests implementation, proceed beyond audit only within the authorization they gave. If scope is ambiguous, stop after the audit and ask for approval before security-sensitive changes.

### Phase 0 — Safety, scope, and baseline
1. Read project instructions (`AGENTS.md`, relevant `CLAUDE.md`, package-specific guidance) and this skill's references.
2. Establish repository status: branch, working tree, staged changes, recent relevant commits.
3. Identify user request, allowed scope, prohibited changes, deployment constraints, and rollback needs.
4. Do not overwrite, reset, stash, discard, or reformat unrelated user changes.
5. Record baseline commands and current failures. Never present pre-existing failures as introduced by this task.

### Phase 1 — Read-only architecture audit
Inspect actual repository configuration and code before proposing changes:
- Laravel/PHP versions and installed auth packages (including Sanctum version)
- `config/auth.php`, `config/session.php`, `config/sanctum.php` if present
- environment examples and relevant deployment configuration (never print secrets)
- routes, route groups, middleware aliases/priority, controllers, services, policies, gates
- User model, casts, user-type constants/enums, providers, guards
- login/logout/me endpoints and password reset flows
- session driver, cookie name/domain/path/SameSite/Secure/HttpOnly settings
- frontend origins, API base URLs, credentialed requests, CSRF setup
- CORS configuration and proxy/TLS assumptions
- RBAC implementation and permission checks
- tests, factories, seeders, frontend integration and browser tests
- session storage, cache, queues, and deployment topology where relevant

Create a route security inventory. For every API/web route or route family, classify:
- Public, admin-only, customer-only, or explicitly shared
- Authentication guard/context
- Required `user_type`
- Required permission/policy/ownership check
- Middleware and rate limits
- Whether it mutates data or exposes sensitive data
- Test coverage and gaps

Trace authentication end-to-end: browser → cookie/CSRF → route middleware → guard/provider → principal → authorization → controller/service → response. Identify implicit default-guard assumptions and session coupling.

**Output:** current-state diagram or concise flow, findings ranked Critical/High/Medium/Low, route inventory, assumptions, compatibility risks, and a proposed design. No code changes in this phase.

### Phase 2 — Design and approval gate
Before implementation, write a target design and migration plan. Explicitly answer:
1. How admin and customer browser contexts are isolated.
2. Which guards/providers/middleware/routes are used.
3. How user-type restrictions are enforced at authentication and authorization boundaries.
4. How sessions/cookies are independently issued, stored, expired, rotated, revoked, and logged out.
5. How CSRF and CORS work for each frontend.
6. How shared services and default guard access are handled.
7. How password reset, email verification, MFA, throttling, and account lockout are affected.
8. What data/config/code changes and migrations are needed.
9. How rollout, compatibility, rollback, and existing sessions are handled.
10. Which tests prove isolation and prevent regression.

**Approval gate:** Ask for explicit approval before any destructive or high-impact change, including schema changes, broad route/middleware rewrites, invalidating all sessions, changing cookie domains, changing auth providers, or removing legacy auth paths. If implementation was explicitly requested and the design is a low-risk, reversible fit, proceed while documenting it; otherwise pause.

### Phase 3 — Implement in small, reversible increments
1. Add/adjust configuration and dedicated auth boundaries first.
2. Enforce `user_type` restrictions in providers/guards and/or dedicated middleware; do not rely only on frontend hiding.
3. Apply explicit guard selection in route middleware and controller/service access where justified.
4. Implement true session isolation (see `references/session-isolation.md`); separate guard names alone are not sufficient.
5. Separate login, logout, and identity endpoints; make logout revoke only the intended context.
6. Preserve RBAC tables and ensure permissions are evaluated for the authenticated principal.
7. Add focused tests with each increment.
8. Keep diffs minimal; avoid unrelated refactors or formatting.
9. Use framework-supported APIs matching the installed Laravel version; verify signatures and behavior from installed source/docs rather than assumptions.
10. Review each diff for fail-open behavior, accidental default guard usage, cookie/session coupling, and data exposure.

### Phase 4 — Comprehensive verification
Run and record:
- Relevant unit/feature tests
- Authentication and authorization suites
- Route listing / middleware inspection
- Static analysis and formatter/linter where configured
- Frontend type/build checks and browser tests if available
- Regression tests for customer checkout/account and admin operations
- Security tests from `references/test-scenarios.md`
- Migration/config validation and deployment checks

Do not weaken assertions, skip tests, or alter expected behavior merely to make the suite green. If a test cannot run, explain why and mark it **Not run**, not Passed.

### Phase 5 — Independent security review
Perform a fresh review as if the implementation were written by someone else. Use `references/security-review-checklist.md`. Search for:
- Every route without intended auth middleware
- Every default/implicit guard call
- Any admin principal accepted by customer endpoints or vice versa
- Session/cookie reuse or shared logout/revocation
- Missing object ownership checks
- Role/permission bypasses and fail-open checks
- CSRF/CORS mistakes, token leakage, logging secrets
- Mass assignment, IDOR, account enumeration, rate-limit gaps
- Tests that pass for the wrong reason or only test happy paths

Fix confirmed defects within approved scope and rerun affected tests. If a Critical/High issue remains, clearly block release.

### Phase 6 — Report and handoff
Use `templates/security-report.md`. Include:
- What was audited/changed and what was not
- Architecture and security boundaries
- Route inventory summary and uncovered routes
- Tests with exact commands and results
- Findings, residual risks, and release blockers
- Migration/rollout/rollback instructions
- Files changed and compatibility notes

Never state “100% secure.” State evidence-based confidence and limitations.

## Non-negotiable safety rules

- **No blind auth rewrites.** Never replace all `web`/`Auth::user()`/`auth()`/`$request->user()` occurrences mechanically.
- **No guard-name-only isolation.** Separate Laravel session guards using the same session store can still share the session payload. Prove actual isolation.
- **No cookie-only theater.** A different cookie name is not sufficient if both contexts ultimately share or overwrite the same authentication state.
- **No frontend-only security.** Backend middleware, guards, policies, and ownership checks must enforce access.
- **No fail-open authorization.** Missing roles/permissions, null user, malformed claims, or exceptions must deny access by default.
- **No privilege escalation.** Customer login must not authenticate staff; admin login must not authenticate customers. Verify at the server boundary.
- **No schema/RBAC replacement without approval.** Preserve the existing schema and semantics by default.
- **No destructive data/session action without approval.** Never truncate session tables, invalidate all sessions, delete users/roles, or run destructive migrations without explicit authorization and a rollback plan.
- **No secrets in output.** Do not print `.env` secrets, cookies, session IDs, bearer tokens, password hashes, reset tokens, or personal data. Redact logs and test artifacts.
- **No unsupported claims.** Do not claim tests, browser flows, penetration tests, or deployment checks ran unless they actually ran.
- **No silent security tradeoffs.** Explain any compatibility compromise and its security implications.
- **No production actions by default.** Do not deploy, rotate production secrets, alter production data, or change live access without explicit approval.
- **No unsafe test shortcuts.** Never disable CSRF, middleware, authorization, or rate limits in production code to make tests pass. Test-only bypasses must be isolated and documented.
- **Protect user work.** Keep unrelated changes and preserve current branch/worktree state.

## Technical principles

- Authentication establishes identity; authorization determines allowed actions. Both must be checked.
- Prefer explicit, named auth contexts and explicit route middleware.
- Restrict user providers or validate user type at the authentication boundary.
- Policies should check ownership and role/permission requirements for individual resources.
- Deny by default and use least privilege.
- Session IDs must rotate on authentication and privilege changes; logout/revocation must invalidate the intended session context.
- Use secure cookie attributes appropriate to deployment: Secure in HTTPS, HttpOnly, deliberate SameSite, narrowest practical domain/path, and no unnecessary cross-subdomain sharing.
- Credentialed CORS must use explicit trusted origins, never wildcard origin with credentials.
- CSRF protection is required for cookie-authenticated state-changing requests.
- Rate-limit login, password reset, verification, MFA, and sensitive actions.
- Admin MFA should be required or explicitly documented as an approved risk.
- Audit privileged actions without logging secrets or sensitive payloads.
- Follow the installed Laravel/Sanctum version's actual semantics.

## Reference files

Read the references relevant to the task:
- `references/authentication-architecture.md`
- `references/session-isolation.md`
- `references/authorization-rbac.md`
- `references/security-checklist.md`
- `references/test-scenarios.md`
- `references/phased-workflow.md`

For a final report, use `templates/security-report.md`.

## Invocation examples

- `$laravel-auth-security Audit the admin/customer authentication architecture. Do not modify files.`
- `$laravel-auth-security Implement the approved admin/customer isolation design. Follow all phases and tests.`
- `$laravel-auth-security Review this authentication PR for security regressions; do not edit.`
