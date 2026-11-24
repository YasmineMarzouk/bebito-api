# Product & Inventory Data-Source Map

## Canonical write paths

| Domain data | Authoritative storage | Writer | Guardrails |
|---|---|---|---|
| Product visibility, summary price, summary stock | `products` | `ProductRepository` | Product status enum, soft delete, published-only public queries. |
| Variant identity/options | `product_variants` | `ProductRepository` | Product-owned ID validation, active/retired state, exactly one default active variant. |
| Variant price row | `product_prices` | `ProductRepository` | TND row synchronized from product price; database unique `(variant,currency)`. |
| Sellable stock | `inventory_items.quantity` | `ProductRepository` admin adjustment and `CheckoutInventoryRepository` reservation | One item per variant, non-negative request validation, checkout sufficiency check/locking. |
| Stock audit history | `inventory_movements` | product initial/adjustment workflow and checkout order reservation | Signed change, enum reason, optional staff/order reference. |
| Product/category/collection media | `assets` and configured storage disk | `FileStorageService` / `AssetsRepository` | Resource ownership, primary-image lock, product primary asset endpoint validates parent. |
| Categories/collections/tags/translations | catalog pivot/translation tables | `ProductRepository`, category/collection repositories | Form-request existence checks and staff RBAC route middleware. |

`products.stock_quantity` is a materialized summary, not an independent source. Product writes calculate it from requested active variant quantities and then recompute it from `inventory_items`; checkout decrements it only after reserving the exact inventory item.

## API data flow

```text
Admin ProductForm
  -> /api/v1/admin/products (staff.permission:products)
  -> Create/UpdateProductRequest -> DTO -> UseCase -> ProductRepository
  -> products + variants + prices + inventory_items + inventory_movements + assets

Storefront catalog API
  -> /api/v1/homepage/*, /api/v1/shop/*
  -> public ProductRepository queries
  -> ProductOutput (active variants, public assets, active taxonomy only)

Storefront checkout
  -> /api/v1/store/checkout
  -> CheckoutInventoryRepository
  -> locked active variant + inventory item, server price, order snapshot, movement
```

## Dashboard and storefront consumers

| Consumer | Files | API contract used | Notes |
|---|---|---|---|
| Admin product editor | `b-bito-admin-suite/src/components/admin/ProductForm.tsx`, `src/lib/product-api.ts` | `/api/v1/admin/products` | Sends immutable persisted variant `id` on update; total stock is UI-derived from variant fields. |
| Admin category/collection screens | `b-bito-admin-suite/src/lib/catalog-api.ts` | `/api/v1/admin/categories`, `/collections` | Staff-only endpoints. |
| Store home/shop/product/collection pages | `bebito-blossom-boutique/src/lib/catalog-api.ts`, route components | `/api/v1/homepage/*`, `/api/v1/shop/*` | Receives price in minor units and active variant stock. |
| Cart/checkout | storefront cart plus `/api/v1/store/checkout` | variant ID/quantity only | Client display values are not trusted at checkout. |

## Visibility rules

- Public product list/detail/homepage: product status is `published`, product not soft deleted.
- Public product variants: `is_active = true`; a retired variant is never output or purchasable.
- Public category/collection lists: `is_active = true`.
- Category/collection counts: only published products.
- Admin catalog routes: staff guard plus section permissions; public routes are read-only.

## Non-sources and unsafe inputs

- Browser cart unit prices, totals, discounts, and stock values are not pricing/inventory sources.
- `stock_quantity` in an admin request is checked for UI consistency when provided but never persisted as a caller-authoritative total.
- Asset ID is valid only within its product resource scope; an arbitrary asset ID cannot change another product's thumbnail.
- `product_prices` is not the current checkout source; do not read it as an alternate price without an explicit pricing design change.
