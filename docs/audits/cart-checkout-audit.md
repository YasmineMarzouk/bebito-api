# Cart & Checkout Audit

## Scope and evidence

Audited the storefront cart, checkout/quote routes, order creation, coupon service, inventory reservation, COD payment, customer order listing, migrations, factories, and existing tests. Schema authority was the Laravel migrations. No production data, non-test database, or migration was run.

The storefront cart is browser `localStorage`, not a database, session, or token cart. It sends only variant IDs and quantities to `/api/v1/store/checkout`; quote requests use `/api/v1/store/checkout/quote`. Checkout remains intentionally available to guests and authenticated customers.

## Canonical data and calculation

- Price: `products.price_amount`, represented as integer TND minor units. `product_prices` is synchronized catalog data, not the checkout price source.
- Stock: `inventory_items.quantity`, locked and re-read in `CheckoutInventoryRepository`; `products.stock_quantity` is its derived summary.
- Formula: `subtotal = Σ(product.price_amount × quantity)`; `total = max(0, subtotal + server-calculated shipping − server-calculated coupon discount)`.
- Currency and country: active `TND` at 3 decimal places and active `TN` are required inside the transaction. Client currency/country fields are not accepted.
- Coupon: normalized code is locked, checked for active/expiry/global limit/minimum amount, then incremented atomically only in the successful order transaction.
- Payment: every new payment is `cash_on_delivery` and `pending`; no online-payment route or webhook exists.

## Findings and remediation

| ID | Severity | Finding | Remediation |
|---|---|---|---|
| CC-001 | P0 | Duplicate submits/retries could create multiple orders, payments, stock decrements, movements, and coupon redemptions. | Added required UUID `Idempotency-Key`, durable unique key/fingerprint columns, deterministic replay, changed-payload conflict handling, and retry-safe transaction behavior. |
| CC-002 | P1 | Checkout hard-coded `TN` and defaulted TND arithmetic without proving active country/currency state. | Locked and verified active `TN` and active three-decimal `TND`; configuration failure aborts and rolls back the transaction. |
| CC-003 | P2 | Line payload count was unbounded. | Capped checkout requests at 25 lines, retaining server-side quantity and distinct-variant validation. |

Existing strengths retained: client totals/prices are ignored; catalog/inventory is re-read server-side; coupons use a locked/conditional usage update; checkout writes order, snapshots, payment, stock and movement records in one transaction; customer order listing is customer-scoped; checkout and quote routes are rate limited.

## Transaction and concurrency

The transaction locks variants/inventory in a stable variant-ID order, revalidates purchasability and stock, creates immutable order artifacts, conditionally claims coupon usage, and records the inventory effect. It retries deadlocks up to three times. The database unique idempotency key protects concurrent same-key requests even when two transactions initially see no order; the losing transaction rolls back and resolves the committed result by fingerprint.

True race tests are not verified: `phpunit.xml` uses in-memory SQLite and the local MySQL Compose service was not running. SQLite is insufficient evidence for MySQL row-lock behavior. Run last-unit stock, last coupon use, duplicate-key, and admin-stock-change races against an isolated MySQL 8 test database before release.

## Test evidence

- Focused checkout/catalog suite: `docker compose run --rm --no-deps app php artisan test --compact tests/Feature/CreateCheckoutTest.php tests/Feature/Catalog/ProductCatalogInventoryTest.php` — **23 passed, 144 assertions**.
- A first focused run surfaced a regression where unavailable catalog items returned 503 before availability validation when test store defaults were absent. The validation ordering was corrected; the final focused run above passed.
- PHP formatting: `vendor/bin/pint --format agent` completed. Formatter-only edits outside this scope were restored.

The full application-suite command was launched, but the runner did not return a final summary; it is not claimed as passing. The storefront build is also not verified: the workspace lacked Vite, and dependency installation did not complete in the available environment. No claim in this report treats blocked MySQL concurrency scenarios as passing.

## Residual risks

- Production-engine locking/deadlock/unique-conflict behavior needs the blocked independent-connection test suite.
- Client cart persistence is local to a browser; there is no server-side cross-device cart, guest-to-login merge, or cart recovery policy. These are absent capabilities, not security bypasses in the present API.
- The success screen displays only the order number returned by checkout. There is no public order-detail endpoint, which prevents order-number IDOR but also means guest order lookup is not implemented.
