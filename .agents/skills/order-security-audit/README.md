# Codex Skill: Order Security Audit

A reusable Codex skill for auditing and testing a Laravel ecommerce order module.

## Install

1. Extract this ZIP in your Laravel repository.
2. Copy the folder `order-security-audit` into:
   `.agents/skills/order-security-audit/`
3. Confirm the resulting structure:

```text
your-laravel-project/
├── AGENTS.md
└── .agents/
    └── skills/
        └── order-security-audit/
            ├── SKILL.md
            ├── README.md
            ├── references/
            └── templates/
```

Codex discovers repository skills from `.agents/skills/`. If your Codex setup uses another configured skill directory, install it there instead.

## Invoke in Codex

```text
$order-security-audit Audit the entire order module and implement the test suite.
```

Other examples:

```text
$order-security-audit Audit inventory concurrency only; do not modify runtime code.
```

```text
$order-security-audit Review my latest cancellation changes for security regressions and add tests.
```

```text
$order-security-audit Run the order audit and produce a production readiness report.
```

You can also let Codex select the skill based on its description when the task clearly matches.

## Project assumptions

- Laravel application.
- Customer and admin authentication contexts are separate; preserve the existing design.
- Payment is exclusively Cash on Delivery (COD).
- There is no online payment gateway in scope.

The skill includes a project-specific schema map from the supplied SQL data dump. That dump contains INSERT data, not DDL; Codex must verify migrations and live schema for types, nullability, indexes, constraints, and defaults before implementing changes.

## Included files

- `SKILL.md`: mandatory workflow and guardrails.
- `references/database-schema.md`: project-specific table/column map extracted from the uploaded SQL data dump, with explicit evidence limitations.
- `references/test-scenarios.md`: detailed test scenario catalog, including coupon cases using the actual column names.
- `references/security-checklist.md`: audit acceptance checklist.
- `references/concurrency-testing.md`: real database concurrency and k6 guidance.
- `references/audit-report-template.md`: report structure.
- `templates/test-plan-template.md`: scenario tracking template.
