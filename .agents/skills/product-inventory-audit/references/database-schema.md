# Bébito Catalog & Inventory Schema Map

## Evidence limitation
Derived from INSERT column lists/sample rows in the uploaded SQL dump. It is **not DDL**. Confirm exact types, defaults, nullability, FK/index/unique/check constraints, soft deletes, and current schema from Laravel migrations and the live local/test schema. If current migrations differ, migrations/current schema win and this document must be updated.

## Core catalog tables (observed columns)

### `products`
`id`, `status`, `is_homepage_featured`, `homepage_position`, `gender`, `sku`, `price_amount`, `compare_at_price_amount`, `discount_percentage`, `stock_quantity`, `low_stock_threshold`, `created_at`, `updated_at`

### `product_variants`
`id`, `product_id`, `sku`, `attributes`, `is_default`, `created_at`, `updated_at`

`attributes` sample values are JSON-like and include size/color. Inspect casts, validation, canonical keys, and whether variant combinations must be unique.

### `product_prices`
`id`, `product_variant_id`, `currency_code`, `price_amount`, `compare_at_price_amount`, `created_at`, `updated_at`

Do not assume the product-level `price_amount` or variant-level price table is authoritative. Trace storefront, cart, checkout, admin writes, imports, and jobs.

### `inventory_items`
`id`, `product_variant_id`, `quantity`, `low_stock_threshold`, `created_at`, `updated_at`

Determine canonical stock source and one-item-per-variant behavior from migrations and code.

### `inventory_movements`
No INSERT column list/rows were available in the dump. A create-table migration exists. Read its migration/model before implementing movement tests. Do not invent fields.

## Catalog relationships and supporting tables

- `categories`: `id`, `is_active`, `is_homepage_featured`, `homepage_position`, `created_at`, `updated_at`
- `category_product`: `category_id`, `product_id`, `created_at`, `updated_at`
- `category_translations`: `id`, `category_id`, `locale_code`, `name`, `slug`, `description`, `created_at`, `updated_at`
- `collections`: `id`, `name`, `slug`, `description`, `is_active`, `is_featured`, `sort_order`, `created_at`, `updated_at`
- `collection_product`: `collection_id`, `product_id`, `sort_order`, `created_at`, `updated_at`
- `collection_translations`: `id`, `collection_id`, `locale_code`, `name`, `slug`, `description`, `created_at`, `updated_at`
- `product_translations`: `id`, `product_id`, `locale_code`, `name`, `slug`, `short_description`, `description`, `meta_title`, `meta_description`, `created_at`, `updated_at`
- `assets`: `id`, `resource_type`, `resource_id`, `disk`, `path`, `original_name`, `mime_type`, `size`, `sort_order`, `is_primary`, `primary_image`, `created_at`, `updated_at`
- `product_tag`: `product_id`, `tag_id`, `created_at`, `updated_at`
- `tags`: inspect migration; dump did not provide populated column evidence.
- `locales`: `code`, `name`, `is_active`, `is_default`, `created_at`, `updated_at`

## Relevant integration tables / business constraints

- `orders` and `order_items` use `product_variant_id`, plus historical `product_name`, `sku`, `quantity`, `unit_price_amount`, and `total_amount` snapshots. Preserve historical snapshots when products/variants change or are removed.
- `currencies`: `code`, `name`, `symbol`, `decimal_places`, `is_active`, `created_at`, `updated_at`. Dump shows active TND (`decimal_places=3`) and inactive USD (`decimal_places=2`). Current checkout currency is active TND.
- `countries`: `code`, `name`, `is_active`, `created_at`, `updated_at`. Current business scope is Tunisia only.
- `customer_addresses` dump was empty; inspect its migration before use.
- Admin roles observed include owner/admin/editor/support/customer, but inspect actual pivot/permission tables and policy definitions; role labels do not prove permissions.

## Schema questions Codex must resolve before implementation
1. Are products published via `status` values? List all enum/string values and transitions.
2. Are product-level prices/stock legacy denormalized fields or still consumed?
3. Are prices stored in TND millimes/integer units, decimal, or another representation?
4. Is `product_prices` unique on `(product_variant_id,currency_code)`?
5. Is `inventory_items` unique on `product_variant_id`?
6. What are actual `inventory_movements` columns and movement types?
7. How are assets associated with product vs variant? Are `resource_type` values morph class names or aliases?
8. Are product/variant deletion restricted, soft-deleted, or cascaded? Historical order FKs must be preserved.
9. Are variant attribute combinations unique? What are the allowed attribute keys/values?
10. What translation slug uniqueness/fallback rules exist?
11. Which endpoints return product/variant stock and pricing?
12. Which service, listener, job, admin action, import, and checkout code paths mutate inventory?

Do not fill unknowns with assumptions. Document decisions and test actual constraints.
