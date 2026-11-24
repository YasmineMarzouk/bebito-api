# Product & Inventory Test Matrix

| ID | Area | Scenario | Test class::method | Status (PASS/FAIL/BLOCKED/N/A) | Evidence / reason |
|---|---|---|---|---|
| CAT-001 | Storefront | Public product listing | | | |
| ADM-001 | Authorization | Unauthorized product update | | | |
| VAR-001 | Variants | Parent/variant mismatch | | | |
| PRC-001 | Pricing | Client price tampering | | | |
| INV-001 | Inventory | Negative stock rejected | | | |
| INV-002 | Concurrency | Last unit race | | | |
| AST-001 | Assets | Cross-product asset attach | | | |
| REL-001 | Relationships | Unauthorized category attach | | | |
| INT-001 | Checkout | Price/stock revalidation | | | |
| REG-001 | Regression | Finding-specific regression | | | |

Add one row per scenario from `references/test-scenarios.md`. Do not collapse multiple scenarios into a single broad CRUD test.
