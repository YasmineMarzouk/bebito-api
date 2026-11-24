# Admin Operations Security & Reliability Audit

Audit date: 2026-11-10. Scope was the Laravel API and its React dashboard callers. No production data or schema was changed.

## Baseline and environment

- Backend: Laravel 13.39.0, Sanctum 4.3.3, PHPUnit 12.5.42.
- Test configuration: SQLite `:memory:` from `phpunit.xml`; safe for route, validation, and persisted-effect tests, but not proof of MySQL locking behavior.
- Local runtime configuration: MySQL. `php artisan migrate:status` reports two pending, uncommitted migrations: `2026_11_09_000001_preserve_catalog_inventory_history` and `2026_11_09_165029_add_checkout_idempotency_to_orders_table`. They were not run.
- The worktree had substantial pre-existing, staged, modified, and untracked audit work. This audit preserved it.

## Administrative inventory

All 39 `api/v1/admin` routes were inventoried from `php artisan route:list --path=admin --json`. The 36 business routes below uniformly use `auth:admin`, `active.account:admin`, and a fail-closed `staff.permission:<section>,<action>` check. The middleware obtains the principal from the `admin` guard and rejects non-staff or absent permissions.

| Domain | Routes / operations | Validation and implementation | Data / side effects |
|---|---:|---|---|
| Admin authentication | 3: CSRF cookie, login, logout, identity | Login throttled; admin provider accepts staff only | Isolated admin session context |
| Categories | 7: list, read, create, update, delete, homepage selection, banner upload | Category requests; `CategoryRepository`; write/execute permission separation | Categories, translations, assets, homepage flags |
| Collections | 6: list, read, create, update, delete, banner upload | Collection requests; `CollectionRepository` | Collections, translations, assets |
| Products / inventory | 8: list, read, create, update, delete, homepage selection, image delete, primary image | Product and primary-image requests; `ProductRepository` transactions | Product, variants, prices, inventory, movements, assets, relations |
| Coupons | 5: list, read, create, update, delete | Coupon requests; `CouponRepository` and coupon service | Coupon rules and usage limits |
| Customers | 6: list, read, create, update, block, delete | Customer requests and use cases | User, customer profile, roles, account state |
| Orders | 2: list, apply/replace coupon | Coupon-ID request; locked order/coupon workflow | Order financial snapshot, pending COD payment, coupon usage |
| Settings / translation | 2: update settings, French-to-English translation | Settings/translation requests | Settings rows; translation-service invocation |

`GET /api/v1/settings` is public storefront configuration rather than an admin route. Its response contains contact, social, shipping, and active-currency configuration only; no credential or account data was found.

## Confirmed findings

| ID | Severity | Status | Endpoint | Evidence and remediation | Regression coverage / residual risk |
|---|---|---|---|---|---|
| AO-001 | High | Fixed | `PUT /api/v1/admin/orders/{order}/coupon` | A staff member with `orders:write` could apply/replace a coupon after an order was delivered, cancelled, or not picked up. The operation rewrote the order total and pending COD payment and changed coupon usage. The use case now locks the order and permits the mutation only while its status is `pending`; otherwise it returns 422 with code `4203`. | `CouponManagementTest::test_an_administrator_cannot_change_a_coupon_after_an_order_has_left_the_pending_state` proves no order, payment, or coupon-usage change. |
| AO-002 | Medium | Unresolved | Product/category/collection uploads and deletion | Filesystem writes/deletes are not transactionally coupled to asset-record writes: a database failure after a store can orphan a file, and a database failure after file deletion can leave an asset record pointing at a missing file. | Requires a deliberately designed compensation/after-commit strategy and failure-injection coverage; not safe to change blindly in this already modified worktree. |
| AO-003 | Medium | Unresolved | Settings, customer, coupon, and order coupon mutation | Inventory movements record trusted actors, but no general privileged activity record exists for customer block/delete, coupon create/update/delete, settings changes, or post-checkout coupon changes. | A proportional audit-log schema/retention/access design is needed before implementation; do not repurpose order-status history for unrelated mutations. |
| AO-004 | Medium | Not verified | Homepage selection and inventory/order races | Transactions and row locks are present in relevant repositories, but the executed suite used SQLite. The local MySQL runtime has pending migrations, so an independent-connection race test was not run. | Run against an isolated, migrated MySQL database before release; do not infer MySQL locking behavior from SQLite. |

## Authorization and validation evidence

- `AdminOperationsSecurityTest` sends direct requests to every one of the 36 protected business endpoints. They all reject unauthenticated requests with 401 and authenticated staff with no assigned permission with 403.
- Existing feature tests exercise the owner/permission model, catalog asset ownership, customer management, coupon validation, and checkout/inventory invariants.
- Admin routes use Form Requests or closed DTO input paths for write operations. Product relation IDs and homepage selections require existing, distinct identifiers; uploads are size- and image-type constrained; coupon, settings, customer, and order-coupon inputs have explicit bounds/types.
- The actual dashboard calls the same API surface from its authentication, catalog, coupon, customer, and settings clients. Its visible routes do not constitute authorization; all enforcement above is backend middleware.

## Verification report

| Command | Engine | Result |
|---|---|---|
| `docker compose exec -T app php artisan test --compact tests/Feature/StaffAuthorizationTest.php tests/Feature/Promotions/CouponManagementTest.php tests/Feature/Customer/CustomerManagementTest.php tests/Feature/Catalog/ProductCatalogInventoryTest.php` | SQLite `:memory:` | 21 passed, 104 assertions |
| `docker compose exec -T app php artisan test --compact tests/Feature/AdminOperationsSecurityTest.php tests/Feature/Promotions/CouponManagementTest.php tests/Unit/Orders/ApplyCouponToOrderUseCaseTest.php` | SQLite `:memory:` | 9 passed, 96 assertions |
| `docker compose exec -T app php artisan test --compact tests/Feature/AuthenticationIsolationTest.php tests/Feature/StaffAuthorizationTest.php tests/Feature/AdminOperationsSecurityTest.php tests/Feature/Catalog/ProductCatalogInventoryTest.php tests/Feature/Promotions/CouponManagementTest.php tests/Feature/Customer/CustomerManagementTest.php tests/Unit/Orders/ApplyCouponToOrderUseCaseTest.php` | SQLite `:memory:` | 30 passed, 194 assertions |
| `docker compose exec -T app vendor/bin/phpunit --no-progress` | SQLite `:memory:` | 82 passed, 386 assertions |
| `docker compose exec -T app vendor/bin/pint --format agent <changed PHP paths>` | N/A | Passed |
| `php artisan route:list --path=admin --json`, `php artisan migrate:status`, and `composer show --direct` inside the local app container | MySQL config / read-only | Completed; 39 admin-prefixed routes found and two local migrations pending |

The full backend suite passed. Production-engine concurrency tests remain unverified.

## Files changed by this audit

- `modules/Orders/UseCases/ApplyCouponToOrderUseCase.php`
- `modules/Orders/Exceptions/OrderCouponModificationNotAllowedException.php`
- `tests/Feature/AdminOperationsSecurityTest.php`
- `tests/Feature/Promotions/CouponManagementTest.php`
- `tests/Unit/Orders/ApplyCouponToOrderUseCaseTest.php`
- `docs/audits/admin-operations-audit.md`

## Remaining release considerations

1. Apply and verify the two pending migrations in a controlled deployment before relying on the current working-tree code.
2. Execute real MySQL concurrency tests for coupon usage, last-unit checkout, and admin inventory adjustment versus checkout.
3. Decide and implement an auditable retention/access model for privileged actions.
4. Add file/database compensation tests before making claims about asset failure recovery.
