# Task 05 — Pack Cart, Checkout, and Orders

Integrate Packs into existing cart, checkout, order item, order editing, cancellation, and restoration mechanisms.

Requirements:
- Checkout validates Pack/variant/quantity using authoritative stock under transaction locks.
- Deduct every component atomically; any failure rolls back all.
- Pack order item preserves Pack identity/pricing/variant and component composition/variant/quantity snapshots.
- Historical order operations use snapshot, never current Pack composition.
- Admin add/remove/quantity/variant edits use existing coherent order-editing mechanism.
- Cancellation/restoration is exactly-once and follows current order status rules.
- Mixed Product/Pack carts and shared components cannot oversell.
- Preserve COD, coupons, delivery, totals, and existing order conventions.

Implement CHK/ORD scenarios and existing regression tests. Execute and report.
