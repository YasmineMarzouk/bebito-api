# Concurrency and Load Testing Guide

## Important distinction

A sequential test that calls checkout many times does not prove concurrency safety. A concurrency claim needs simultaneous independent requests/transactions and verification against the actual database engine and isolation behavior.

## Phase 1: Inspect concurrency controls

Inspect:
- Database engine/version and transaction isolation.
- Stock representation (product, variant, size, warehouse, or stock movement).
- Checkout transaction boundaries.
- Whether stock is locked with `SELECT ... FOR UPDATE`, changed via a conditional atomic update, or protected by another correct mechanism.
- Unique constraints for idempotency.
- Deadlock and retry behavior.
- Cancellation/restock logic.
- Whether tests use separate connections/processes.

Do not prescribe locks blindly. Explain the invariant and verify the chosen mechanism.

## Phase 2: Database-backed concurrency tests

Use the real supported database engine in an isolated test environment. SQLite may not reproduce production locking behavior; if production uses MySQL/PostgreSQL, run concurrency verification against that engine.

Test at least:

1. **Last stock unit**
   - Stock starts at 1.
   - Multiple independent checkout requests attempt to buy 1.
   - Exactly one succeeds.
   - Final stock is 0.
   - No negative stock, duplicate logical orders, or orphan order items.

2. **Limited stock**
   - Stock starts at 5.
   - 20 independent customers attempt to buy 1.
   - At most 5 units/orders succeed, subject to actual order rules.
   - Final inventory and order line quantities reconcile.

3. **Same idempotency key**
   - Submit the same key concurrently from the same customer.
   - At most one logical order exists.
   - All successful responses refer to that order/result.

4. **Cancellation race**
   - Concurrent cancellation requests for one eligible order.
   - Stock is restored at most once.
   - Final status and stock movement are consistent.

5. **Fulfillment/cancellation race**
   - Competing transitions execute concurrently.
   - One valid final state results; no duplicate effects.

6. **Admin stock adjustment race**
   - Checkout and stock adjustment overlap.
   - Invariants hold and result follows documented ordering semantics.

Use processes/connections or a concurrency harness that truly overlaps operations. Avoid sharing one in-memory transaction or one connection and calling it concurrent.

## Phase 3: k6 load suite

Keep load tests outside normal unit/feature test runs. Use synthetic users and dedicated test products/orders.

Suggested profiles (adjust to expected capacity):
- Smoke: 1–5 virtual users, short duration.
- Normal: 20–50 VUs.
- Peak: 100–200 VUs.
- Stress: gradual ramp until threshold/failure.
- Spike: abrupt increase.
- Soak: sustained traffic.

Measure:
- Request rate/throughput.
- p50, p90, p95, p99 latency.
- HTTP error rate and timeout rate.
- Checkout success/rejection distribution.
- Database CPU, connections, locks, deadlocks, and query latency.
- Queue depth and failed jobs if relevant.
- Inventory/order consistency after run.

Define thresholds before execution (e.g. p95 latency and error rate) based on infrastructure and product expectations. Do not invent a universal acceptable threshold.

## Safety requirements

- Run only against an approved isolated staging/load environment.
- Never use production customer data or production order endpoints.
- Use bounded traffic and explicit duration.
- Do not run destructive cleanup outside test-owned records.
- Ensure the script can stop safely.
- Verify inventory/order integrity after every run.
- Document environment (app revision, database engine, instance size, workers, configuration).
- Report unexecuted load tests as NOT VERIFIED.

## k6 script design

A useful script should:
- Load test users from environment variables or safe test fixtures (never commit credentials).
- Authenticate using the application's actual supported flow.
- Generate unique test data or use a controlled product pool.
- Submit checkout with valid payloads.
- Capture expected business rejections separately from server errors.
- Avoid hardcoding secrets.
- Use thresholds that are explicitly agreed.
- Include cleanup instructions restricted to test-created records.
