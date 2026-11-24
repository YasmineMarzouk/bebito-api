# Product & Inventory Audit Report

## Executive summary
- Scope:
- Date / branch / commit:
- Laravel/PHP/DB versions:
- Outcome:
- P0/P1/P2/P3 findings:

## Schema grounding
- Migrations inspected:
- Actual schema constraints:
- Dump discrepancies:
- Canonical price source:
- Canonical inventory source:
- Movement schema and semantics:

## Architecture map
- Admin routes/controllers/services:
- Storefront routes/controllers/services/resources:
- Models/relationships:
- Inventory mutation paths:
- Price mutation/read paths:
- Asset storage/ownership:
- Cache/jobs/listeners:

## Findings
| ID | Severity | Area | Evidence | Risk | Fix | Regression test |
|---|---|---|---|---|---|

## Admin coverage
- Permission matrix:
- Routes tested:
- Unauthorized mutation invariants:

## Storefront coverage
- Visibility and read endpoints:
- Client field tampering:
- Price/stock/variant ownership:

## Test matrix
| Scenario | Test class::method | Status | Command/output |
|---|---|---|---|

## Concurrency and performance
- DB engine:
- Race scenarios:
- Load profiles:
- Results:

## Commands executed
- ...
- Pass/fail/skipped counts:

## Residual risks / blocked tests
- ...

## Data safety
- Confirm no production DB/data touched:
- Any test data cleanup:
