---
name: pack-module
description: Orchestrate implementation, testing, concurrency validation, load testing, and production readiness for Bébito's Pack catalog module.
---

# Bébito Pack Module Orchestrator

Use this skill when implementing, auditing, testing, or modifying the Pack catalog module.

## Mandatory first steps

1. Read `docs/architecture/architecture.md` completely.
2. Read `references/business-rules.md`, `references/inventory-rules.md`, `references/architecture.md`, and `references/testing-strategy.md`.
3. Inspect the existing Product, inventory, cart, checkout, order, media, category, collection, tag, variant, event/listener, queue, and frontend implementations.
4. Do not assume database or architectural details not verified in the repository.
5. Follow the documented architecture and established project conventions. If a requirement conflicts with architecture, report the conflict and resolve it according to the architecture before implementation.

## Task execution order

Execute the task instructions in order:

1. `tasks/01-pack-database.md`
2. `tasks/02-pack-backend.md`
3. `tasks/03-pack-composition.md`
4. `tasks/04-pack-inventory.md`
5. `tasks/05-pack-checkout-orders.md`
6. `tasks/06-pack-admin-frontend.md`
7. `tasks/07-pack-storefront.md`
8. `tasks/08-pack-functional-tests.md`
9. `tasks/09-pack-concurrency-tests.md`
10. `tasks/10-pack-load-tests.md`
11. `tasks/11-pack-production-readiness.md`

Do not skip a task silently. Tasks may be split into smaller implementation batches, but preserve dependencies and report progress.

## Core constraints

- A Pack is a virtual catalog entity composed of at least two standalone products.
- A Pack cannot contain another Pack.
- Pack stock is derived from component inventory and must never be independently persisted.
- Pack variant availability is derived from explicitly mapped component variants.
- Pack composition is editable only before its first publication.
- Once published for the first time, composition and component variant mappings remain permanently immutable, even after deactivation or unpublishing.
- Other permitted Pack catalog fields remain editable after publication.
- Product and Pack purchases share the same authoritative inventory.
- Inventory validation, deduction, restoration, and order consistency must remain synchronous and atomic.
- Use events/listeners for decoupled side effects when appropriate; do not make inventory correctness depend on asynchronous listeners.
- Admin Pack create/edit pages must follow the existing Product form design and reuse components/patterns.
- All frontend availability is informational; backend checkout revalidates authoritative inventory.
- Historical orders must preserve Pack and component snapshots.
- Do not introduce a parallel inventory, cart, or order system.

## Execution discipline

For each task:

1. Inspect relevant code.
2. State a concise implementation plan.
3. Implement the task.
4. Add/update tests.
5. Execute relevant tests.
6. Review for architecture, security, inventory, and performance issues.
7. Report files changed, tests run/results, and unresolved issues.

Do not claim a test passed unless it was actually executed successfully. Do not claim production readiness without evidence.
