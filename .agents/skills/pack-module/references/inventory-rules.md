# Pack Inventory Rules and Invariants

## Source of truth

Standalone product/variant inventory is authoritative. Pack inventory is a derived availability view. Never persist a Pack stock quantity, reserved balance, or independent Pack inventory movement.

## Calculation

For each Pack variant, calculate:

`pack_available = min(floor(component_available / component_quantity))`

Use authoritative available stock semantics from the existing inventory system, including reservations if the system supports them. Pack total stock is the sum of Pack variant availability.

A missing/inactive/unmapped required component variant makes that Pack variant unavailable. Component quantity is always respected.

## Shared inventory

All operations use the same inventory mechanism:
- standalone product checkout
- Pack checkout
- admin order creation/editing
- cancellation/restoration
- returns/restocking
- inventory adjustments

Pack purchase must deduct every required component variant atomically. If any component fails validation, roll back all deductions and order changes.

Product purchase changes Pack availability naturally through the shared stock source. Packs sharing components must all reflect the same inventory.

## Transactions and locking

- Lock authoritative inventory rows in a deterministic order to reduce deadlocks.
- Revalidate stock inside the transaction after locks are acquired.
- Do not trust earlier availability reads or client stock.
- Prevent negative stock and overselling.
- Use existing deadlock retry strategy where applicable.
- Ensure duplicate requests/idempotency cannot double-deduct.
- Restore inventory exactly once, using order snapshots and existing lifecycle rules.
- Do not let asynchronous events/listeners perform critical stock mutations after checkout.

## Required invariants

1. No component inventory becomes negative.
2. Successful Pack orders deduct all and only required component quantities.
3. Failed Pack operations cause no partial deductions.
4. Product and Pack sales cannot oversell shared components.
5. Inventory movements reconcile with inventory balances.
6. Cancellation/restoration cannot happen twice.
7. Historical Pack orders do not depend on current composition.
8. Pack stock is never persisted as an independent inventory balance.
9. Every successful order has valid component snapshots.
10. Concurrent operations preserve all above invariants.

## Caching

Caching is optional and must be justified by measurement. It is disposable, targeted, and correctly invalidated. Cached stock never authorizes checkout; checkout always revalidates under transactional locks.
