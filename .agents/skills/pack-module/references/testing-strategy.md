# Pack Testing Strategy and Scenario Matrix

Codex must implement automated tests for every applicable scenario below, map IDs to actual test names/files, execute tests, and report passed/failed/skipped/unimplemented. No placeholders or claims without execution.

Use project testing conventions. Use real database integration for inventory correctness. Use the actual production-compatible database engine for locking/concurrency tests (e.g. MySQL if the project uses MySQL; SQLite is not a substitute for its locking semantics).

## CAT — Catalog CRUD and validation

- CAT-01 create valid draft
- CAT-02 missing title rejected
- CAT-03 missing SKU rejected
- CAT-04 duplicate SKU rejected
- CAT-05 duplicate slug follows existing rules
- CAT-06 invalid/negative price rejected
- CAT-07 invalid compare-at price rejected
- CAT-08 invalid threshold rejected
- CAT-09 invalid category/collection/gender/tag rejected
- CAT-10 valid media association
- CAT-11 update title/description/price/images/categories/tags/collection/gender/threshold
- CAT-12 draft deletion/archive follows lifecycle
- CAT-13 Pack referenced by order remains historically readable
- CAT-14 referential integrity on deletion
- CAT-15 unauthorized admin/customer access rejected
- CAT-16 nonexistent ID and malformed payload return correct errors

## CMP — Composition

- CMP-01 two and three components accepted
- CMP-02 fewer than two components cannot publish
- CMP-03 add/remove/update quantity on draft
- CMP-04 zero/negative quantity rejected
- CMP-05 duplicate component rejected or normalized
- CMP-06 nonexistent/deleted/ineligible product rejected
- CMP-07 Pack-as-component rejected at every entry point
- CMP-08 invalid/missing variant mapping rejected
- CMP-09 mapping must belong to component product and Pack
- CMP-10 component update transaction cannot partially persist
- CMP-11 quantity changes recalculate pricing and availability

## PUB — Publication immutability

- PUB-01 valid Pack publishes
- PUB-02 invalid composition/mapping cannot publish
- PUB-03 add/remove/change quantity/replace component after first publication rejected
- PUB-04 mapping changes after first publication rejected
- PUB-05 direct API, alternate endpoint, service, bulk path cannot bypass
- PUB-06 deactivation/reactivation/unpublishing never unlocks
- PUB-07 title/price/images/categories/threshold remain editable where allowed
- PUB-08 concurrent publish/composition change yields a serialized valid result
- PUB-09 failed publication leaves previous state intact

## VAR — Variant mapping

- VAR-01 valid mapping for every component accepted
- VAR-02 nonexistent variant rejected
- VAR-03 variant belonging to another product rejected
- VAR-04 missing component size makes Pack variant unavailable
- VAR-05 inactive/deleted component variant handled safely
- VAR-06 missing/duplicate mapping rejected
- VAR-07 mapping quantity consistent with composition
- VAR-08 component quantity changes preserve mapping integrity
- VAR-09 variant reorder/label change does not break ID mapping
- VAR-10 referenced Pack variant deletion respects integrity
- VAR-11 post-publication variant operations follow existing lifecycle without changing composition

## PRI — Pricing

- PRI-01 compare-at calculation with two/three components
- PRI-02 component quantity multiplies price
- PRI-03 component price change recalculates compare-at
- PRI-04 selling price remains manual
- PRI-05 correct discount below/equal/above compare-at
- PRI-06 monetary rounding follows project conventions
- PRI-07 zero component price handled
- PRI-08 draft composition changes recalculate compare-at
- PRI-09 published composition stays immutable while component price changes
- PRI-10 concurrent component price updates leave consistent result
- PRI-11 component lifecycle changes do not corrupt historical prices

## INV — Derived stock

- INV-01 equal component stock gives equal Pack stock
- INV-02 limiting component determines Pack stock
- INV-03 zero component stock makes Pack variant unavailable
- INV-04 one sold-out component variant affects only mapped Pack variants
- INV-05 other Pack variants remain available
- INV-06 component quantity 2/3 uses floor division
- INV-07 quantity greater than stock yields zero
- INV-08 total Pack stock sums variant availability
- INV-09 stock change/restoration immediately reflected
- INV-10 multiple Packs sharing a component calculate independently
- INV-11 missing/inactive mapping makes variant unavailable
- INV-12 reservations are reflected per existing stock semantics
- INV-13 low-stock threshold boundary and zero threshold
- INV-14 large composition/variant calculations are correct and efficient
- INV-15 listing/detail availability avoids N+1
- INV-16 no Pack stock column/balance is persisted
- INV-17 client-supplied stock ignored/rejected
- INV-18 stale cache cannot authorize checkout

## SH — Shared inventory

- SH-01 standalone product sale reduces Pack availability
- SH-02 Pack sale deducts every component
- SH-03 Pack A purchase affects Pack B sharing component
- SH-04 Pack A/B/C sharing component remain consistent
- SH-05 mixed standalone components and Packs preserve global stock
- SH-06 Pack then product and product then Pack cannot oversell
- SH-07 Pack purchase failure leaves no partial deduction
- SH-08 restoration increases affected Pack availability
- SH-09 admin stock adjustment reflected in Packs
- SH-10 movement history records actual component movements only
- SH-11 invalid negative stock operation rejected

## CHK — Cart and checkout

- CHK-01 add available Pack
- CHK-02 unavailable Pack/variant rejected
- CHK-03 quantity within/above availability handled
- CHK-04 cart contains Pack plus component product
- CHK-05 cart contains multiple Packs sharing component
- CHK-06 stock changes after cart creation revalidated
- CHK-07 price changes follow existing revalidation policy
- CHK-08 Pack deactivated/deleted after cart creation handled
- CHK-09 composition stable after publication
- CHK-10 checkout failure rolls back all
- CHK-11 retry/idempotency does not duplicate deduction
- CHK-12 COD order, coupon, delivery, totals, snapshots correct

## ORD — Orders and lifecycle

- ORD-01 admin adds Pack; component stock deducted
- ORD-02 increase/decrease Pack quantity
- ORD-03 remove Pack and restore correct components
- ORD-04 change variant restores old and deducts new atomically
- ORD-05 shared component with other order item validated globally
- ORD-06 insufficient stock edit fails without partial changes
- ORD-07 cancellation restores original snapshot
- ORD-08 double cancellation cannot double restore
- ORD-09 delivered/not-picked-up statuses follow existing rules
- ORD-10 Pack composition/catalog changes do not alter historical order
- ORD-11 deleted/renamed Pack/components remain historically readable
- ORD-12 totals and inventory stay consistent after edits
- ORD-13 failed order edit rolls back

## EVT — Events/listeners

- EVT-01 publish/composition events dispatched as appropriate
- EVT-02 component price changes update affected projections
- EVT-03 availability change invalidates targeted caches
- EVT-04 component deletion/deactivation handled
- EVT-05 rollback does not trigger after-commit side effects
- EVT-06 retries do not duplicate effects
- EVT-07 queue failure observable/recoverable
- EVT-08 duplicate event idempotency where required
- EVT-09 unrelated product does not process unrelated Packs
- EVT-10 checkout does not depend on asynchronous listener
- EVT-11 search indexing/catalog projections update correctly

## UI — Admin/storefront

- UI-01 Pack listing/search/filter/pagination
- UI-02 create/edit same design/patterns as Product
- UI-03 draft composition editable
- UI-04 previously published composition read-only with explanation
- UI-05 selector contains products only
- UI-06 mapping/validation correctly represented
- UI-07 prices/discount/stock correct
- UI-08 unavailable variants and out-of-stock Pack disabled
- UI-09 cart/checkout uses backend validation
- UI-10 loading/errors/authorization/responsive behavior

## CON — Concurrency

Use truly concurrent independent workers/connections, coordinated starts/barriers where possible, and real database transactions/locks. Sequential tests do not count.

- CON-01 two customers compete for last Pack
- CON-02 ten customers compete for last Pack
- CON-03 100 requests compete for 10 Packs
- CON-04 Product and Pack compete for last component
- CON-05 Pack A/B sharing component compete
- CON-06 three Packs share component under contention
- CON-07 different component quantities compete
- CON-08 variants share component inventory
- CON-09 same customer duplicate checkout
- CON-10 duplicate idempotency key
- CON-11 Pack purchase versus admin stock adjustment
- CON-12 Pack purchase versus cancellation
- CON-13 concurrent order quantity edits
- CON-14 concurrent Pack variant changes
- CON-15 concurrent component price changes
- CON-16 publish versus composition update
- CON-17 simultaneous cancellation requests
- CON-18 deadlock retry/safe failure
- CON-19 transaction rollback under contention
- CON-20 worker failure during checkout
- CON-21 different compositions do not corrupt each other
- CON-22 high contention on shared component

After each concurrency scenario assert: no negative stock, no overselling, no duplicate deductions/restorations, successful orders have snapshots, failed requests have no partial effects, movement/balance reconciliation, and derived Pack stock is correct.

## LOAD — Traffic/load/stress

Implement real load scripts (prefer k6 if compatible) and reproducible data/setup/cleanup:
- LOAD-01 Pack listing normal traffic
- LOAD-02 listing with 1,000+ Packs
- LOAD-03 listing with availability and query-count/N+1 inspection
- LOAD-04 detail and variant availability
- LOAD-05 detail with many components/variants
- LOAD-06 admin listing/search/filter
- LOAD-07 create/update/publish
- LOAD-08 component price/stock change and invalidation
- LOAD-09 cart and checkout
- LOAD-10 mixed Product/Pack checkout
- LOAD-11 shared component contention
- LOAD-12 order editing/cancellation
- LOAD-13 cache invalidation
- LOAD-14 queue backlog
- LOAD-15 sudden spike
- LOAD-16 sustained traffic/endurance
- LOAD-17 stress ramp to degradation
- LOAD-18 mixed storefront traffic regression

Initial configurable profiles: baseline 10 VUs/5 min; load 50 VUs/15 min; stress ramp 100–200 VUs; spike 10→100 VUs; endurance 50 VUs/60 min. Tune to environment. Never run destructive/stress traffic against production.

Measure throughput, error rate, p50/p95/p99 latency, database query count, transaction duration, lock contention/deadlocks, queue time, and memory where available. Do not invent results or thresholds.

## Final coverage requirement

Create a scenario-to-test mapping (Markdown or machine-readable) with:
- scenario ID
- test name/file
- test type
- execution command
- status: passed / failed / skipped / unimplemented
- reason for any non-passed status

Do not claim full coverage if any applicable scenario is unimplemented or unexecuted. Report environment limitations explicitly.
