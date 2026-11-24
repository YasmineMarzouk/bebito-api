# Database Schema Map (Observed from Uploaded SQL Data Dump)

## Evidence and limitations

This map was derived from the INSERT column lists and sample rows in the uploaded `db.rar` archive. The archive contains SQL data files, not `CREATE TABLE` definitions. It therefore does **not** establish SQL types, nullability, defaults, indexes, unique constraints, foreign keys, check constraints, triggers, or the full set of possible values. Confirm all of those from the Laravel migrations and the actual database before implementing fixes. Empty dump files also do not prove that a table is unused or has no columns.

The data dump includes migration records through `2026_11_05_132728_add_primary_image_to_assets_table` (batch 14). Relevant migration names include:
- `2026_11_01_070837_create_coupons_table`
- `2026_11_01_070838_create_orders_table`
- `2026_11_01_085616_create_inventory_items_table`
- `2026_11_01_085617_create_inventory_movements_table`
- `2026_11_01_085618_create_order_items_table`
- `2026_11_01_085619_create_order_addresses_table`
- `2026_11_01_085620_create_order_status_histories_table`
- `2026_11_01_085621_create_payments_table`
- `2026_11_03_000000_expand_coupons_for_management`

## Order and checkout tables

### `orders`
Observed columns: `id`, `number`, `user_id`, `customer_name`, `customer_email`, `currency_code`, `status`, `payment_method_code`, `coupon_id`, `coupon_code`, `coupon_discount_amount`, `total_amount`, `created_at`, `updated_at`.

Notes: sample rows show `user_id` as `null` in some cases, but this alone does not prove guest checkout is a supported feature. Sample orders use `currency_code = TND`, `payment_method_code = cash_on_delivery`, and `status = pending`; do not infer the complete state machine from sample rows.

### `order_items`
Observed columns: `id`, `order_id`, `product_variant_id`, `product_name`, `sku`, `quantity`, `unit_price_amount`, `total_amount`, `created_at`, `updated_at`.

Notes: at least one sample row has a null `product_variant_id`. Preserve historical snapshots and inspect migration constraints and deletion behavior.

### `order_addresses`
Observed columns: `id`, `order_id`, `type`, `recipient_name`, `phone`, `line_one`, `line_two`, `city`, `postal_code`, `country_code`, `created_at`, `updated_at`. Sample rows use `type = shipping` and `country_code = TN`. Verify whether billing addresses or other address types are actually supported.

### `order_status_histories`
Observed columns: `id`, `order_id`, `status`, `changed_by`, `note`, `created_at`, `updated_at`.

### `payments`
Observed columns: `id`, `order_id`, `method`, `status`, `provider_reference`, `amount`, `currency_code`, `metadata`, `paid_at`, `created_at`, `updated_at`. Sample rows use `method = cash_on_delivery` and `status = pending`. Confirm allowed payment statuses and cash-collection workflow in code. There is no evidence in this dump of an online gateway integration.

## Coupons

### `coupons`
Observed columns: `id`, `code`, `type`, `percentage`, `fixed_amount`, `min_order_amount`, `usage_limit`, `usage_count`, `expires_at`, `is_active`, `created_at`, `updated_at`.

Important: use these actual column names. Do not refer to `discount_value`, `max_usage`, or a `status` column unless the current migrations/code reveal a later schema change not represented in this dump.

Required investigation before implementation:
1. Read the original and expansion migrations; identify exact `type` values, column types, defaults, indexes, and uniqueness/case-sensitivity of `code`.
2. Trace admin CRUD and customer coupon-application endpoints/services/requests/resources.
3. Establish how `percentage` and `fixed_amount` are interpreted and stored. Determine whether monetary values are integer subunits and how TND precision (3 decimal places) is handled.
4. Establish whether `min_order_amount` compares against merchandise subtotal before or after other discounts/shipping; do not invent a rule.
5. Establish whether `usage_limit` is global and when `usage_count` increments. The column names suggest a shared limit/counter, but confirm from application requirements and code.
6. Make validation, count updates, and order creation atomic. Protect the last available use under concurrent checkout. Ensure retries do not consume multiple uses and cancellation behavior follows an explicit existing rule.
7. Persist the applied coupon snapshot to `orders.coupon_id`, `orders.coupon_code`, and `orders.coupon_discount_amount` as appropriate to existing model conventions.

## Catalog, pricing, and inventory candidates

### `products`
Observed columns: `id`, `status`, `is_homepage_featured`, `homepage_position`, `gender`, `sku`, `price_amount`, `compare_at_price_amount`, `discount_percentage`, `stock_quantity`, `low_stock_threshold`, `created_at`, `updated_at`.

### `product_variants`
Observed columns: `id`, `product_id`, `sku`, `attributes`, `is_default`, `created_at`, `updated_at`. Sample `attributes` contain JSON such as size and color.

### `product_prices`
Observed columns: `id`, `product_variant_id`, `currency_code`, `price_amount`, `compare_at_price_amount`, `created_at`, `updated_at`.

### `inventory_items`
Observed columns: `id`, `product_variant_id`, `quantity`, `low_stock_threshold`, `created_at`, `updated_at`.

### `inventory_movements`
No rows/column list were present in the dump file. The migration record confirms a table-creation migration exists; inspect that migration for its columns and constraints.

Important: both `products` and variant-level tables contain price/stock-like fields. Do not assume the legacy product-level `price_amount`/`stock_quantity` or normalized `product_prices`/`inventory_items` is authoritative. Trace the actual checkout path and every stock mutation, then test that all code paths use one consistent source of truth.

## Country and currency configuration

### `countries`
Observed columns: `code`, `name`, `is_active`, `created_at`, `updated_at`. Dump contains active `TN` / Tunisia. The checkout shipping snapshot uses `order_addresses.country_code`. Current business rule: sales are Tunisia-only. Enforce this on the server, not just in the UI.

### `currencies`
Observed columns: `code`, `name`, `symbol`, `decimal_places`, `is_active`, `created_at`, `updated_at`. Dump contains active `TND` with `decimal_places = 3`, and inactive `USD` with `decimal_places = 2`. Current checkout currency must be active TND. Never accept a client override to USD or another inactive currency. Verify the exact integer-money convention before calculations or formatting.

## Related identity and customer tables

### `users`
Observed columns: `id`, `name`, `gender`, `email`, `user_type`, `email_verified_at`, `password`, `is_blocked`, `remember_token`, `created_at`, `updated_at`. Sample `user_type` values include `staff` and `customer`.

### `customer_profiles`
Observed columns: `id`, `user_id`, `phone`, `preferred_locale_code`, `marketing_opt_in`, `manual_badge`, `shopping_for`, `created_at`, `updated_at`.

### `roles`
Observed columns: `id`, `name`, `title`, `created_at`, `updated_at`. Sample role names include `owner`, `admin`, `editor`, `support`, and `customer`. Inspect the actual role-user pivot and permission tables/migrations; do not infer permissions from role names alone.

### `customer_addresses`
The dump file is empty, so its columns cannot be inferred from this archive. Read its migration and model before writing address ownership tests.

## Other observed table column lists

- `assets`: `id`, `resource_type`, `resource_id`, `disk`, `path`, `original_name`, `mime_type`, `size`, `sort_order`, `is_primary`, `primary_image`, `created_at`, `updated_at`.
- `categories`: `id`, `is_active`, `is_homepage_featured`, `homepage_position`, `created_at`, `updated_at`.
- `category_product`: `category_id`, `product_id`, `created_at`, `updated_at`.
- `category_translations`: `id`, `category_id`, `locale_code`, `name`, `slug`, `description`, `created_at`, `updated_at`.
- `collections`: `id`, `name`, `slug`, `description`, `is_active`, `is_featured`, `sort_order`, `created_at`, `updated_at`.
- `collection_product`: `collection_id`, `product_id`, `sort_order`, `created_at`, `updated_at`.
- `collection_translations`: `id`, `collection_id`, `locale_code`, `name`, `slug`, `description`, `created_at`, `updated_at`.
- `product_translations`: `id`, `product_id`, `locale_code`, `name`, `slug`, `short_description`, `description`, `meta_title`, `meta_description`, `created_at`, `updated_at`.
- `locales`: `code`, `name`, `is_active`, `is_default`, `created_at`, `updated_at`.
- `settings`: `id`, `group`, `key`, `value`, `created_at`, `updated_at`.

These are contextual only; do not expand the audit into unrelated catalog/admin features unless they affect checkout/order correctness or the user requests it.
