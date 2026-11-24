# Pack test coverage matrix

This matrix maps the Pack scenario IDs to executable tests and load assets. Status reflects this change only: the user requested that tests not be run, so no scenario is recorded as passed.

| Scenario IDs | Test / file | Type | Command | Status | Reason |
| --- | --- | --- | --- | --- | --- |
| CAT-01 to CAT-16 | — | Feature/API | `php artisan test --compact tests/Feature/Catalog/PackSchemaTest.php` | unimplemented | Catalog create/update/delete/authorization tests still need authenticated admin fixtures and uploaded-image coverage. |
| CMP-01 | `PackCompositionTest::test_two_and_three_component_compositions_with_complete_mappings_are_valid` | Feature/database | `php artisan test --compact tests/Feature/Catalog/PackCompositionTest.php` | skipped | Implemented; execution deferred by request. |
| CMP-02, CMP-04, CMP-05 | `PackCompositionTest::test_fewer_than_two_components_are_rejected`, `test_zero_or_negative_component_quantity_is_rejected`, and `test_duplicate_components_are_rejected` | Feature/database | `php artisan test --compact tests/Feature/Catalog/PackCompositionTest.php` | skipped | Implemented; execution deferred by request. |
| CMP-09, VAR-03 | `PackCompositionTest::test_mapping_a_variant_from_another_product_is_rejected` | Feature/database | `php artisan test --compact tests/Feature/Catalog/PackCompositionTest.php` | skipped | Implemented; execution deferred by request. |
| CMP-03, CMP-06 to CMP-08, CMP-10, CMP-11 | — | Feature/API | — | unimplemented | Draft mutation, deletion/ineligible products, Pack-as-component entry points, missing mappings, rollback, and projection checks remain. |
| PUB-03, PUB-06 | `PackCompositionTest::test_first_publication_permanently_locks_composition_but_not_pack_catalog_fields` | Feature/database | `php artisan test --compact tests/Feature/Catalog/PackCompositionTest.php` | skipped | Implemented; execution deferred by request. |
| PUB-01, PUB-02, PUB-04, PUB-05, PUB-07 to PUB-09 | — | Feature/concurrency | — | unimplemented | Requires authenticated publication paths and a dedicated MySQL contention fixture. |
| VAR-01 | `PackCompositionTest::test_component_variants_are_derived_by_their_shared_size_and_color` | Feature/database | `php artisan test --compact tests/Feature/Catalog/PackCompositionTest.php` | skipped | Implemented; execution deferred by request. |
| VAR-02, VAR-04 to VAR-11 | — | Feature | — | unimplemented | Variant lifecycle and multi-variant availability cases remain. |
| PRI-01 to PRI-05, PRI-08, PRI-09 | `PackCompositionTest::test_component_price_changes_refresh_compare_at_price_without_changing_manual_sell_price` | Feature/database | `php artisan test --compact tests/Feature/Catalog/PackCompositionTest.php` | skipped | Implemented; execution deferred by request. |
| PRI-06, PRI-07, PRI-10, PRI-11 | — | Unit/concurrency | — | unimplemented | Rounding, zero-price, lifecycle, and concurrent-price fixtures remain. |
| INV-02, INV-06, INV-13, INV-16 | `PackAvailabilityTest::test_it_derives_stock_from_the_lowest_component_capacity_without_persisting_pack_stock` | Feature/database | `php artisan test --compact tests/Feature/Catalog/PackAvailabilityTest.php` | skipped | Implemented; execution deferred by request. |
| INV-01, INV-03 to INV-05, INV-07 to INV-12, INV-14, INV-15, INV-17, INV-18 | — | Feature/performance | — | unimplemented | Multi-variant, stale-cache, and query-count cases remain. |
| SH-02, SH-05, SH-07 | `PackCheckoutTest` reservation and rollback tests | Feature/API/database | `php artisan test --compact tests/Feature/PackCheckoutTest.php` | skipped | Implemented; execution deferred by request. |
| SH-01, SH-03, SH-04, SH-06, SH-08 to SH-11 | — | Feature | — | unimplemented | Shared-Packs, restoration, stock adjustment, movement history, and invalid-adjustment coverage remain. |
| CHK-01, CHK-03, CHK-04, CHK-10, CHK-11 | `PackCheckoutTest` | Feature/API/database | `php artisan test --compact tests/Feature/PackCheckoutTest.php` | skipped | Implemented; execution deferred by request. |
| CHK-02, CHK-05 to CHK-09, CHK-12 | — | Feature/API | — | unimplemented | Cart behavior, deactivation, revalidation-policy, coupon, delivery, and total coverage remain. |
| ORD-01 to ORD-13 | — | Feature/API | — | unimplemented | Admin order mutation, cancellation, lifecycle, and historical-order coverage remain. |
| EVT-01 to EVT-11 | — | Feature/queue | — | unimplemented | Pack events, cache invalidation, and projection contracts are not implemented. |
| UI-01 to UI-10 | — | Frontend/E2E | — | unimplemented | Dashboard and storefront E2E harnesses are not yet present. |
| CON-01 inventory-reservation portion | `PackCheckoutContentionTest::test_two_independent_customers_competing_for_the_last_pack_cannot_oversell` | MySQL integration/concurrency | `PACK_CONCURRENCY_ENABLED=1 php artisan test --compact tests/Concurrency/PackCheckoutContentionTest.php` | skipped | Implements two independently booted reservation workers; it requires an isolated MySQL test database and was not run. |
| CON-01 full checkout assertions, CON-02 to CON-22 | — | MySQL integration/concurrency | — | unimplemented | Full checkout/order snapshot, additional coordinated-worker profiles, and order/admin contention cases remain. |
| LOAD-01, LOAD-04, LOAD-06, LOAD-09, LOAD-10 | `tests/Load/pack-catalog.k6.js` | k6 load | `k6 run tests/Load/pack-catalog.k6.js` | skipped | Implemented profiles and guarded test-target validation; requires dedicated seeded `.test` data and k6. |
| LOAD-02, LOAD-03, LOAD-05, LOAD-07, LOAD-08, LOAD-11 to LOAD-18 | — | k6 load | — | unimplemented | Data setup/cleanup, query inspection, mutation, cache, queue, contention, and mixed-traffic assets remain. |

The concurrency and load commands must only run against isolated test infrastructure. The k6 script refuses non-localhost/non-`.test` targets and leaves mutations disabled unless `PACK_LOAD_ENABLE_MUTATIONS=true` is explicitly supplied.
