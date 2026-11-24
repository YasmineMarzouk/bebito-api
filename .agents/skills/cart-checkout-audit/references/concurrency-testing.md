
# Cart & Checkout Concurrency Test Guide

Use isolated test/staging only.

Test with the production DB engine/version and independent connections/processes; SQLite cannot validate MySQL/PostgreSQL row-lock semantics.

## Required races

### 1. Duplicate checkout

Same customer/intent sends 2–20 concurrent requests.

Assertions:

- One logical order.
- One payment.
- One inventory effect.
- One coupon redemption.
- Consistent response/recovery behavior.

### 2. Last stock unit

Stock = 1, several independent customers checkout quantity = 1.

Assertions:

- At most one succeeds.
- Stock never negative.
- Order/movement totals reconcile.

### 3. Stock = 5 race

Multiple requests quantity = 1.

Assertions:

- Total accepted quantity <= 5.
- Final stock >= 0.
- Initial stock = final stock + consumed/reserved quantity.

Adapt to the actual reservation semantics.

### 4. Last coupon usage

`usage_limit = 1`, `usage_count = 0`, two eligible concurrent checkouts.

Assertions:

- At most one committed use.
- Failed checkout does not consume usage.

### 5. Checkout rollback

Force failure after stock or coupon operation.

Verify all DB changes rollback.

### 6. Checkout vs admin inventory update

Run authorized admin adjustment and checkout simultaneously.

Assertions:

- No lost update.
- No negative stock.
- No movement/quantity mismatch.

Behavior follows defined locking and transaction rules.

### 7. Checkout vs price/status change

Race checkout against product/variant price or status change.

Verify consistent revalidation and no stale/unpurchasable checkout.

### 8. Cart mutation race

Concurrent add/update/remove (only if persisted/server-managed).

Verify no corrupted lines or inconsistent totals.

## Evidence to report

- DB engine/version.
- Workers and initial data.
- Exact test command/script.
- Success/failure counts.
- Final inventory and coupon invariants.
- Orders, payments, order items, movements, coupon usage before/after.
- Deadlock/timeout behavior and retry count.

Do not call a sequential loop a concurrency test.
