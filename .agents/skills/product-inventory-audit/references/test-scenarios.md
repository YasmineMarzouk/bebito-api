# Product & Inventory Audit — Automated Test Scenario Catalog

Implement applicable scenarios using Laravel's current test conventions. Record every scenario in `docs/audits/product-inventory-test-matrix.md` with test method and execution status. Test response **and** persisted state/side effects.

## 1. Storefront catalog visibility and read access
- Public active/published product appears in listing and detail.
- Inactive/draft/unpublished product absent from listing, search, category, collection, related products, homepage, and direct detail.
- Hidden product remains hidden by ID, slug, query filters, alternate endpoint, and cache.
- Product status transition immediately changes public visibility; cache invalidated.
- Guest and customer browse permitted public catalog.
- Product resources expose only public fields; no internal/admin fields or private storage paths.
- Invalid/nonexistent product ID/slug returns consistent safe 404.
- Pagination defaults, maximum page size, empty page, malformed page, negative/huge values.
- Sorting only accepts allowlisted fields and directions; injection/arbitrary columns rejected.
- Search/filter input length and invalid values validated; filters combine correctly.
- Query does not reveal hidden products via counts, facets, related product IDs, or error differences beyond acceptable behavior.

## 2. Admin authentication, permissions, and object-level authorization
For every product, variant, price, inventory, asset, category, collection, and relationship mutation endpoint:
- Unauthenticated denied.
- Customer denied.
- Staff without permission denied.
- Staff with required permission allowed.
- Read vs create/update/delete permissions are independently respected where configured.
- Cross-tenant/foreign parent IDs (if tenant concept exists) denied.
- Nested route parent-child mismatch denied (variant belongs to different product; asset belongs to different product).
- IDOR attempts to modify another object denied.
- Authorization denial leaves database and files unchanged.
- Permission revocation takes effect.
- Admin endpoint cannot be called via storefront route or vice versa.

## 3. Product CRUD and validation
- Create with valid required fields succeeds.
- Missing required fields, invalid types, overlong strings, invalid enum/status/gender, malformed slug/SKU, invalid flags/positions rejected.
- Negative/overflow price, compare-at price, discount, stock, low-stock threshold rejected.
- Unknown fields and protected fields are ignored/rejected safely.
- Mass assignment cannot set internal fields, IDs, timestamps, homepage flags, status, stock, price unless specific authorized operation.
- Update only intended fields; partial update preserves unrelated fields.
- Failed validation is atomic/no partial writes.
- Delete/deactivate behavior respects variant, inventory, assets, and order history.
- Product with historical order items cannot cause snapshot loss or broken history.
- Duplicate SKU/slug behavior follows actual unique constraints and validation.
- Concurrent create/update uniqueness collision returns controlled validation/conflict, not 500.
- Product status transition rules enforced if lifecycle exists.

## 4. Variant integrity
- Create valid variant for product.
- Nonexistent parent product rejected.
- Cross-product variant IDs denied in nested endpoints.
- Valid attributes accepted; malformed JSON, scalar instead of object, invalid key/value/type rejected.
- Allowed size/color values follow actual product rules; no assumptions without existing spec.
- Default variant is deterministic; only one default if schema/business rule requires.
- Disabled variant cannot be selected/purchased.
- Duplicate SKU or duplicate combination behavior follows actual constraints.
- Variant update does not erase unrelated attributes.
- Variant delete/deactivate respects inventory and historical order references.
- Variant creation correctly initializes inventory if existing design requires it.
- Concurrent variant creation does not violate unique constraints.

## 5. Pricing and currency
- Admin authorized price creation/update works.
- Unauthorized price mutation denied.
- Storefront ignores/rejects payload price, compare-at price, discount, currency, price ID.
- Price resolved for correct variant and active TND.
- Inactive USD cannot be requested or used for checkout.
- Missing price row handled safely (not silently zero or wrong product fallback unless intended).
- Duplicate price rows prevented by DB/application where required.
- Negative, too-large, excessive-precision, malformed prices rejected.
- TND precision (`decimal_places=3`) preserved in calculation, serialization, and display data.
- Product list/detail/cart/checkout price values agree.
- Compare-at price/discount logic and boundaries tested according to implemented rule.
- Price update invalidates catalog/cart cache.
- Price change after cart creation: checkout re-fetches authoritative price and follows defined behavior.
- Concurrent price update and checkout yields a consistent price snapshot/transaction result.

## 6. Inventory and movements
- Inventory item created for variant as expected.
- Missing inventory row behavior is explicit and safe (not unlimited stock).
- Quantity 0 => out of stock; positive quantity => availability according to business rules.
- Negative quantity rejected at every public/admin path and DB constraint where feasible.
- Admin adjustment succeeds only with permission and valid amount/reason if required.
- Storefront/customer cannot mutate inventory or movements.
- Movement record created for each required stock adjustment; fields/type/value match actual migration.
- Adjustment transaction rollback leaves both quantity and movement unchanged.
- Concurrent increments/decrements no lost updates.
- Cannot decrement below zero; insufficient stock produces controlled response.
- Last-unit concurrent purchase: one unit, N concurrent checkout attempts, no more than one successful reservation/consumption, never negative stock.
- Stock decrement/release exactly once under retries, duplicated requests, job retries, or duplicate listeners.
- Order cancellation/restock occurs once under duplicate/concurrent cancellation.
- Fulfillment/stock mutation follows existing order lifecycle.
- Stock movement history cannot be edited/deleted by unauthorized users.
- Inventory item deletion is safe and respects variant/order history.
- Low-stock threshold boundary: below/equal/above behavior is verified.
- Low-stock notification/job is not duplicated unexpectedly.
- Product `stock_quantity` and `inventory_items.quantity` stay consistent if both are intended to be maintained; otherwise legacy field is not accidentally used.
- Inventory import/sync job handles invalid rows and partial failure without corrupting stock.

## 7. Assets and image security
- Valid supported image upload succeeds.
- Wrong MIME, extension mismatch, forged MIME, executable/polyglot, oversized file, corrupt image rejected.
- Path traversal, malicious filename, SVG/script handling follows explicit safe policy.
- Upload to unauthorized product/variant denied.
- Attach asset to valid product/variant succeeds.
- Attach asset to unrelated/foreign resource denied.
- Primary image switch results in expected single primary (if intended) and atomic state.
- Reorder uses valid bounded sort positions and correct resource scope.
- Delete asset/file follows ownership and cleanup policy; unauthorized delete denied.
- Failed DB operation removes orphan upload or records safe cleanup.
- Missing file/storage failure handled gracefully.
- Public URLs expose no local path/secrets; private assets remain private.
- Product deletion cleans assets only according to existing ownership rules; shared files not deleted.

## 8. Categories, collections, tags, translations
- Admin permission required for create/update/delete/attach/detach.
- Customer cannot mutate relationships.
- Attach valid product/category/collection/tag succeeds.
- Invalid or cross-parent IDs rejected.
- Inactive category/collection hidden from storefront as designed.
- Products appear only in correct category/collection.
- Sort order validation and deterministic ordering.
- Translation valid locale accepted; invalid/inactive locale rejected or handled per design.
- Translation fallback works; missing translation does not 500.
- Duplicate locale/slug behavior follows constraints.
- Translation update invalidates caches.
- Featured/homepage flags and positions protected.
- Category/collection listing cannot leak hidden products.

## 9. API contracts and cache
- Response schema and field types stable for list/detail/search/filter endpoints.
- Empty/null/missing translations and images serialize safely.
- Resource serialization avoids accidental lazy-loading N+1.
- Query count bounded for representative product lists.
- Cache hit returns same correct visibility/price/stock as DB.
- Cache invalidated after product/status/variant/price/stock/asset/translation/category/collection mutations.
- Locale/currency/filter/page cache keys do not collide.
- Hidden product is not exposed from a stale cache.
- Rate limit and expensive search limits work.
- Errors do not leak SQL, stack traces, filesystem paths, credentials, or internal model data.

## 10. Checkout and order integration (coordinate with audited order module)
- Cart accepts valid product variant only.
- Client-supplied unit price/total/discount/stock ignored or rejected.
- Checkout rechecks active/public product, variant, current price, active TND, and sufficient stock.
- Price changed after add-to-cart follows explicit rule and never uses stale client price.
- Product/variant unpublished after add-to-cart cannot be purchased.
- Inventory is decremented/reserved exactly once by canonical order workflow.
- Failed order creation rolls back stock and movement changes.
- Concurrent checkout cannot oversell.
- Cancellation/restock exactly once.
- Historical order item snapshots remain unchanged after product name/SKU/price update or product deletion/deactivation.
- Product deletion cannot cascade-delete order item snapshots.

## 11. Reliability and regression
- DB unavailable/storage unavailable/queue unavailable failures are handled without partial data.
- Transaction rollback on exception.
- Retry does not duplicate stock movements, uploads, or alerts.
- Race conditions for SKU/slug/primary image/stock uniqueness are handled.
- Every discovered defect has a regression test.
- Full relevant suite and baseline tests pass.

## Coverage rule
A scenario is:
- **PASS** only if an automated test ran and passed.
- **FAIL** if test fails or behavior is wrong.
- **BLOCKED** only with an evidenced environment/tool limitation.
- **N/A** only with a documented reason and owner decision.

No scenario may be marked PASS based only on manual inspection or code reading.
