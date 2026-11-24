
# Bébito Cart & Checkout Schema Grounding

## Evidence and limitation

Derived from the previously uploaded SQL archive. It contains INSERT/sample data rather than complete DDL.

The archive includes:

- `products`
- `product_variants`
- `product_prices`
- `inventory_items`
- `inventory_movements`
- `coupons`
- `orders`
- `order_items`
- `order_addresses`
- `payments`
- `order_status_histories`
- `countries`
- `currencies`
- `customer_addresses`
- `users`, `roles`, and supporting tables

No `carts.sql` or `cart_items.sql` dump was present.

This is not proof that cart tables do not exist. Inspect migrations and source code for cart persistence or a different table name.

**Migrations and current schema are authoritative.**

## Observed checkout/order fields

### `orders`

Observed columns include:

- `id`
- `number`
- `user_id`
- `status`
- `payment_method_code`
- `coupon_id`
- `coupon_code`
- `coupon_discount_amount`
- `total_amount`
- `created_at`
- `updated_at`

Check migrations and the complete schema for additional fields.

### `order_items`

Observed:

- `id`
- `order_id`
- `product_variant_id`
- `product_name`
- `sku`
- `quantity`
- `unit_price_amount`
- `total_amount`
- `created_at`
- `updated_at`

These are historical snapshots. Product updates/deletion must not rewrite order history.

### `order_addresses`

Observed:

- `id`
- `order_id`
- `recipient_name`
- `phone`
- `line_one`
- `line_two`
- `city`
- `postal_code`
- `country_code`
- `created_at`
- `updated_at`

Order address is a snapshot. Saved customer address ownership must be verified.

### `payments`

Observed:

- `id`
- `order_id`
- `method`
- `status`
- `amount`
- `currency_code`
- `paid_at`
- `created_at`
- `updated_at`

Payment is COD-only. Inspect actual initial status and allowed transitions.

### `order_status_histories`

Observed status history table. Inspect migration for exact columns and required semantics.

## Coupon columns

Observed in `coupons`:

- `id`
- `code`
- `type`
- `percentage`
- `fixed_amount`
- `min_order_amount`
- `usage_limit`
- `usage_count`
- `expires_at`
- `is_active`
- `created_at`
- `updated_at`

Verify migrations and previous coupon audit. No client-supplied discount is authoritative.

## Product and inventory references

- `products`: `status`, `sku`, `price_amount`, `compare_at_price_amount`, `discount_percentage`, `stock_quantity`, `low_stock_threshold`, homepage flags/position, gender, timestamps.
- `product_variants`: `product_id`, `sku`, `attributes`, `is_default`, timestamps.
- `product_prices`: `product_variant_id`, `currency_code`, `price_amount`, `compare_at_price_amount`, timestamps.
- `inventory_items`: `product_variant_id`, `quantity`, `low_stock_threshold`, timestamps.
- `inventory_movements`: dump has no populated rows/column list; inspect migration/model.
- `currencies`: `code`, `name`, `symbol`, `decimal_places`, `is_active`; active TND with 3 decimals, USD inactive in dump.
- `countries`: `code`, `name`, `is_active`; Tunisia is the currently configured sales country.
- `customer_addresses`: dump is empty; inspect migration and ownership relation.

## Cart persistence

No cart schema is evidenced by the archive.

Codex must determine whether the cart is:

- Client-side/local storage and sent as checkout lines.
- Session-based.
- API token/session-backed.
- DB-backed with another table name.
- Or a hybrid.

If DB cart exists, document exact table and columns from migrations.

Do not invent `carts` or `cart_items` tables or add them unless requirements and architecture justify it.

## Schema validation tasks

Inspect migrations and current test DB to confirm:

- FK constraints/order snapshots and deletion rules.
- Unique indexes on order number, payment/order relationship, product price per variant/currency, inventory item per variant, coupon code.
- Money data types/units and TND 3-decimal handling.
- Order/payment/coupon status values.
- Cart identity and ownership constraints if persisted.
- Idempotency key storage/index if implemented.
- Indexes for checkout lookup/locking.

Update this document/report if actual schema differs.
