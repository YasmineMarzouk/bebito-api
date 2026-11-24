
---
name: cart-checkout-audit
description: Audit, secure, complete, and comprehensively test the Bébito Laravel cart and checkout flow from storefront through order creation, coupon application, COD payment, and inventory integration.
---

# Cart & Checkout Audit — Bébito Ecommerce

## Objective

Make the cart and checkout journey dependable, secure, and fully tested for guest/customer storefront use, integrating with the already-audited Authentication, Product/Inventory, Coupon, and Order modules.

Completion means:

- Cart contents and totals are calculated from trusted server-side catalog data, never client prices.
- Checkout validates the complete current state of every line, coupon, address, currency, and stock.
- A successful checkout creates the correct order, order items/snapshots, address snapshot, coupon usage, COD payment record, and inventory effect exactly once according to existing architecture.
- Failures roll back all related changes.
- Admin cannot manipulate checkout/cart state improperly; customers cannot access another customer's cart/order or protected admin functionality.
- Every applicable scenario is represented by automated tests, executed, and documented.

## Mandatory repository and schema discovery

**Do not assume a cart table exists.**

The previously uploaded SQL archive includes:

- `products`
- `product_variants`
- `product_prices`
- `inventory_items`
- `inventory_movements`
- `coupons`
- `orders`
- `order_items`
- `order_addresses`
- `payments`
- `order_status_histories`
- `countries`
- `currencies`
- `customer_addresses`
- `users`, `roles`, and supporting tables

It did not show a `carts` or `cart_items` SQL dump. This does not prove none exists: inspect migrations, code, frontend storage, and APIs to determine whether cart is database-backed, session-backed, token-backed, or client-side.

The uploaded dump contains INSERT/sample data, not complete DDL. **Migrations and current schema are authoritative.** Inspect actual types, indexes, constraints, FKs, defaults, soft deletes, unique keys, and transaction behavior in a safe test environment.

Before modifying code:

1. Map storefront cart UI/state and persistence (local storage/session/API/database).
2. Map every cart and checkout route, controller, FormRequest, service/action, model, resource, middleware, job/listener, and frontend API call.
3. Trace checkout into order creation, order items, address snapshot, coupon usage, COD payment, and inventory mutation.
4. Review existing tests, factories, API contract, and auth/customer identity behavior.
5. Confirm active currency is TND (`currencies.code`, `is_active`, `decimal_places`); TND precision is 3 decimal places.
6. Confirm current sales country is Tunisia (`countries`); reject unsupported/inactive country server-side.
7. Confirm payment is **Cash on Delivery only**. Do not add or test online payment gateways/webhooks as if they exist.
8. Inspect coupon implementation and prior coupon audit. Verify actual columns and rules; never trust coupon discount from client.
9. Read the existing order and product/inventory audit reports/tests. Do not reimplement or bypass their invariants.

## Rules

- Preserve current architecture and API contracts unless a change is required; update frontend and tests together when changing contracts.
- No blind rewrite. Establish baseline and reproduce bugs before fixes.
- Never trust client `product_id`, `variant_id`, quantity, price, total, discount, coupon amount, currency, stock, availability, address ownership, payment status, order status, or internal fields.
- Server recalculates all line totals, subtotal, discount, shipping/fees (if any), and grand total using authoritative DB values and explicit rounding conventions.
- Validate every product and variant belongs together, is active/public/purchasable, has an active TND price, and has sufficient current stock at checkout.
- Customer identity comes from authenticated principal or approved guest flow—not a supplied user ID. Check address ownership.
- Enforce allow-listed request fields; mass assignment cannot set order/payment/stock/price/status/discount/ownership fields.
- Use transactions and appropriate row locks/atomic stock updates. Avoid deadlocks by consistent lock order and bounded retry where supported.
- Checkout idempotency/duplicate-submit handling must be defined and tested. If no key exists, inspect current deduplication strategy and decide/implement a compatible one rather than assuming.
- Keep COD payment state controlled by backend/admin operations. Customer cannot mark paid or choose an arbitrary payment status.
- Preserve historical order snapshots (`product_name`, `sku`, quantity, unit price/total, address/coupon snapshots as implemented).
- Do not invent shipping price, free-shipping threshold, guest checkout, cart expiry, per-customer coupon cap, or other policies. Discover current implementation/requirements; record ambiguity and ask/flag rather than silently adding business rules.
- Do not mutate production data, run destructive migrations, or reset any non-test DB.
- Do not weaken/remove assertions or mark unexecuted tests as passing.
- Do not claim full security or 100% coverage. Report residual risks honestly.

## Phase 0 — Baseline / journey map

Create a baseline map:

- Cart representation, persistence, ownership, session/token lifecycle.
- Add/update/remove/clear cart endpoints/actions and frontend handlers.
- Cart line identity (variant + options), quantity rules, max quantity.
- Cart price/stock display and refresh behavior.
- Checkout request contract and validation.
- Coupon apply/remove/validation lifecycle.
- Address selection/creation and ownership.
- Shipping/fees/tax (if present) and total formula.
- Order creation transaction, stock mutation, coupon usage, COD payment.
- Success page/order confirmation and retry behavior.
- Failure handling, stale cart handling, and cleanup.
- Existing tests and missing scenarios.

Deliver a data-flow diagram or concise sequence map in:

`docs/audits/cart-checkout-flow.md`

## Phase 1 — Cart correctness and security

Audit:

- Add only valid, public, active product variants; variant must belong to product.
- Quantity is integer, positive, bounded by actual business limits; zero/negative/fractional/overflow rejected or handled by defined remove semantics.
- Duplicate lines merge or remain distinct according to established identity; repeated add is not accidental duplicate quantity.
- Update/remove/clear affects only current customer's/session cart.
- Cart isolation: user A cannot read/change user B cart; guest cart cannot be claimed by arbitrary session/user ID.
- Guest-to-login cart merge (if supported) handles duplicates, limits, stale/inactive variants, and ownership safely.
- Client cannot submit price, compare-at price, discount, stock, line total, subtotal, grand total, currency, or internal IDs to override server calculations.
- Cart responses calculate current server price/availability; stale price/stock is clearly handled.
- Inactive/unpublished product, disabled variant, missing price, inactive currency, or out-of-stock variant cannot proceed to checkout.
- Product/variant deletion/deactivation and price/stock changes after adding to cart are handled safely.
- Cart persistence/session expiry/replay is correct for current design.
- Cart endpoints are rate-limited where abuse could cause expensive operations.
- Errors are structured and do not leak internals.

## Phase 2 — Checkout validation and business invariants

At checkout, server must revalidate all lines and derive totals:

- Product and variant exist, relationship is valid, product/variant currently purchasable/public.
- Active TND price is resolved from canonical price source; currency must be active and allowed.
- Current stock suffices for every requested quantity.
- Quantity and total calculations are overflow-safe and use consistent TND precision (3 decimals / actual money storage convention).
- Price changes since cart creation are handled according to explicit policy; never use stale browser price.
- Country is Tunisia and active; address is valid and belongs to customer when using saved address.
- Address fields are validated; snapshot copied to order so later address edits do not rewrite history.
- Coupon is active, unexpired, within global usage limit, meets minimum order amount, and calculation matches coupon type. Inspect exact coupon rules and whether minimum is based on subtotal before/after discount. Do not invent per-customer limits or exclusions.
- Coupon usage increment/reservation is concurrency-safe and rolled back if order creation fails.
- Shipping/fees are calculated server-side according to existing rules (if present).
- Grand total equals server-calculated subtotal minus valid discount plus configured fees; never negative; rounding consistent.
- COD is the only accepted payment method; client cannot set payment status/paid_at.
- Customer cannot submit user_id/order status/payment status/order number/created_at/discount snapshots or inventory fields.
- Empty cart, malformed payload, excessive line count, duplicate variants, invalid address, stale coupon, insufficient stock, and invalid currency fail safely with no partial writes.

## Phase 3 — Atomic order creation / cross-module integration

Trace and test one canonical transaction:

1. Lock/re-read relevant variants/prices/inventory/coupon rows as required.
2. Validate every line before irreversible mutations.
3. Create order and immutable item/address/price snapshots.
4. Create COD payment record in correct initial state.
5. Apply inventory decrement/reservation exactly once via audited inventory workflow.
6. Record inventory movements according to canonical inventory design.
7. Record coupon usage safely.
8. Commit only if all steps succeed.
9. Dispatch notifications/jobs after commit (or use outbox pattern if existing); failed external notification must not undo a committed order or duplicate order on retry.

Any failure at any step must roll back DB changes. Verify exact order transaction boundaries and event/listener side effects. Avoid duplicate stock mutation from order observers/listeners.

## Phase 4 — Idempotency, concurrency, abuse

- Double-click/rapid submit creates at most one order for the same checkout intent according to idempotency contract.
- Retry after timeout returns/reconciles existing result rather than duplicate order/payment/stock/coupon usage.
- Two checkout attempts competing for last stock cannot oversell.
- Two buyers competing for last coupon usage cannot exceed `usage_limit`.
- Concurrent coupon usage and order failure does not consume usage incorrectly.
- Concurrent cart updates do not lose or corrupt lines (if server persisted).
- Concurrent price/stock/product status changes vs checkout follow consistent locking/revalidation rules.
- Repeated add/update/remove requests behave consistently.
- Bound payload sizes, number of cart lines, quantity, and apply rate limits as appropriate.
- Use real independent DB connections/processes and production DB engine semantics in concurrency tests; SQLite is not adequate for row-lock validation.

## Phase 5 — Storefront and admin surface

### Storefront

- Guest/customer cart and checkout only through intended endpoints.
- Authenticated customer's cart/order/address isolation.
- Public API reveals only intended cart/catalog fields.
- No client-side total or price trust.
- Success/failure/validation states are accurate; cart isn't cleared before order is safely committed.
- Checkout retry and refresh do not duplicate order.

### Admin

Cart is normally customer-owned, not admin-editable. Inspect whether admin has cart/order preview/support tools:

- Only authorized staff can inspect customer cart, if feature exists.
- Admin cannot silently mutate cart/checkout data without permission/audit trail.
- Admin order edits after checkout cannot corrupt snapshots or cause double stock/coupon changes.
- Admin payment collection/status actions remain in audited order/payment workflows, not cart endpoints.
- Customer endpoints cannot invoke admin checkout/order operations.

## Phase 6 — Automated test suite (required)

Implement tests—not only a checklist—covering all applicable cases in `references/test-scenarios.md`:

- Unit tests for calculation/validation services.
- Feature tests for all cart/checkout routes.
- Authorization/ownership tests.
- Integration tests for checkout → order → items/address → COD payment → coupon → inventory.
- Transaction rollback and DB integrity tests.
- True concurrency tests on actual DB engine.
- Regression test for every finding.

For every test assert response **and** DB state/side effects (order count, payment count/status, inventory, movement count, coupon usage, cart state, snapshots, notification dispatch). Avoid one generic happy-path CRUD test.

## Phase 7 — Fix, run, report

Prioritize:

- P0: client price/total manipulation, IDOR, unauthorized checkout, overselling, duplicate orders, payment status manipulation, data corruption.
- P1: wrong totals, invalid coupon acceptance, stock/coupon drift, broken atomicity, stale/hidden item checkout.
- P2: validation gaps, inconsistent cart UX/API behavior, cache/query issues.
- P3: noncritical ergonomics/documentation.

For each finding: reproduce → failing regression test → minimal fix → rerun targeted and full suite.

Required deliverables:

- `docs/audits/cart-checkout-audit.md`
- `docs/audits/cart-checkout-test-matrix.md`
- `docs/audits/cart-checkout-flow.md`
- automated tests in project conventions
- updated API docs/contracts if changed

Final report must state:

- Cart storage model and schema found (including whether carts tables exist).
- Checkout calculation formula and money representation.
- Canonical product price/stock source.
- Coupon eligibility/calculation and concurrency handling.
- Address ownership/country behavior.
- COD payment lifecycle.
- Transaction and idempotency guarantees.
- Admin/storefront coverage.
- Exact test commands, DB engine, passed/failed/skipped counts.
- Concurrency scenarios and outcomes.
- Findings fixed and residual risks/blocked tests.
- Confirmation no production data was modified.

**Completion criterion:** every applicable scenario is automated and executed; PASS only with evidence, BLOCKED/N/A with documented justification. Never state “fully secure” or “100% covered.”
