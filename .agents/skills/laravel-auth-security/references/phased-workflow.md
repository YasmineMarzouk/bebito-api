# Mandatory Phased Workflow (Operational)

This reference is a compact execution checklist. The full rules are in `SKILL.md`.

## Phase 0 — Baseline and safety
- Read repository instructions.
- Check branch, status, diffs, untracked files.
- Record scope, approvals, constraints, rollback.
- Preserve user work and baseline failures.

## Phase 1 — Audit only
- Inspect framework/packages/config/routes/models/middleware/frontend/RBAC/tests.
- Trace both auth flows end to end.
- Build route security inventory.
- Rank findings and identify assumptions.
- No edits.

## Phase 2 — Design and gate
- Propose context isolation, guard/provider strategy, session/token lifecycle, CSRF/CORS, authorization, reset/MFA, rollout and rollback.
- Map all impacts and tests.
- Obtain explicit approval for high-impact/destructive changes.
- Do not proceed if the design is unresolved or unsafe.

## Phase 3 — Implementation
- Make small, reversible changes.
- Enforce user type at auth boundary.
- Apply explicit guards and route protection.
- Prove session/token isolation.
- Preserve RBAC and domain behavior.
- Add tests alongside changes.
- Review diffs after each logical increment.

## Phase 4 — Verification
- Run focused tests then complete relevant suites.
- Run static analysis, route/middleware inspection, frontend build/browser tests.
- Execute isolation, IDOR, CSRF, RBAC and ecommerce scenarios.
- Report exact commands, exit codes, skipped/not-run tests.

## Phase 5 — Independent review
- Search all route declarations and implicit guard usage.
- Review for fail-open, shared session, cross-user access, CSRF/CORS, leakage, rate limits.
- Fix within approved scope; rerun tests.
- Critical/High unresolved = release blocker.

## Phase 6 — Handoff
- Complete `templates/security-report.md`.
- Document changes, tests, findings, risks, rollout, rollback.
- No “100% secure” claim.
