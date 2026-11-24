# Task 04 — Derived Pack Inventory

Inspect the authoritative inventory service, reservation semantics, movement ledger, locking, and transaction patterns.

Implement a reusable Pack availability calculation:
- Per Pack variant: minimum floor(component available / required quantity).
- Total Pack stock: sum of variant availability.
- Missing/inactive/invalid required mapping means unavailable.
- Low-stock state based on calculated total and Pack threshold.
- No Pack stock persisted.
- Product inventory changes naturally affect all Packs sharing components.
- Optimize queries; avoid N+1 and unnecessary recalculation.

Integrate Pack deductions/restorations into the existing inventory mechanism, not a parallel mechanism. All critical operations remain synchronous and atomic. Lock authoritative rows deterministically and revalidate inside transaction.

Implement INV and SH tests, including database assertions and regression tests. Execute and report.
