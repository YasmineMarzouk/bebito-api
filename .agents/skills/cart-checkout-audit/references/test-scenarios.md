
# Cart & Checkout Automated Test Scenario Catalog

Add one test matrix row for each applicable scenario. Test both response and DB state.

## A. Cart operations and persistence

- Add valid active/purchasable variant.
- Add nonexistent product/variant; variant-parent mismatch.
- Add inactive/unpublished product or disabled variant.
- Add out-of-stock/missing-inventory variant.
- Quantity zero, negative, fractional, string, huge, overflow, and valid boundaries.
- Repeated add same variant: merge or distinct behavior matches contract.
- Update quantity and remove line.
- Remove nonexistent line and clear empty cart safely.
- Cart line count/payload bounds.
- Cart persists/reloads according to storage model.
- Guest cart behavior and guest-to-login merge if supported.
- User A cannot read/update/remove User B cart.
- Guest/session identifier cannot be spoofed to access another cart.
- Product/variant deleted, disabled, price changed, or stock changed after cart add.
- Cart response uses server price and current availability.
- Client price/stock/discount/total/currency fields ignored or rejected.
- Cart API errors are structured; no internal leakage.

## B. Checkout validation

- Valid cart checkout succeeds.
- Empty cart rejected.
- Invalid/missing variant/product rejected.
- Variant does not belong to product.
- Product/variant no longer purchasable.
- Insufficient stock and last-unit edge.
- Missing/inactive price or currency.
- Inactive USD/unsupported currency rejected; active TND accepted.
- Price changed after cart creation; authoritative price policy applied.
- Product/variant deactivated after cart creation.
- Invalid quantity, duplicate line, too many lines, oversized payload.
- Invalid user_id/order status/payment status/order number/internal field injection.
- Customer identity from authenticated user; user ID spoofing denied.
- Guest checkout only if explicitly supported; otherwise guest checkout rejected.
- Address missing/invalid; saved address owned by another user denied.
- Tunisia-only country validation; inactive/foreign country rejected.
- Address snapshot persists even if customer address later changes.
- Validation failure creates no order/payment/items and no stock/coupon mutation.

## C. Price and total integrity

- Subtotal calculated from authoritative current variant prices.
- Line total = server unit price × validated quantity.
- Client unit price, subtotal, discount, shipping, total, currency, and price ID tampering rejected/ignored.
- TND precision (3 decimals) maintained; no float rounding errors.
- Negative/overflow values prevented.
- Compare-at price is display-only unless existing business logic explicitly applies a discount.
- Product/variant price and cart/checkout totals reconcile.
- Coupon fixed/percentage math, min order, active status, expiry, usage limit, rounding, and maximum discount rules follow implementation.
- Coupon code case/whitespace normalization follows contract.
- Invalid, expired, inactive, exhausted, below-minimum coupon rejected.
- Coupon removed/replaced recalculates total server-side.
- Coupon usage count increments only on committed checkout and rolls back on failure.
- Coupon last-use concurrency: usage_limit=1, two concurrent checkouts; at most one valid redemption.

## D. COD and order creation

- Only COD payment method accepted.
- Client cannot set payment status, paid_at, amount, method, or currency.
- Correct COD payment row created with expected initial state and exact order amount/currency.
- Exactly one order/payment per checkout intent.
- Order number uniqueness under concurrent checkout.
- Order items store correct product name/SKU/variant/quantity/unit price/total snapshots.
- Order address snapshot is correct.
- Order coupon snapshots and discount amount correct.
- Order status initial state follows existing order lifecycle.
- Order status history created as designed.
- Cart is cleared only after successful commit (or remains recoverable by contract).
- Confirmation response contains only authorized order data.

## E. Atomicity and rollback

Inject failure at each critical stage (using test hooks/mocks where safe):

- Order row creation.
- Order item creation.
- Address snapshot.
- Payment row.
- Inventory decrement/movement.
- Coupon usage update.
- After-commit notification dispatch.

Assert DB transaction rolls back all DB mutations on pre-commit failure; stock and coupon usage unchanged; no partial order/payment/items.

External notification failure after commit does not duplicate or erase order.

## F. Ownership and access control

- Unauthenticated checkout behavior per contract.
- Authenticated customer can checkout own cart/address.
- User A cannot inspect/change User B cart/address/order.
- Customer cannot access admin cart tools or mutate catalog/inventory.
- Admin cart inspection only if feature exists and permission is required.
- Admin cannot change payment/stock through a cart endpoint.
- IDOR across cart IDs, line IDs, address IDs, order IDs.
- Unauthorized requests leave state unchanged.

## G. Idempotency, retries, concurrency

- Double-click/parallel same checkout request.
- Retry after simulated timeout.
- Same idempotency key/same payload returns consistent result.
- Same key/different payload rejected.
- Missing key behavior follows explicit dedup strategy.
- Concurrent last-stock-unit checkout: no oversell.
- Concurrent coupon last-use checkout: usage limit never exceeded.
- Concurrent cart update/add/remove maintains consistency (if persisted).
- Checkout vs admin stock adjustment.
- Checkout vs product/variant deactivation.
- Deadlock retry bounded and no duplicate side effects.
- Repeated job/listener/event does not double-decrement stock or coupon.
- Order/payment/inventory/coupon state reconciles after race.

## H. Storefront UX/API behavior

- API response schema and errors match frontend contract.
- Cart badge/line count and totals match server state.
- Loading/double submit prevention does not replace server idempotency.
- Validation errors map to relevant fields/messages.
- Stock/price changes are communicated and checkout can recover.
- Network timeout/retry doesn't create duplicate order.
- Cart is not cleared on failed checkout.
- Success page cannot be used to access another order by changing ID.
- Locale/product labels and image fallback safe (where relevant).

## I. Reliability and regression

- DB/storage/queue failures are handled safely.
- Transaction rollback on exception.
- Rate limiting and abuse controls.
- Query performance for large cart and checkout.
- Every discovered bug has regression test.
- Full relevant suite passes.

## Coverage status

- PASS only when automated test executed and passed.
- FAIL if behavior wrong.
- BLOCKED with concrete environment limitation.
- N/A with documented reason.

No scenario may be marked PASS based only on manual inspection or code reading.
