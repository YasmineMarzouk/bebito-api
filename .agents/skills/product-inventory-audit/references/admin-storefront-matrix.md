# Admin vs Storefront Responsibility Matrix

Use this as a starting point, then map to the repository's real routes and permission names.

| Capability | Admin dashboard | Customer storefront |
|---|---|---|
| Browse public products | Allowed according to staff permission | Allowed for guest/customer |
| Read unpublished products | Authorized catalog staff only | Denied/not exposed |
| Create/update/delete product | Permission-specific | Never |
| Change product status/featured/position | Permission-specific | Never |
| Create/update variants and attributes | Permission-specific | Never |
| Set SKU | Permission-specific | Never |
| Set price/compare-at/discount | Permission-specific | Never; client price ignored |
| Read current price | Authorized admin | Public purchasable variant only |
| Set inventory quantity/threshold | Inventory permission | Never |
| Read availability | Authorized staff | Public availability only |
| Create/edit inventory movements | Only via authorized stock workflow | Never |
| Upload/attach/reorder/delete assets | Product/media permission and ownership | No direct mutation |
| Attach categories/collections/tags | Permission-specific | Never |
| Change translations/slugs/SEO | Permission-specific | Never |
| Set homepage flags/position | Permission-specific | Never |
| Add to cart / checkout | Not an admin function | Allowed; server revalidates |
| View internal audit/movement history | Authorized staff | Never |

For every row, test unauthenticated, customer, staff without permission, and authorized staff. Where the application separates view/create/update/delete permissions, test each independently. Do not use generic “admin can do everything” assumptions without verifying the permission implementation.
