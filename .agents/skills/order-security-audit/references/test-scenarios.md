# Order Module Test Scenario Catalog

Tailor every scenario to the actual application. Do not invent features. Mark unsupported scenarios N/A with a reason. Expected results must reflect documented business rules.

Suggested result statuses: PASS, FAIL, BLOCKED, NOT VERIFIED, NOT APPLICABLE.

## A. Checkout and request validation

- [ ] Valid checkout creates exactly one order and the expected order items.
- [ ] Empty/missing cart is rejected.
- [ ] Missing required checkout fields are rejected.
- [ ] Invalid address ID is rejected.
- [ ] Address owned by another customer is rejected.
- [ ] Unknown product ID is rejected.
- [ ] Invalid variant/size ID is rejected.
- [ ] Variant/size does not belong to submitted product is rejected.
- [ ] Quantity zero is rejected.
- [ ] Negative quantity is rejected.
- [ ] Decimal quantity is rejected where only integer units are supported.
- [ ] Malformed quantity (array/object/string) is rejected.
- [ ] Quantity above configured maximum is rejected.
- [ ] Inactive/unpublished product cannot be ordered.
- [ ] Product/variant unavailable cannot be ordered.
- [ ] Product removed/changed during checkout is handled safely.
- [ ] Insufficient stock is rejected.
- [ ] Multiple valid products/variants produce correct line items.
- [ ] Duplicate cart lines are consolidated or rejected according to actual rules.
- [ ] Cart/request size limits are enforced.
- [ ] Unknown or unexpected request fields are rejected or safely ignored.

## B. Server-side pricing and order snapshots

### Coupon-specific cases for this schema

Use `coupons.type`, `percentage`, `fixed_amount`, `min_order_amount`, `usage_limit`, `usage_count`, `expires_at`, and `is_active`. Confirm exact type values and calculation/rounding semantics from migrations and code before writing expected values.

- [ ] Inactive coupon (`is_active = false`) is rejected.
- [ ] Expired coupon (`expires_at`) is rejected; confirm timezone/date-boundary semantics.
- [ ] Minimum order threshold (`min_order_amount`) is enforced using the actual documented subtotal basis.
- [ ] Percentage coupon reads `percentage`; fixed coupon reads `fixed_amount`; irrelevant amount field cannot affect the result.
- [ ] Invalid/unknown `type`, null/zero/negative/out-of-range discount values are handled safely according to schema constraints and business rules.
- [ ] `usage_limit` / `usage_count` behavior is verified from code and enforced; do not assume per-customer limits.
- [ ] Concurrent requests competing for the final permitted coupon use cannot exceed the limit.
- [ ] Coupon application and order creation either both commit or both roll back.
- [ ] Checkout retry cannot increment `usage_count` more than once for the same successful order.
- [ ] Cancellation/rejection does not decrement or restore usage unless the verified business policy says it should; repeated cancellation has no duplicate counter effect.
- [ ] Applied coupon is snapshotted to `orders.coupon_id`, `orders.coupon_code`, and `orders.coupon_discount_amount` consistently.
- [ ] Historical order totals remain unchanged after coupon edits/deactivation/expiry.


- [ ] Client-supplied unit price cannot override catalog price.
- [ ] Client-supplied subtotal cannot override computed subtotal.
- [ ] Client-supplied grand total cannot override computed total.
- [ ] Client-supplied discount cannot override eligibility/calculation.
- [ ] Client-supplied shipping fee cannot override server rules.
- [ ] Client-supplied tax cannot override calculation (if applicable).
- [ ] Negative or extreme monetary values are rejected/ignored.
- [ ] Discount expiration is enforced.
- [ ] Discount eligibility (products, customer, minimum amount, etc.) is enforced.
- [ ] Coupon usage limit remains enforced under concurrency (if applicable).
- [ ] Totals are correct for multiple lines and quantities.
- [ ] Rounding/precision behavior is deterministic.
- [ ] Order item price/label/variant snapshots preserve history after catalog changes.
- [ ] Shipping/address snapshot behavior is correct according to policy.
- [ ] Currency is consistent and cannot be changed by the client.

## C. Customer/admin authorization and IDOR

- [ ] Guest cannot access protected customer order endpoints.
- [ ] Customer A cannot view Customer B's order.
- [ ] Customer A cannot update Customer B's order.
- [ ] Customer A cannot cancel Customer B's order.
- [ ] Customer A cannot access another customer's order items/details.
- [ ] Changing order ID in URL does not bypass ownership.
- [ ] Changing order ID in body/query does not bypass ownership.
- [ ] Customer cannot access admin order routes.
- [ ] Admin without required permission cannot view/modify sensitive order data.
- [ ] Read-only admin cannot perform write actions.
- [ ] Admin with correct permission can perform allowed action.
- [ ] Customer session/cookie cannot authenticate as admin.
- [ ] Admin session/cookie cannot authenticate as customer.
- [ ] Expired/revoked/logged-out session is rejected.
- [ ] Disabled/suspended customer cannot perform restricted actions.
- [ ] Disabled admin cannot perform privileged actions.
- [ ] Customer cannot change `user_id`, owner, tenant, or equivalent fields.
- [ ] Authorization is enforced in backend policies/services, not only UI.

## D. Order lifecycle and transitions

Derive the state machine from source and business rules.

- [ ] Order starts in the correct initial state.
- [ ] Every allowed transition succeeds for authorized actor.
- [ ] Every forbidden transition is rejected.
- [ ] Customer cannot set admin-controlled statuses.
- [ ] Customer cannot set delivered/fulfilled status.
- [ ] Customer cannot confirm cash collection/payment.
- [ ] Cancellation is allowed only in eligible states.
- [ ] Cancellation after dispatch/delivery follows explicit policy.
- [ ] Fulfillment/delivery action requires appropriate admin permission.
- [ ] Duplicate fulfillment/delivery action has no duplicate side effects.
- [ ] Cancelled order cannot be silently edited/reopened.
- [ ] Invalid/unknown status value is rejected.
- [ ] Status changes are auditable where required.
- [ ] Concurrent cancellation and fulfillment produce a valid single final state.

## E. COD-only payment integrity

- [ ] COD is the only supported payment method.
- [ ] Unsupported payment method supplied by customer is rejected.
- [ ] Backend sets/validates payment method; client cannot force another method.
- [ ] New order receives correct unpaid/pending COD state.
- [ ] Customer cannot set `paid`, `payment_status`, `cash_collected`, or equivalent fields.
- [ ] Customer cannot mark order as paid through mass assignment.
- [ ] Only authorized admin/staff can record cash collection.
- [ ] Admin cash-collection action checks current order state.
- [ ] Payment status transition is valid and idempotent.
- [ ] Repeated cash-collection action does not duplicate effects.
- [ ] Delivery does not automatically imply paid unless the documented business rule explicitly says so.
- [ ] Cancellation does not mark order paid.
- [ ] Delivered/unpaid and delivered/paid states are represented consistently.
- [ ] COD payment changes are auditable and expose no sensitive data.

Do not add gateway tests (cards, webhooks, signatures, online refunds) unless an online payment integration is actually introduced and requested.

## F. Inventory and stock integrity

- [ ] Successful checkout decrements correct stock quantity.
- [ ] Stock is variant/size-specific where applicable.
- [ ] Failed checkout leaves stock unchanged.
- [ ] Failed item insert/transaction rolls back stock.
- [ ] Cancellation restores stock according to policy.
- [ ] Duplicate cancellation restores stock at most once.
- [ ] Cancellation of an order that did not reserve/deduct stock does not add stock.
- [ ] Stock never becomes negative.
- [ ] Product/variant deletion does not corrupt order history.
- [ ] Stock adjustment during checkout remains consistent.
- [ ] Reservation expiration/release occurs once (if reservations exist).
- [ ] Inventory accounting reconciles to orders and stock movements.

## G. Idempotency and duplicate requests

- [ ] Double-click/repeated checkout does not create unintended duplicate orders.
- [ ] Retry after client timeout is safe.
- [ ] Idempotency key is scoped to authenticated customer.
- [ ] Same key and same payload returns/references same logical result.
- [ ] Same key with different payload is rejected.
- [ ] Concurrent requests with same key create at most one order.
- [ ] Idempotency storage has a database uniqueness guarantee where needed.
- [ ] Duplicate cancellation has no duplicate stock/status effects.
- [ ] Duplicate admin state transition has no duplicate side effects.

If no idempotency feature exists, report risk and recommend a design; do not pretend a test passes.

## H. Transactions, exceptions, and resilience

- [ ] Order header + items + inventory effects are atomic as required.
- [ ] Failure after order header creation rolls back correctly.
- [ ] Failure during item creation rolls back correctly.
- [ ] Failure during inventory update rolls back correctly.
- [ ] Failure during discount usage update rolls back correctly.
- [ ] Database timeout leaves no partial order.
- [ ] Deadlock is handled safely (bounded retry or safe failure).
- [ ] Retry cannot duplicate order or stock effects.
- [ ] Notification/email failure does not corrupt a committed order.
- [ ] Queue/job failure is observable and recoverable.
- [ ] API error response is safe and consistent.
- [ ] Exceptions do not expose stack traces/secrets in production responses.

## I. API abuse and data exposure

- [ ] Mass assignment of sensitive order fields is blocked.
- [ ] SQL injection probes in filters/search are safe.
- [ ] Stored/reflected XSS in notes/address/name is safely encoded on output.
- [ ] Excessive request body is rejected.
- [ ] Rate limiting applies to checkout and sensitive actions.
- [ ] Pagination is bounded and enforced.
- [ ] Sorting/filter fields are allowlisted.
- [ ] Customer list response contains only that customer's orders.
- [ ] API resource excludes internal fields and other customers' data.
- [ ] Error responses do not reveal secrets, stack traces, SQL, or internal identifiers unnecessarily.
- [ ] Logs avoid tokens, cookies, session IDs, and unnecessary PII.

## J. Performance and query behavior

- [ ] Order list uses pagination.
- [ ] Order list/detail avoids N+1 queries.
- [ ] Indexes support customer ownership and frequent filters.
- [ ] Checkout loads only necessary relationships.
- [ ] Query plans are reviewed for critical order queries at representative data volume.
- [ ] No unbounded export/list endpoint.
- [ ] Database connection and worker limits are documented.
- [ ] Performance regression tests/benchmarks exist where useful.

## K. Concurrency and load (requires separate evidence)

- [ ] Last-stock contention test with multiple independent requests.
- [ ] Same idempotency key concurrently submitted.
- [ ] Concurrent checkout and cancellation.
- [ ] Concurrent checkout and admin stock adjustment.
- [ ] Concurrent admin status updates.
- [ ] Concurrent customer order reads and writes.
- [ ] Load profile and thresholds are defined.
- [ ] k6 run uses isolated staging/test data.
- [ ] Integrity checks run after load (stock, orders, totals, duplicates).
- [ ] Results include latency percentiles, error rate, throughput, and environment.
