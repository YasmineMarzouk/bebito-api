# Concurrency and Load Testing — Product & Inventory

## Principles
- Run only against isolated test/staging DB and test services. Never target production.
- Use the same DB engine/version as production for lock/transaction tests; SQLite is not a substitute for MySQL/PostgreSQL locking semantics.
- Use independent DB connections/processes for true concurrency; a sequential loop is not a race test.
- Record DB engine, worker count, scenario, initial state, command/script, output, and result.
- Avoid arbitrary capacity claims. Load tests establish observed behavior under a stated setup.

## Required race scenarios

### 1. Last inventory unit
Initial state: one variant, `inventory_items.quantity = 1`, one unit requested by each of N concurrent checkout workers (e.g. 10–30).
Assertions:
- successful purchases/reservations total <= 1;
- inventory never negative;
- failed attempts return controlled insufficient-stock/conflict response;
- movements and order item quantities reconcile exactly;
- no duplicate decrement due to retry/listener/job.

### 2. Several units
Initial stock = 5; concurrent workers each request one.
Assertions: total accepted quantity <= 5, final stock >= 0, and initial stock = final stock + consumed/reserved quantity (adapt to reservation semantics).

### 3. Concurrent admin adjustment and checkout
Run authorized admin adjustment and checkout simultaneously.
Assertions: no lost update, negative stock, stale overwrite, or movement/quantity mismatch. Define expected ordering/semantics based on locking and transactions.

### 4. Duplicate adjustment request
If API supports idempotency, submit same key concurrently and assert one effective adjustment and one movement. If it does not support idempotency, document risk and do not invent a contract.

### 5. Product/variant deactivation vs checkout
Race a product or variant status change against checkout. Outcome must follow transaction/locking rule; no purchase of an already invalid item after the agreed consistency point.

### 6. Primary image race
If one primary image per resource is intended, concurrently set different images primary. Assert invariant after both complete and deterministic final state.

## Load profiles (non-production)
- Smoke: 1–5 VUs, short duration, functional correctness.
- Normal: 20–50 VUs, browse/search/detail plus small checkout mix.
- Peak: 100–200 VUs only if environment is sized and approved; verify error rate, latency, DB contention, query count.
- Stress: increase gradually to find failure mode; do not interpret as production capacity.
- Soak: sustained modest traffic to find memory/cache/queue/resource leaks.

## Tools
Use existing project tools first. If adding k6 or another tool, keep scripts in `tests/performance/` or existing convention and document setup. Do not add a dependency solely for a test that can be covered by existing infrastructure.

## Report
Report:
- exact environment/DB engine;
- worker/VU count and duration;
- response/error counts and latency percentiles if available;
- invariant assertions;
- any failure and reproduction instructions.
