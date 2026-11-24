# Product & Inventory Audit Skill (Codex)

This skill audits and hardens Bébito's Laravel catalog/inventory modules from both admin and storefront perspectives, and requires automated tests for every applicable scenario.

## Install
Copy the `product-inventory-audit` directory into the Laravel repository:

```text
<laravel-repo>/.agents/skills/product-inventory-audit/
```

If your Codex setup uses another project skill directory, use its configured location. Keep the folder name and `SKILL.md`.

## Invoke

```text
$product-inventory-audit Audit and harden the product/catalog and inventory modules end-to-end for the admin dashboard and customer storefront. Implement automated tests for every applicable scenario, fix findings, and verify the complete suite.
```

## Included references
- `references/database-schema.md` — observed table/column map from uploaded dump; migrations/current schema are authoritative.
- `references/test-scenarios.md` — extensive automated test catalog.
- `references/concurrency-testing.md` — true DB concurrency scenarios and load profiles.
- `references/admin-storefront-matrix.md` — capability and permission boundaries.
- `templates/audit-report-template.md` — required report structure.

## Safety
Use isolated test DB only. Never run destructive or mutating audit operations against production. Do not claim complete coverage unless tests ran and all applicable scenarios are passing or explicitly blocked/N/A with evidence.
