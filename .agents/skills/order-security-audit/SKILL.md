---
name: order-security-audit
description: Audit, test, harden, and verify a Laravel ecommerce order module for security, correctness, inventory integrity, concurrency, and production reliability. Use when reviewing checkout, order creation, order listing/details, admin order management, cancellation, fulfillment, COD payment status, stock, discounts, shipping, or order-related regressions. Includes a COD-only payment model (no online payment gateway).
---

# Order Security & Reliability Audit

## Purpose

Perform a repository-grounded security and reliability audit of the ecommerce order module, then implement or improve tests and fixes within the user's requested scope. The project is a Laravel ecommerce application with separate customer/admin authentication contexts. Payment is **cash on delivery (COD) only**; there is no online payment gateway.

## Mandatory rules

- Read the repository's `AGENTS.md`, applicable `CLAUDE.md`, and relevant skills before work.
- Inspect actual code, migrations/schema, routes, policies, middleware, and tests. Never invent route names, model names, statuses, permissions, or business rules. Use `references/database-schema.md` as a map of the uploaded SQL data dump, but treat it as observed INSERT-column evidence—not authoritative DDL.
- Preserve the existing customer/admin authentication architecture. Do not replace guards, cookies, sessions, or identity models as part of an order audit unless explicitly requested.
- Do not silently alter business rules. Explain ambiguous rules and ask only when an answer is essential; otherwise document assumptions and continue with a safe audit.
- Start with a read-only audit and plan. Do not immediately rewrite the order module.
- Make small, reversible changes. Avoid unrelated refactors and dependency additions.
- Never run destructive commands against production or a shared database. Confirm the test database is isolated before migrations, resets, seeders, or destructive test setup.
- Never use real customer data, real COD operations, or production orders for load tests.
- Never claim a test, scan, or load test passed unless it was actually executed and its output supports the claim.
- Do not use mocks to claim database locking, transaction isolation, inventory concurrency, or load behavior was verified. Use the real supported test database for those claims.
- Do not log or expose secrets, session IDs, tokens, addresses, or unnecessary customer/payment data.
- Fail closed on security-sensitive validation and authorization.
- If a vulnerability is discovered, add a regression test. Distinguish confirmed findings from hypotheses and unverified risks.

## Project-specific domain constraints and schema guardrails

- Sales are currently restricted to Tunisia. The supplied dump has `countries(code, name, is_active, created_at, updated_at)` with active `TN`; checkout shipping addresses are stored in `order_addresses.country_code`. Verify the active-country lookup and enforce the current Tunisia-only business rule server-side. Do not hardcode a country label if the country code/configuration is the established source.
- The supplied `currencies` data has active `TND` and inactive `USD`. Checkout/order creation must use the active supported currency from server-side configuration/database logic; never trust a client-supplied currency. Verify how `decimal_places` and integer amount columns are represented before doing arithmetic. The sample uses `TND` with `decimal_places = 3` and values such as `41000`; do not assume ordinary two-decimal cents.
- The coupon schema observed in the dump is `coupons(code, type, percentage, fixed_amount, min_order_amount, usage_limit, usage_count, expires_at, is_active)`. Do not introduce fictitious `discount_value`, `max_usage`, or `status` fields. Determine accepted `type` values from migrations and code; likely branches are percentage/fixed amount but confirm exact enum/string values and which amount field applies.
- Coupon eligibility must inspect `is_active`, `expires_at`, `min_order_amount`, and the actual meaning of `usage_limit`/`usage_count`. Do not add per-customer limits, start dates, product restrictions, or other rules absent from the schema/business logic.
- `orders` stores coupon snapshots (`coupon_id`, `coupon_code`, `coupon_discount_amount`) in addition to `total_amount`. Verify whether coupon usage is counted at order placement, confirmation, fulfillment, or another lifecycle point. Make counter changes transaction-safe and idempotent, and prevent concurrent requests from exceeding the effective limit. Do not increment/decrement counters on retries or cancellations without a verified policy.
- Preserve historical order item snapshots (`product_name`, `sku`, `unit_price_amount`, `total_amount`) and order coupon snapshots. Do not recompute old orders from today's catalog/coupon values.
- `order_items.product_variant_id` appears nullable in the dump. Determine whether legacy/deleted variants are intentionally supported; do not add a NOT NULL migration or reject historical records without evidence.
- `orders.user_id` appears nullable in the dump. Determine whether guest checkout is supported by routes and code; test the actual supported modes rather than inferring guest checkout solely from nullable-looking sample data.

## COD-specific scope

Do not design or test online payment gateway features unless the repository unexpectedly contains such integration and the user explicitly asks to audit it. Exclude card payment, gateway webhooks, signature verification, gateway retries, and online refund callbacks from the default scope.

Include COD controls:
- Customer cannot choose an unsupported payment method or manipulate payment state.
- New COD orders initialize to the correct unpaid/pending state according to the actual domain.
- Customer cannot mark an order paid or confirm cash collection.
- Only an appropriately authorized admin/staff action may record cash collection.
- Payment status changes are validated, auditable, and idempotent.
- Delivery and cash collection are modeled according to the actual business workflow; do not assume delivery automatically means paid.
- Cancellation cannot incorrectly mark an order paid or trigger duplicate stock effects.
- COD-related status and amount fields are protected from mass assignment.

## Workflow

### Phase 0 — Establish scope and safe environment
1. Identify requested scope (full audit, specific feature, regression, or test-only).
2. Locate test commands, database configuration, and environment files without printing secrets.
3. Confirm test environment is isolated and not production/shared.
4. Record the baseline git status; do not overwrite user changes.
5. Identify the project's testing conventions (PHPUnit/Pest, factories, traits, helpers).

### Phase 1 — Read-only architecture discovery

Before changing coupon or order code, read `references/database-schema.md` and reconcile every observed table/column against the Laravel migrations, models, casts, relationships, constraints, and current application code. The archive is a data dump without `CREATE TABLE` statements; it does not prove nullability, types, indexes, foreign keys, uniqueness, or defaults. If the live migrations disagree with the dump, migrations and the running application are authoritative; document the discrepancy.

Project-specific table map to investigate:
- `coupons`: `code`, `type`, `percentage`, `fixed_amount`, `min_order_amount`, `usage_limit`, `usage_count`, `expires_at`, `is_active`.
- `orders`: `number`, nullable-looking `user_id` in observed rows, customer snapshots, `currency_code`, `status`, `payment_method_code`, `coupon_id`, `coupon_code`, `coupon_discount_amount`, `total_amount`.
- `order_items`: `product_variant_id` (observed nullable in at least one row), `product_name`, `sku`, `quantity`, `unit_price_amount`, `total_amount`.
- `payments`: `method`, `status`, `provider_reference`, `amount`, `currency_code`, `metadata`, `paid_at`.
- `order_addresses`: `type`, recipient/contact/address fields, `country_code`.
- `order_status_histories`: `status`, `changed_by`, `note`.
- Inventory/catalog candidates: `inventory_items`, `inventory_movements`, `product_variants`, `product_prices`, and legacy-looking `products.stock_quantity`, `products.price_amount`, `products.compare_at_price_amount`.

Do not assume both catalog/inventory representations are active sources of truth. Trace the checkout read/write path and identify the authoritative price and stock source before proposing changes.
Inspect and map:
- Order routes (customer and admin), route middleware, guards, policies, gates, permission checks.
- Controllers, Form Requests, services/actions, models, relationships, observers, events/listeners, jobs, notifications.
- Order, order-item, product, variant/size, inventory/stock, address, discount/coupon, shipping, and COD/payment fields/tables.
- Actual order lifecycle and allowed transitions.
- Checkout calculations and where price/stock are read and written.
- Transactions, row locks, atomic updates, unique constraints, foreign keys, indexes.
- Cancellation, fulfillment, delivery, and cash-collection paths.
- Existing test coverage and test infrastructure.

Produce a concise architecture map and list uncertainties. Cite source paths and relevant symbols/line numbers where possible.

### Phase 2 — Plan and approval boundary
Create a prioritized plan:
- P0: ownership/authorization bypass, price or payment-state manipulation, overselling, duplicate orders, transaction integrity.
- P1: lifecycle/cancellation inconsistencies, discount/shipping integrity, data leakage, resilience.
- P2: performance, observability, edge cases, maintainability.

Separate:
- tests that can be added without changing runtime behavior;
- fixes requiring business-rule decisions;
- schema/dependency changes requiring explicit approval.

Do not perform broad schema or architecture changes without approval.

### Phase 3 — Test implementation
Use the existing conventions. Prefer:
- Feature tests for HTTP/auth/authorization and observable behavior.
- Unit tests for isolated calculation/state-transition logic.
- Database-backed tests for transactions and constraints.
- Integration/concurrency tests with real database connections when required.
- k6 scripts as a separate, opt-in staging load suite.

Use factories and fixtures responsibly. Avoid brittle tests that duplicate implementation details rather than assert outcomes. Each discovered defect should have a regression test.

### Phase 4 — Fixes (only within scope)
For approved fixes:
- Make the smallest change that resolves the issue.
- Preserve existing API contracts unless change is approved.
- Enforce authorization on the server, not frontend-only.
- Validate all client-controlled fields.
- Calculate prices, discounts, shipping, and totals on the server.
- Use transactions and locking/atomic inventory operations where the actual concurrency model requires them.
- Protect sensitive fields from mass assignment.
- Make retryable operations idempotent.
- Keep notification/queue failure from corrupting committed order data.
- Add regression coverage before/with the fix.

### Phase 5 — Verification
Run in this order where available:
1. Targeted new tests.
2. Existing order tests.
3. Full test suite.
4. Static analysis/lint relevant to changed code.
5. Database concurrency tests using the real configured test DB.
6. k6 only when explicitly requested and against an approved isolated staging environment.

Record exact commands and actual outcomes. If a test cannot run, state why and mark NOT VERIFIED.

### Phase 6 — Report and handoff
Use `references/audit-report-template.md`. Include:
- Architecture and actual lifecycle.
- Scope and exclusions.
- Findings with severity, evidence, impact, and remediation.
- Tests added, commands run, pass/fail/skip counts.
- Checklist statuses with evidence.
- Concurrency/load results and limitations.
- Residual risks and production recommendation: GO / CONDITIONAL GO / NO-GO.

## Test matrix

Use `references/test-scenarios.md` as the scenario catalog and `references/database-schema.md` for the observed schema map. Tailor tests to actual code and rules; mark scenarios NOT APPLICABLE with reasons rather than forcing nonexistent functionality.

## Checklist

Use `references/security-checklist.md` and record PASS / FAIL / NOT IMPLEMENTED / NOT VERIFIED / NOT APPLICABLE, with evidence for every meaningful status.

## Concurrency and load

Use `references/concurrency-testing.md`. Do not confuse a sequential PHPUnit test with a concurrency proof. Verify the database engine and transaction behavior. k6 tests should use dedicated test accounts/products, cleanup strategy, bounded traffic, and no real customer/payment data.

## Definition of done

- Architecture and lifecycle are grounded in source.
- Critical ownership, price, stock, idempotency, COD, and lifecycle paths are covered or explicitly reported as gaps.
- Test suite is runnable and documented.
- Regression tests exist for confirmed fixes.
- Test claims are backed by actual execution.
- Audit report and checklist are delivered.
- Remaining risks are clearly stated.
