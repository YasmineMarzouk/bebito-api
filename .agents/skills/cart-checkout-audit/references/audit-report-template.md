
# Cart & Checkout Audit Report

## Summary

- Branch/commit/date:
- Laravel/PHP/DB versions:
- Cart persistence model:
- Scope and outcome:

## Flow map

- Cart API/frontend:
- Checkout endpoint/service:
- Order/payment/inventory/coupon integration:
- Transaction boundary:
- Idempotency mechanism:

## Schema grounding

- Migrations/current schema inspected:
- Cart table(s), if any:
- Dump/schema discrepancies:
- Money representation and rounding:
- Canonical price/stock source:

## Findings

| ID | Severity | Evidence | Risk | Fix | Regression test |
|---|---|---|---|---|

## Security and ownership

- Guest/customer model:
- Cart/address ownership:
- Admin boundaries:
- Client field tampering:

## Calculation rules

- Subtotal:
- Coupon:
- Shipping/fees:
- Grand total:
- COD payment state:

## Test matrix

| Scenario | Test class::method | Status | Evidence |
|---|---|---|---|

## Concurrency

- DB engine:
- Duplicate checkout:
- Stock race:
- Coupon race:
- Rollback:
- Results:

## Commands/results

- Commands:
- Passed/failed/skipped:

## Remaining risks / blocked cases

- ...

## Data safety

- Confirm production data untouched:
