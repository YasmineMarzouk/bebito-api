
# Cart & Checkout Test Matrix

| ID | Domain | Scenario | Test class::method | Status | Evidence |
|---|---|---|---|---|---|
| CART-001 | Cart | Add valid variant | | | |
| CART-002 | Ownership | User A cannot access User B cart | | | |
| CHK-001 | Checkout | Valid checkout | | | |
| CHK-002 | Integrity | Client price tampering | | | |
| CHK-003 | Address | Foreign address ID denied | | | |
| CPL-001 | Coupon | Invalid/exhausted coupon rejected | | | |
| COD-001 | Payment | COD only / client paid status rejected | | | |
| TXN-001 | Atomicity | Failure rolls back order+stock+coupon | | | |
| IDE-001 | Idempotency | Concurrent duplicate checkout | | | |
| INV-001 | Concurrency | Last stock unit race | | | |
| CPL-002 | Concurrency | Last coupon usage race | | | |

Add one row for every applicable scenario in `references/test-scenarios.md`.

A broad happy-path test does not cover negative, ownership, tampering, rollback, and race cases.
