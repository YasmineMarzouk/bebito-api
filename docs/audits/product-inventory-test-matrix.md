# Product & Inventory Test Matrix

| ID | Area | Scenario | Test class::method | Status (PASS/FAIL/BLOCKED/N/A) | Evidence / reason |
|---|---|---|---|---|---|
| CAT-001 | Storefront | Published product list/detail | `ShopCatalogTest::test_the_public_shop_product_endpoint_lists_only_published_products`; `...detail...` | PASS | Automated API tests passed. |
| CAT-002 | Storefront | Draft product hidden by list and ID | `ShopCatalogTest::test_the_public_shop_product_endpoint_lists_only_published_products`; `...detail...` | PASS | Automated API tests passed. |
| CAT-003 | Storefront | Active category and collection listing | `ShopCatalogTest::test_the_public_shop_category_endpoint_lists_only_active_categories`; `...collection...` | PASS | Automated API tests passed. |
| CAT-004 | Storefront | Collection filter and discounted filter | `ShopCatalogTest::test_the_public_shop_product_endpoint_can_filter_by_collection`; `...discounted...` | PASS | Automated API tests passed. |
| CAT-005 | Storefront | Hidden data excluded from counts/taxonomy | `ShopCatalogTest::test_public_catalog_counts_and_product_categories_exclude_unpublished_and_inactive_data` | PASS | Automated API test passed. |
| CAT-006 | Storefront | Pagination/search/server sorting | — | N/A | No such API contract exists; routes return the full public list. |
| ADM-001 | Authorization | Customer cannot use admin category/collection routes | `ProductCatalogInventoryTest::test_staff_can_manage_categories_and_collections_while_a_customer_cannot_use_admin_catalog_routes` | PASS | Automated API test passed. |
| ADM-002 | Authorization | Staff without product read permission denied | `StaffAuthorizationTest::test_staff_without_an_assigned_permission_cannot_read_admin_catalog_data` | PASS | Automated API test passed. |
| ADM-003 | Authorization | Read cannot create/update/delete | `ProductCatalogInventoryTest::test_staff_with_read_only_product_permission_cannot_mutate_products` | PASS | Automated API test passed. |
| ADM-004 | Authorization | Write and execute enforced independently | `ProductCatalogInventoryTest::test_product_write_and_execute_permissions_are_enforced_independently` | PASS | Automated API test passed. |
| ADM-005 | Admin CRUD | Product create with staff attribution | `ProductCatalogInventoryTest::test_staff_creation_derives_product_stock_sets_a_primary_image_and_records_initial_inventory` | PASS | Automated API test passed. |
| ADM-006 | Admin CRUD | Category/collection create/update/delete | `ProductCatalogInventoryTest::test_staff_can_manage_categories_and_collections_while_a_customer_cannot_use_admin_catalog_routes` | PASS | Automated API test passed. |
| VAL-001 | Validation | Negative stock rejected atomically | `ProductCatalogInventoryTest::test_product_creation_rejects_negative_stock_and_a_non_image_upload_without_writing_catalog_data` | PASS | Automated API test passed. |
| VAL-002 | Validation | Non-image upload rejected atomically | `ProductCatalogInventoryTest::test_product_creation_rejects_negative_stock_and_a_non_image_upload_without_writing_catalog_data` | PASS | Automated API test passed. |
| VAL-003 | Validation | Invalid enum/value/string boundary corpus | — | N/A | No complete allowed size/color specification exists; enum status/gender are validated. |
| VAR-001 | Variants | Stable IDs survive update | `ProductCatalogInventoryTest::test_product_update_preserves_variant_identity_derives_stock_and_records_adjustments` | PASS | Automated API test passed. |
| VAR-002 | Variants | Cross-product variant ID rejected | `ProductCatalogInventoryTest::test_product_update_rejects_a_variant_owned_by_another_product` | PASS | Automated API test passed. |
| VAR-003 | Variants | Removed variant retired, not erased | `ProductCatalogInventoryTest::test_removing_a_variant_retires_it_and_prevents_customer_checkout_without_erasing_history` | PASS | Automated API test passed. |
| VAR-004 | Variants | Default variant deterministic | `ProductCatalogInventoryTest::test_staff_creation_derives_product_stock_sets_a_primary_image_and_records_initial_inventory` | PASS | First active variant is default; update normalizes one default. |
| PRC-001 | Pricing | Price/compare-at/discount written consistently | `ProductCatalogInventoryTest::test_product_update_preserves_variant_identity_derives_stock_and_records_adjustments` | PASS | Product and active TND variant prices are synchronized. |
| PRC-002 | Checkout | Client price/stock fields ignored | `CreateCheckoutTest::test_checkout_ignores_client_supplied_price_fields_and_uses_the_authoritative_catalog_price` | PASS | Automated API test passed. |
| PRC-003 | Currency | Duplicate variant currency price | — | N/A | No per-variant currency-price mutation endpoint exists in the current API; schema constraint is documented but not claimed as an automated test. |
| INV-001 | Inventory | Item created per variant | `ProductCatalogInventoryTest::test_staff_creation_derives_product_stock_sets_a_primary_image_and_records_initial_inventory` | PASS | Automated API test passed. |
| INV-002 | Inventory | Product summary derived from variants | `ProductCatalogInventoryTest::test_product_update_preserves_variant_identity_derives_stock_and_records_adjustments` | PASS | Mismatched supplied total rejected; final summary asserted. |
| INV-003 | Inventory | Initial/manual movement records | `ProductCatalogInventoryTest::test_staff_creation_derives_product_stock_sets_a_primary_image_and_records_initial_inventory`; `...update...` | PASS | Signed values, reason, and staff attribution asserted. |
| INV-004 | Inventory | Retired variant cannot purchase | `ProductCatalogInventoryTest::test_removing_a_variant_retires_it_and_prevents_customer_checkout_without_erasing_history` | PASS | Automated checkout rejection passed. |
| INV-005 | Inventory | Insufficient stock | `CreateCheckoutTest::test_checkout_rejects_a_variant_with_insufficient_inventory` | PASS | Automated API test passed. |
| INV-006 | Inventory | Checkout rollback after failure | `CreateCheckoutTest::test_checkout_rolls_back_stock_and_order_when_a_coupon_has_reached_its_usage_limit` | PASS | Stock/movement/order state asserted. |
| INV-007 | Inventory | Last-unit and adjustment/checkout races | — | BLOCKED | Requires independent connections on isolated MySQL/MariaDB; SQLite memory cannot prove locks. |
| AST-001 | Assets | Exactly one primary image under sequential changes | `PrimaryImageTest::test_an_asset_resource_always_has_exactly_one_primary_image` | PASS | Automated persistence test passed. |
| AST-002 | Assets | Cross-product primary asset rejected | `ProductCatalogInventoryTest::test_primary_image_endpoint_rejects_an_asset_from_another_product_without_changing_the_current_primary_image` | PASS | Automated API test passed. |
| AST-003 | Assets | Primary-image concurrent race | — | BLOCKED | Requires independent DB connections on production-equivalent engine. |
| AST-004 | Assets | Forged/polyglot/corrupt image corpus | — | N/A | Laravel MIME/image rules are present; no project-approved malware/corrupt-file fixture corpus exists. |
| REL-001 | Relationships | Valid category/collection management | `ProductCatalogInventoryTest::test_staff_can_manage_categories_and_collections_while_a_customer_cannot_use_admin_catalog_routes` | PASS | Automated API test passed. |
| REL-002 | Relationships | Public inactive taxonomy hidden | `ShopCatalogTest::test_public_catalog_counts_and_product_categories_exclude_unpublished_and_inactive_data` | PASS | Automated API test passed. |
| ORD-001 | Order integration | Soft deletion preserves order item and movement history | `ProductCatalogInventoryTest::test_product_delete_soft_deletes_the_catalog_entry_and_preserves_order_and_inventory_history` | PASS | Automated API test passed. |
| ORD-002 | Order integration | Duplicate item line prevention | `CreateCheckoutTest::test_checkout_rejects_duplicate_variant_lines_before_reserving_stock` | PASS | Automated API test passed. |
| REG-001 | Regression | P1 variant replacement/data loss | `ProductCatalogInventoryTest::test_removing_a_variant_retires_it_and_prevents_customer_checkout_without_erasing_history` | PASS | Automated API test passed. |
| REG-002 | Regression | P1 hard deletion/history loss | `ProductCatalogInventoryTest::test_product_delete_soft_deletes_the_catalog_entry_and_preserves_order_and_inventory_history` | PASS | Automated API test passed. |
