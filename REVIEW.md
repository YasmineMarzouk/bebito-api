# Maw V3 — Code Review Guidance

## Project Context

- **Framework:** Laravel 12 with Octane (RoadRunner)
- **Language:** PHP 8.2+
- **Architecture:** Modular (nwidart/laravel-modules + alizharb/laravel-modular), follows Clean Architecture (Uncle Bob)
- **Frontend:** Vite + Tailwind CSS 4
- **Infra:** Docker, AWS (ECS, S3, CodeBuild), RoadRunner
- **CI pipeline:** AWS CodeBuild with Trivy vuln/secret scans, PHPUnit, Composer audit
- **Modules (17):** Assistance, Auth, Company, Complaints, Consulting, Core, Documents, Experience, News, Portal, Recruitment, Register, Reports, Reviews, User, Wally, WebAssistance

## Architecture Authority

**All architecture rules are defined in [`docs/architecture/ARCHITECTURE.md`](docs/architecture/ARCHITECTURE.md).** That file is the single source of truth for this project's structure, layer responsibilities, dependency rules, exception handling, naming conventions, and module layout. The review agent MUST consult it when evaluating any PR that touches module structure, use cases, repositories, DTOs, controllers, or cross-module communication.

If a PR violates a rule in ARCHITECTURE.md, flag it as **HIGH** severity (or **CRITICAL** if it breaks a core principle like dependencies pointing inward). When an implementation conflicts with ARCHITECTURE.md, the architecture document wins — the code must be updated.

## Static Analysis & Style Baseline

Pull requests must pass the following checks (enforced in CI or locally):

| Tool | Config | Command |
|------|--------|---------|
| PHPStan | Level 7 (Larastan) | `vendor/bin/phpstan analyse` |
| Pint | Laravel preset, braces `next` | `vendor/bin/pint --test` |
| Rector | Laravel code quality + collection sets | `vendor/bin/rector --dry-run` |
| PHP Insights | phpinsights.php | `vendor/bin/phpinsights` |
| PHPUnit | Unit + Feature suites, sqlite in-memory | `php artisan test` |
| Infection | infection.json5 | `vendor/bin/infection` |

If a diff introduces code that would fail any of these, flag it as a **CRITICAL** finding.

## Security and Hard Constraints

These are hard stops. Flag as **CRITICAL** with `severity: critical`:

1. **NO secrets in code.** No API keys, tokens, passwords, or private keys in any committed file — including config, tests, stubs, and comments. `.env.example` may contain placeholder keys only (e.g., `key=`)
2. **NO raw SQL.** All queries must use Eloquent ORM, query builder, or properly bound parameters. Raw `DB::statement()` or string concatenation in queries is rejected unless accompanied by a documented justification in the PR description.
3. **NO unsanitized output.** Any user-originated data rendered in Blade must use `{{ }}` (auto-escaped) or be explicitly sanitized. `{!! !!}` requires a reviewer rationale comment.
4. **Authorization on every route.** Every route (web and API) must have a corresponding auth middleware or policy. No unprotected endpoints.
5. **No `env()` outside config files.** Per Laravel best practice and PHPStan's `larastan.noEnvCallsOutsideOfConfig` rule.
6. **No bypass of mass-assignment protection.** All `$fillable`/`$guarded` attributes must be explicitly declared. No `Model::unguard()` in production paths.

## Localization (i18n)

The application is bilingual. Flag as **HIGH** severity:

1. **Every user-facing string must be translated.** No hardcoded text in controllers, Blade templates, Vue components, API responses, validation messages, emails, PDFs, notifications, or error messages. Use Laravel's `__()` helper or `trans()` function with translation keys in `lang/en/` and `lang/it/`.
2. **New translation keys added to both `en` and `it` files.** A PR introducing keys only in one language is incomplete.
3. **API responses must be localized.** Use `Accept-Language` header or user preference to determine the response language.
4. **Exception messages intended for end users must be translatable.** DomainException messages shown to users go through `__()`.

## Architecture Invariants

All architecture rules are documented in [`docs/architecture/ARCHITECTURE.md`](docs/architecture/ARCHITECTURE.md). Key high-level constraints summarized here for quick reference — but the full document is authoritative:

1. **Dependencies point inward only.** No framework classes (`Request`, `Model`, `Storage`) in use cases. Use cases import interfaces, not concretions.
2. **Use cases own application logic.** Controllers validate and delegate. Services wrap infrastructure. Repositories abstract persistence.
3. **Repositories are the only entry point to the database.** No `Model::where()` or `->save()` outside a repository.
4. **Single-action controllers only.** Versioned under `Http/Controllers/V1/`.
5. **DTOs are dumb.** Hold data, never inject services or perform I/O.
6. **Three exception categories only.** DomainException, ExternalServiceException, and the global handler. No bare `catch (\Exception $e)` outside the handler.
7. **API versioning:** Routes namespaced under `api/v1/`, `api/v2/`, etc. Breaking changes require a new version.

## Testing Expectations

- New business logic, services, and API endpoints require feature tests covering the happy path and at least one failure/edge case.
- Bug fixes require a regression test reproducing the original failure.
- Tests use `sqlite :memory:` (see phpunit.xml). Do not introduce tests that depend on a real MySQL connection.
- Mock external services (AWS, notifications, etc.) using Laravel's `Http::fake()` or bound interfaces. No real network calls in tests.
- Do not mark untested PRs as passing review. Flag missing tests as a **HIGH** severity item.

## Files to Skip (Non-Reviewable)

The review agent should not flag these file types unless a specific security concern exists:

- `composer.lock`, `package-lock.json`, `yarn.lock` — dependency lock files
- `vendor/`, `node_modules/` — external packages
- `storage/` — generated/cache files
- `*.min.js`, `*.min.css`, `*.map` — compiled assets
- `openapi/`, `apidog/` — auto-generated OpenAPI specs (unless structural issues)
- `docs/` — documentation (skip unless the PR modifies `docs/architecture/` or is documentation-only)

## Severity Calibration

| Severity | When to Use |
|----------|-------------|
| **critical** | Security vulnerability, secret exposure, data loss risk, SQL injection, auth bypass |
| **high** | Missing authorization, broken business logic, missing mandatory tests, potential N+1 query, module isolation violation |
| **medium** | Code style violations (not caught by Pint), missing type hints (PHPStan L7), potential bug with low likelihood, missing docblocks on public API |
| **low** | Naming suggestions, minor readability improvements, optional refactoring hints |
| **info** | Positive observations, architectural alignment notes |

## Module-Specific Focus Areas

| Module | Key Risks |
|--------|-----------|
| **Auth** | JWT handling (firebase/php-jwt), Socialite OAuth flows, session/CSRF, Sanctum tokens |
| **Register** | Input validation, email verification flow, GDPR compliance |
| **Core** | Middleware chain (RequestLogging, Datadog), base classes used by all modules — changes here affect every module |
| **Company / User** | PII handling, relationship integrity, role/permission logic |
| **Documents** | File upload security, mPDF rendering, S3 presigned URLs |
| **Reports** | Data aggregation, large dataset performance, PDF generation |
| **Consulting / Reviews** | User-generated content, rating integrity |
| **Portal** | User-facing API surface, CORS, rate limiting |

## Sub-Agent Usage

Use 0 sub-agents for:
- Single-file typo, doc, or config changes
- `composer.json`/`package.json` dependency bumps (audit for known vulns only)
- Formatting-only or Pint auto-fix PRs
- CI/CD configuration changes (`buildspec.yml`, `docker-compose.yml`)

Use 1 sub-agent for:
- Focused changes under 300 lines touching one risky area (Auth, billing, database migrations, file upload)

Use 3 sub-agents for PRs spanning multiple domains:
1. **Architecture / module-boundary reviewer:** checks module isolation, service class delegation, DTO usage, route authorization
2. **Security / data-flow reviewer:** checks input validation, output escaping, secret exposure, SQL injection, auth bypass
3. **Test coverage reviewer:** checks that tests cover observable behavior, edge cases, and regression tests for bug fixes

Use 6 sub-agents only for PRs above 800 changed lines or security-sensitive work:
1. PHPStan / static analysis alignment
2. Architecture & module boundary enforcement
3. Security deep-dive (OWASP top 10 for Laravel)
4. Performance (N+1 queries, eager loading, caching, Octane memory leaks)
5. Test quality & coverage
6. API contract & DTO validation

Each sub-agent must stay read-only, must not post comments, and must return findings with: path, line, severity, rationale, and confidence. The main reviewer verifies every finding before posting.

## Summary Style

Reviews should include:
1. A one-line verdict (Approve / Approve with suggestions / Request changes)
2. A table of findings grouped by severity
3. Any architectural concerns that span multiple files
4. Positive callouts for well-structured code, good test coverage, or clean module separation

## Additional Guidance

- The project targets **deployment on AWS ECS** with RoadRunner. Flag any changes that assume a shared filesystem (use S3 for persistent storage) or stateful request handling.
- **Octane compatibility:** Flag use of `$_SERVER`, `$_GET`, `$_POST` superglobals or static-state patterns that don't reset between requests in long-running PHP processes.
- **Infection mutation testing** is configured — high mutation score thresholds apply to new code.
- The `Core` module is the backbone; PRs touching `modules/Core/` should receive extra scrutiny.
