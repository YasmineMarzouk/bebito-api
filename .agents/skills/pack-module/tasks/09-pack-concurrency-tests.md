# Task 09 — Real Concurrency and Race-Condition Tests

Read CON scenarios in references/testing-strategy.md.

Implement a dedicated suite with genuinely concurrent independent workers/processes/connections, coordinated starts/barriers where feasible, real transactions, and the actual production-compatible DB engine. Sequential requests do not constitute concurrency coverage.

Cover last-unit contention, Product-vs-Pack, shared Packs/components, variants, duplicate/idempotent checkout, stock adjustment, cancellation, order editing, publication/composition races, deadlocks, rollback, and worker failure.

Assert final DB balances, movement reconciliation, order snapshots, no negative stock, no overselling, no duplicate deduction/restoration, no partial failed operations, and derived Pack availability.

Create required test infrastructure and dedicated CI job if needed. Execute suite and report actual results and infrastructure limits.
