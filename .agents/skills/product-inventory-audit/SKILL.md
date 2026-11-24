---
name: product-inventory-audit
description: Audit, harden, complete, and comprehensively test the Laravel product catalog and inventory modules for both the customer storefront and admin dashboard. Use when reviewing products, variants, prices, stock, categories, collections, assets/images, catalog visibility, inventory movements, or product administration.
---

# Product & Inventory Audit — Bébito Laravel Ecommerce

## Mission

Make the **product/catalog and inventory domain reliable, secure, and fully tested from both sides**:

- **Admin dashboard:** authorized staff can manage catalog and stock safely; unauthorized users cannot read or mutate protected admin data or bypass permissions.
- **Customer storefront:** customers/guests can only see purchasable, correctly localized and priced products/variants; they cannot manipulate prices, stock, visibility, or internal fields.
- **Order integration:** catalog prices and stock are authoritative at checkout; product/variant snapshots remain correct; inventory mutations are consistent with the already-audited order module.
- **Tests:** every applicable scenario in the references is implemented as automated tests, not merely documented or manually checked.

This skill is intended for the existing Bébito Laravel repository. **Do not redesign the database or replace architecture just because another pattern is preferred.**

## Mandatory database grounding

The uploaded database dump was mapped in `references/database-schema.md`. It contains INSERT data rather than full DDL. Treat its names as observed evidence, not as a substitute for migrations.

Before coding, inspect:
1. All relevant Laravel migrations and current schema (`php artisan migrate:status`; schema inspection in a safe local/test environment).
2. Models, casts, relationships, scopes, factories, seeders, policies, gates, FormRequests, controllers, services/actions, resources, routes, jobs/listeners, and frontend API contracts.
3. Existing tests and test DB configuration.
4. Actual database constraints, indexes, foreign keys, soft deletes, and deletion behavior.
5. Current source-of-truth for product price and inventory. Resolve the legacy/normalized duplication explicitly from code and checkout behavior.

Use the exact observed schema names in `references/database-schema.md`. Do not invent columns such as `stock`, `price`, `active`, or `variant_stock` when actual columns differ. If migrations differ from the dump, **migrations/current schema win**; document the discrepancy and use actual current names.

## Non-negotiable working rules

- Start with a repository reconnaissance and a written baseline; do not make changes until you understand the implementation.
- Keep scope to product/catalog/inventory and dependencies required for correctness. Do not rewrite authentication or orders; integrate with their existing contracts and tests.
- Preserve existing API routes and response shapes unless a change is necessary. If changing a contract, update consumers and tests together and explain it.
- Do not trust frontend validation, product IDs, variant IDs, SKU, price, discount, stock, currency, availability, status, asset paths, sort positions, or relationship IDs from the client.
- Use allow-listed request fields and server-side authorization. Never mass-assign sensitive/internal fields from customer requests.
- Admin and storefront use distinct authorization/visibility rules. A staff role name alone is not proof of permission; inspect the actual role/permission/policy system.
- Prices and stock are never authoritative in the browser. Re-read current values server-side at checkout and use the already-audited order transaction/inventory flow.
- Use decimal-safe/integer money according to existing conventions. TND has `decimal_places = 3`; do not assume 2 decimals or silently change representation.
- For concurrent stock changes, use DB transactions and appropriate row locks/atomic updates; enforce nonnegative stock at DB/application levels where feasible.
- Do not silently “fix” historical records, delete production data, run destructive migrations, or reset any non-test database.
- Do not weaken tests, remove assertions, or mark skipped tests as passed to get green.
- Never claim a test passed unless it was executed and its output inspected.
- If a scenario cannot be automated because a dependency/environment is missing, document exact reason, safe manual procedure, and remaining risk. Do not call coverage complete while a required scenario is untested.
- Do not add speculative business rules (e.g. mandatory variant, one image, unique product name, automatic stock decrement on admin save) without evidence in existing requirements/code. Identify ambiguity and propose a safe decision.

## Phase 0 — Baseline and inventory map

Create a baseline report before changes:

- Product admin routes and storefront routes/API endpoints.
- Models and relationships.
- Product lifecycle/status values and public visibility rules.
- Variant attributes format (JSON keys, size/color semantics, default variant rules).
- Pricing source of truth and currency selection.
- Stock source of truth and mutation paths.
- Asset/image ownership, primary image rules, ordering, storage disk, URL generation.
- Category/collection/tag relationships and translation fallback.
- Existing admin permission matrix.
- Existing tests and uncovered risks.
- Migration/schema discrepancies from the uploaded dump.

Search every read/write of:
`price_amount`, `compare_at_price_amount`, `discount_percentage`, `stock_quantity`, `inventory_items.quantity`, `product_prices.price_amount`, `inventory_movements`, product/variant status or visibility, and product variant attributes.

Do not proceed with assumptions where a conflicting source exists; trace the call path and establish one canonical source.

## Phase 1 — Product catalog and inventory audit

Review and fix findings in these domains:

### A. Product lifecycle and visibility
- Draft/disabled/archived/out-of-stock products are not exposed as purchasable unless existing business rules explicitly allow display-only behavior.
- Storefront filters cannot reveal unpublished products by changing query parameters, IDs, slugs, or API paths.
- Product status changes are authorized and validated.
- Deactivation/unpublishing invalidates or blocks new checkout attempts.
- Product slug/identifier handling, route binding, and translation resolution cannot expose another product or bypass visibility.
- Featured/homepage flags and positions are admin-only writable fields.
- Public listing/detail/search/category/collection endpoints consistently apply visibility scopes.
- Product pagination, sorting, and filters are validated and bounded; no arbitrary SQL/order-by injection.

### B. Product variants
- Variant belongs to the requested product; nested routes cannot mix IDs across parents.
- Attributes JSON is validated, cast, and serialized safely; malformed JSON and unexpected keys are rejected/handled.
- Variant SKU rules are verified against actual unique constraints and business requirements.
- Default variant selection is deterministic and does not return a disabled/non-purchasable variant.
- Duplicate variant combinations are detected if the domain defines combinations as unique; do not assume without confirming.
- Variant deletion/deactivation with historical order references is safe and preserves order snapshots.
- Storefront cannot request another product's variant to obtain its price or inventory.
- Variant updates do not accidentally overwrite unrelated attributes or inventory.

### C. Pricing and discounts
- Verify canonical price source: `product_prices` by variant/currency vs legacy product-level columns. Do not synchronize two sources blindly.
- Active currency must be used; current checkout currency is active TND (`currencies.code`, `is_active`, `decimal_places`).
- Client-supplied prices, compare-at prices, discounts, currency, totals, or price IDs are ignored/rejected on storefront mutations.
- Admin price updates require authorization and server-side validation.
- Price must be nonnegative and within sensible DB/application bounds; precision matches TND.
- Compare-at price and discount percentage consistency is tested according to actual business logic; do not invent discount rules.
- Product detail/list/cart/checkout price values agree for the same variant/currency.
- No stale cached price can be used to place an order after price change; checkout revalidates authoritative current price.
- If multiple price rows for a variant/currency are possible, identify and enforce expected uniqueness from schema/requirements.

### D. Inventory and stock
- Identify whether `products.stock_quantity` is legacy/summary, while `inventory_items.quantity` is canonical. Trace all writes and reads.
- Establish how one inventory item per variant is enforced; inspect FK/index/unique constraints.
- Stock is nonnegative; quantity input types and bounds are validated.
- Storefront displays availability based on authoritative inventory and does not allow overselling.
- Admin stock adjustment is permission-controlled, auditable, and atomic.
- All stock changes have the appropriate `inventory_movements` record if that is the established design; inspect its actual migration columns before writing tests or code.
- Prevent direct client stock mutation and mass assignment.
- Concurrent adjustments and order reservations/consumption cannot produce negative inventory or lost updates.
- Product/variant creation, deletion, and activation handle inventory initialization/retention correctly.
- Low-stock thresholds and alerts are correct; do not send duplicate alerts for the same event unless intended.
- Stock synchronization, import, seed, or background jobs cannot overwrite newer inventory unexpectedly.
- Order cancellation/restock and order fulfillment integrate with existing order audit; no double-restock or double-decrement.

### E. Images/assets
- Asset belongs to a valid product/variant/resource according to `assets.resource_type` and `resource_id`; validate ownership before attach/detach/reorder/delete.
- Upload validates allowed MIME, actual file type, size, extension, dimensions if configured, and storage path; do not trust client MIME/name.
- Reject executable/unsafe files and path traversal.
- Primary image selection is deterministic; changing primary is atomic and does not leave multiple primary images where uniqueness is intended.
- `sort_order` is validated and stable; deletion/reorder does not corrupt unrelated assets.
- Public image URLs do not expose private storage or arbitrary files.
- Missing/deleted image behavior has a safe fallback.
- Admin cannot attach an asset to another product without permission.
- Deleting a product/variant follows configured asset cleanup and historical reference behavior; no accidental deletion of shared files.

### F. Categories, collections, tags, translations
Use the observed tables:
- `categories`, `category_product`, `category_translations`
- `collections`, `collection_product`, `collection_translations`
- `product_translations`, `product_tag`, `tags` (inspect exact schema)
- `locales`

Audit:
- Relationship IDs exist and are authorized; attach/detach cannot be used for IDOR.
- Inactive categories/collections do not expose products in public listings unless intentional.
- Product membership and collection sort order are consistent.
- Translation locale is valid; slug uniqueness follows actual scoped constraints.
- Missing translation fallback is deterministic and does not leak admin-only text.
- Search/filter combinations, empty results, and pagination are correct.
- Featured/homepage positions cannot be manipulated by customers.

### G. API, query, cache, and performance
- Validate query filters, pagination, sort keys, search length, and include/expand options.
- Prevent N+1 queries; use eager loading and verify query count where useful.
- Cache keys include relevant locale/currency/visibility/filters and invalidate on product, price, stock, translation, asset, category, or collection changes.
- Do not cache private/admin data in public responses.
- Check rate limiting/abuse controls for expensive search endpoints.
- Public resources expose only intended fields (no internal notes, admin metadata, storage internals, or unneeded IDs).
- Verify API resources are stable and consistent across listing/detail endpoints.

## Phase 2 — Admin and storefront security matrix

### Admin dashboard
Test every relevant route/action for:
- Unauthenticated access → 401/redirect as designed.
- Customer role access → denied.
- Staff without required permission → denied.
- Authorized staff → allowed.
- Object-level access where applicable.
- Mass assignment of `status`, homepage flags/position, stock, price, SKU, product/variant IDs, primary asset, and relationship IDs.
- Validation errors do not partially mutate data.
- Unauthorized asset upload/delete/reorder, product/variant update, price change, stock adjustment, category/collection membership change.
- Admin actions are logged/audited where established or required.

### Storefront
Test:
- Guest and authenticated customer can browse only public products.
- Hidden/inactive product or variant cannot be bought through direct ID, slug, variant endpoint, cart API, or checkout.
- Customer cannot create/update/delete products, variants, prices, inventory, assets, categories, or collections.
- Attempts to inject admin fields or foreign IDs are rejected or ignored safely.
- Product/variant ownership is checked on nested endpoints.
- Product/variant price and stock cannot be overridden by client payload.
- No leakage of admin-only attributes or unpublished products through search, related products, collections, or cached endpoints.

## Phase 3 — Automated test suite (required)

Use the existing Laravel test conventions, factories, helpers, and test DB. Add/adjust tests—not only a checklist. Full scenario catalog is in `references/test-scenarios.md`.

Minimum test layers:
1. Unit tests for price/discount/availability/variant logic where meaningful.
2. Feature tests for every admin and storefront endpoint.
3. Authorization tests for all role/permission levels.
4. Database tests for FK, uniqueness, constraints, cascades/retention, and rollback.
5. Integration tests across product → variant → price → inventory → asset/category/collection → cart/checkout.
6. Concurrency tests against the actual supported DB engine (not SQLite substitutes if locking semantics differ).
7. Regression tests for every bug discovered.

Test both successful and rejected requests, and assert database state, response shape, and side effects—not status code alone.

Do not rely on a single generic CRUD test. Cover each endpoint's relevant field and permission boundaries.

## Phase 4 — Correctness, concurrency, and checkout integration

- Reconcile storefront price/availability with checkout and the audited order module.
- Verify product price or stock changes between add-to-cart and checkout.
- Verify product/variant deactivation between add-to-cart and checkout.
- Test simultaneous stock adjustment and order consumption against actual DB.
- Test last-unit purchase race: stock = 1; multiple concurrent checkouts; at most one succeeds, stock never negative, movements/order quantities reconcile.
- Test repeated inventory adjustment request/idempotency if endpoint supports idempotency.
- Test transaction rollback: failures after inventory update must roll back all related DB changes.
- Test cancellation/restock once and only once through existing order lifecycle.
- Confirm no double stock mutation by observers/listeners/jobs/retries.

See `references/concurrency-testing.md`.

## Phase 5 — Fix, verify, and report

Fix issues in priority order:
- P0: exploitable authorization/IDOR, price/stock manipulation, overselling, data corruption, public unpublished data.
- P1: incorrect prices/availability, inventory drift, destructive asset/relationship operations, broken checkout integration.
- P2: incomplete validation, cache invalidation, performance, admin usability affecting correctness.
- P3: low-risk consistency/documentation.

For every finding:
- Reproduce with a failing test first where feasible.
- Implement minimal safe fix.
- Add regression test.
- Run targeted tests, then full relevant suite.
- Inspect actual test output and DB state.
- Avoid unrelated refactoring.

## Required deliverables

Create/update these files in the repository (or clearly report if project convention differs):
- `docs/audits/product-inventory-audit.md` — findings, severity, evidence, fixes, residual risks.
- `docs/audits/product-inventory-test-matrix.md` — scenario → test class/method → status.
- `docs/audits/product-inventory-data-source-map.md` — canonical price/stock source and all mutation paths.
- Add automated test classes following project naming conventions.
- Update API docs/contract only if changed.

Final response must include:
1. Summary of implementation and audit.
2. Actual schema/migrations inspected and any dump discrepancies.
3. Canonical source of price and stock (with code references).
4. Admin/storefront coverage and permission matrix.
5. Tests executed with exact commands and pass/fail counts.
6. Concurrency tests executed and DB engine used.
7. Findings fixed by severity.
8. Remaining blockers/untested scenarios with explicit reasons.
9. Confirmation that no production data or non-test DB was modified.

**Completion criterion:** all applicable scenarios in the test matrix are implemented and passing; any excluded scenario is explicitly justified. Do not call the module “fully secure” or “100% secure”; state evidence-based coverage and residual risks.
