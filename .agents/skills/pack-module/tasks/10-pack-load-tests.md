# Task 10 — Traffic, Load, Stress, Spike, and Endurance Tests

Implement actual load scripts (prefer k6 if compatible), test data setup/cleanup, authentication, configurable profiles, thresholds, and execution docs.

Cover LOAD scenarios in references/testing-strategy.md: listing/detail, availability, large catalog, admin search, CRUD/publication, component changes, cart/checkout, mixed Product/Pack traffic, shared inventory contention, order editing/cancellation, cache/queue behavior, spike, stress, sustained/endurance.

Measure throughput, error rate, p50/p95/p99, query counts/N+1, transaction time, lock/deadlock behavior, queue latency, and memory where available.

Use a dedicated non-production environment and test accounts/inventory. Never stress production.

Execute at least a smoke profile if environment permits. Clearly separate implemented scripts from executed profiles; report real results and bottlenecks, never invented metrics.
