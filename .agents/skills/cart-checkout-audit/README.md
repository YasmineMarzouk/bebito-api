
# Cart & Checkout Audit Skill — Codex

## Install

Copy the `cart-checkout-audit` folder to:

`<laravel-repo>/.agents/skills/cart-checkout-audit/`

## Invoke

```text
$cart-checkout-audit

Audit and harden the complete cart and checkout flow for the storefront.

Integrate with the audited product/inventory, coupon, authentication, and order modules.

Implement automated tests for every applicable scenario, fix findings, and run the complete relevant suite.
```

## Contents

- `SKILL.md`: full audit workflow and completion criteria
- `references/database-schema.md`: observed schema based on previously uploaded SQL archive; migrations/current schema authoritative
- `references/test-scenarios.md`: exhaustive cart/checkout test catalog
- `references/concurrency-testing.md`: duplicate checkout, stock/coupon races, rollback
- `templates/audit-report-template.md`
- `templates/test-matrix-template.md`

## Important schema note

The SQL archive did not include `carts.sql` or `cart_items.sql`.

The skill explicitly requires Codex to inspect migrations and code to determine cart storage and never invent cart tables.
