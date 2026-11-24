# Cart & Checkout Test Matrix

Status meanings: **PASS** is an executed automated test; **BLOCKED** needs the production database engine or unavailable infrastructure; **N/A** is absent from the current route contract.

Storefront build: **BLOCKED**. `vite` was absent and `npm install` did not complete in the available environment, so no frontend build result is claimed.

| Area | Scenario | Evidence | Status |
|---|---|---|---|
| Cart storage | Local persistence and line merging/bounds | `CartContext.tsx` implementation review | N/A API test: client-only cart |
| Cart storage | Database/session cart ownership and guest-to-login merge | No cart schema or server cart endpoint exists | N/A |
| Checkout input | Empty, bad, duplicate, missing, excessive, or malformed lines | `CreateCheckoutRequest`; `CreateCheckoutTest` duplicate and max-line tests | PASS |
| Checkout input | Quantity range 1–99 and 25-line cap | `CreateCheckoutRequest`; `CreateCheckoutTest::test_checkout_rejects_a_cart_with_more_than_twenty_five_lines_without_writes` | PASS |
| Catalog integrity | Missing/unavailable/retired variant and insufficient stock | `CheckoutInventoryRepository`; `CreateCheckoutTest`; `ProductCatalogInventoryTest` | PASS |
| Price integrity | Server price only; client price/stock fields ignored | `CreateCheckoutTest::test_checkout_ignores_client_supplied_price_fields_and_uses_the_authoritative_catalog_price` | PASS |
| Price freshness | Current catalog price is read at checkout | `CheckoutInventoryRepository::itemsFor`; checkout integration tests | PASS |
| Currency | Active TND with three decimal places is required | `CreateCheckoutUseCase::checkoutCurrency`; successful checkout test | PASS |
| Country | Active Tunisia (`TN`) is required and failure rolls back | `CreateCheckoutTest::test_checkout_rolls_back_when_tunisia_is_inactive` | PASS |
| Address | Request validation and immutable shipping snapshot | `CreateCheckoutRequest`, `OrderRepository::createCheckout`; happy-path DB assertions | PASS |
| Saved addresses | Customer address selection/ownership | No saved-address checkout field or endpoint | N/A |
| Customer identity | Guard identity prevails over injected user/payment/currency fields | `CreateCheckoutTest::test_checkout_uses_the_authenticated_customer_and_ignores_client_payment_and_currency_fields` | PASS |
| Guest checkout | Existing guest path creates a valid COD order | `CreateCheckoutTest::test_a_guest_can_checkout_available_product_variants` | PASS |
| COD | Pending cash-on-delivery payment only; client payment state ignored | Same authenticated-customer regression test | PASS |
| Coupon | Case normalization, expired/inactive/limit rejection, calculation preview | `CouponApplicationServiceTest`, `CreateCheckoutTest` quote and limit tests | PASS |
| Coupon | Committed checkout increments usage once and snapshots discount | `CreateCheckoutTest::test_checkout_redeems_a_coupon_and_records_an_inventory_movement` | PASS |
| Atomicity | Coupon-limit failure rolls back order, stock, and movement | `CreateCheckoutTest::test_checkout_rolls_back_stock_and_order_when_a_coupon_has_reached_its_usage_limit` | PASS |
| Order snapshots | Item, address, coupon, COD payment, status-history creation | `OrderRepository::createCheckout`; `CreateCheckoutTest` | PASS |
| Inventory | Canonical inventory decrement plus one `order_placed` movement | `CreateCheckoutTest::test_checkout_redeems_a_coupon_and_records_an_inventory_movement` | PASS |
| Idempotency | Missing key denied before writes | `CreateCheckoutTest::test_checkout_requires_an_idempotency_key_before_it_can_mutate_inventory` | PASS |
| Idempotency | Same key/same request returns one order and one effect | `CreateCheckoutTest::test_checkout_retry_with_the_same_key_is_idempotent_and_rejects_different_details` | PASS |
| Idempotency | Same key/different request is denied | Same idempotency regression test | PASS |
| Customer order access | Customer list is scoped to authenticated user | `StaffAuthorizationTest::test_customer_order_listing_is_scoped_to_the_authenticated_customer` | PASS |
| Admin cart access | Admin cart inspection/mutation | No admin cart route exists | N/A |
| Rate limits | Checkout and coupon-quote limits are registered | `AppServiceProvider` route limiters | PASS by configuration review |
| True duplicate-submit race | Independent MySQL connections and unique-key recovery | SQLite cannot validate row locks; local MySQL Compose service was unavailable | BLOCKED |
| Last-stock race | Independent MySQL connections; no oversell | SQLite cannot validate row locks; local MySQL Compose service was unavailable | BLOCKED |
| Last-coupon-use race | Independent MySQL connections; usage never exceeds limit | SQLite cannot validate row locks; local MySQL Compose service was unavailable | BLOCKED |
| Checkout versus admin inventory/status change | Independent production-engine connections | No isolated MySQL test environment was running | BLOCKED |
| Notification after commit | Checkout notification/job behavior | No notification or job dispatch exists in this path | N/A |

Executed focused command: `docker compose run --rm --no-deps app php artisan test --compact tests/Feature/CreateCheckoutTest.php tests/Feature/Catalog/ProductCatalogInventoryTest.php` — **23 passed, 144 assertions**.
