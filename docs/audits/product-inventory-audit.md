# Product & Inventory Audit Report

## Executive summary

- Scope: Laravel `Catalog`, `Inventory`, Core asset persistence, the admin product payload, public storefront catalog contract, and checkout integration.
- Date: 2026-11-09. The worktree was already dirty; unrelated changes were preserved.
- Runtime verified: PHP 8.4.27 and Composer 2.11.1 through the project Docker container. Tests use Laravel's SQLite `:memory:` connection from `phpunit.xml`.
- Outcome: hardened and regression-tested. No production database, production files, deployment, or destructive database action was performed.
- Fixed findings: one P1 and five P2/P3 findings. No open P0/P1 findings remain in the audited code paths.

## Schema grounding

Migrations, rather than the data dump, were used as the schema authority. Inspected migrations include the catalog tables introduced on 2026-11-01, homepage/primary-image migrations, and the new additive `2026_11_09_000001_preserve_catalog_inventory_history` migration.

- `products`: status, price summary, compare-at summary, discount summary, stock summary, thresholds, homepage fields, and now nullable `deleted_at`.
- `product_variants`: belongs to a product; SKU is unique; attributes are JSON; `is_default` and new `is_active` are booleans. The new `(product_id, is_active)` index supports active catalog reads.
- `product_prices`: unique on `(product_variant_id, currency_code)`.
- `inventory_items`: one per variant (`product_variant_id` is unique) and holds the canonical quantity/threshold.
- `inventory_movements`: immutable-by-application append records with item, signed quantity change, reason, optional order reference/user, and note. Supported reasons are `initial_stock`, `manual_adjustment`, and `order_placed`.
- `assets`: polymorphic resource type/id, ordered, with nullable `primary_image`. The application enforces a single primary image for an existing resource.

Current application contract: `products.price_amount` is the checkout/storefront price source in minor units. Variant `product_prices` rows are synchronized to the active TND price but are not the checkout source. `inventory_items.quantity` is canonical stock; `products.stock_quantity` is a derived active-variant summary and is never trusted as an independent write.

## Architecture map

- Admin: `/api/v1/admin/products`, categories, and collections are protected by `auth:admin`, active-account, and section/permission middleware. Read, write, and execute map to RBAC actions.
- Storefront: `/api/v1/homepage/*` and `/api/v1/shop/*` expose only published products, active taxonomy, and active variants. Storefront pages call these through `bebito-blossom-boutique/src/lib/catalog-api.ts`.
- Dashboard: `b-bito-admin-suite/src/components/admin/ProductForm.tsx` now submits persisted variant IDs. The API can therefore update an existing variant instead of replacing it.
- Catalog writes: form request → DTO → use case → repository. `ProductRepository` is the catalog persistence boundary; checkout uses `CheckoutInventoryRepository` to revalidate variant availability and reserve stock.
- Cache/jobs/listeners: no catalog cache, queue job, or listener was present in the audited paths.

## Findings

| ID | Severity | Area | Evidence | Fix | Regression test |
|---|---|---|---|---|---|
| PI-001 | P1 | Variant/history integrity | Product update deleted every variant; FK cascades could erase inventory movements and invalidate cart/order links. | Stable variant IDs are accepted and validated for ownership. Removed variants are retired, zeroed with a movement, and excluded from catalog/checkout. | `ProductCatalogInventoryTest::test_removing_a_variant_retires_it_and_prevents_customer_checkout_without_erasing_history` |
| PI-002 | P1 | Product deletion | Hard product delete could cascade through variants/inventory and destroy audit history. | Product deletion is now a soft delete; variants, inventory movements, and order-item links remain. | `ProductCatalogInventoryTest::test_product_delete_soft_deletes_the_catalog_entry_and_preserves_order_and_inventory_history` |
| PI-003 | P2 | Inventory consistency | Product stock was caller-supplied and could drift from variant quantities; adjustments had no movement record. | Request checks any supplied total; repository derives the total, preserves variant rows, and records initial/manual movements atomically. | `ProductCatalogInventoryTest::test_product_update_preserves_variant_identity_derives_stock_and_records_adjustments` |
| PI-004 | P2 | IDOR | Variant IDs were not part of the update contract, and a future nested update could not prove product ownership. | Persisted IDs are sent by the dashboard and rejected unless they belong to the target active product. | `ProductCatalogInventoryTest::test_product_update_rejects_a_variant_owned_by_another_product` |
| PI-005 | P2 | Asset ownership/concurrency | Primary-image assignment lacked a resource-row lock; cross-product asset selection must not be accepted. | Asset create/delete and product primary-image switching lock resource rows; endpoint validates product ownership. | `PrimaryImageTest`, `ProductCatalogInventoryTest::test_primary_image_endpoint_rejects_an_asset_from_another_product_without_changing_the_current_primary_image` |
| PI-006 | P3 | Public visibility | Category/collection counts and product taxonomy could include unpublished products or inactive taxonomy. | Public counts use published products; public product relations load active categories/collections only. | `ShopCatalogTest::test_public_catalog_counts_and_product_categories_exclude_unpublished_and_inactive_data` |
| PI-007 | P3 | Domain representation | Catalog used plain-text state comparisons. | Added typed product status, gender, and inventory-status enums; model casts and comparisons use them. | Catalog and checkout suites |

## Admin coverage

- Product/category/collection routes use the staff guard and RBAC middleware.
- Owner-role staff CRUD, customer denial, read-only denial, and independent write/execute enforcement are automated.
- Nested primary-image ownership and cross-product variant ownership are automated.
- Invalid create requests do not write product, variant, or asset data.

## Storefront coverage

- Guest catalog lists/details expose published products only; inactive categories/collections are hidden.
- Retired variants cannot be checked out, and a soft-deleted product has no public detail route.
- Checkout derives price and availability from server-side product/variant/inventory rows, not client fields.

## Concurrency and performance

- The code locks product-variant rows before stock mutation and resource asset rows before primary-image mutation.
- SQLite `:memory:` cannot validate MySQL/MariaDB lock behavior or true multi-process races. No production-equivalent isolated database is configured in this workspace, so last-unit, concurrent admin-adjustment, and primary-image race tests are blocked.
- No cache or job layer exists in scope; cache invalidation/load tests are not applicable to the current implementation.

## Commands executed

- `docker compose exec -T app php -v`
- `docker compose exec -T app composer -V`
- `docker compose exec -T app php artisan route:list --path=api/v1 --except-vendor`
- Baseline: `php artisan test` — 62 passed, 199 assertions.
- Targeted catalog/checkout tests passed during implementation.
- Final: `php artisan test` — 74 passed, 280 assertions (24.22 seconds).
- `vendor/bin/pint` completed successfully for the changed PHP files.

## Residual risks / blocked tests

- True concurrent database tests remain blocked by the SQLite-only test configuration. Run the documented race cases against isolated MySQL/MariaDB before production rollout.
- The public catalog intentionally has no pagination, server-side search, or server-side sorting API. Those scenarios are N/A for the current route contract, not silently treated as covered.
- Category/collection banner replacement leaves older resource assets in storage; this is a low-severity storage-retention concern, not a product/inventory integrity issue. It was not destructively cleaned during this audit.

## Data safety

- Only isolated in-memory test data was created and cleaned by `RefreshDatabase`.
- The additive migration has been created but not applied to any non-test database.
