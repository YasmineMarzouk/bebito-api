# Architecture Requirements

This reference supplements—but never replaces—the repository's `docs/architecture/architecture.md`.

## Required discovery

Inspect actual:
- Product model/schema/relations, services, repositories (if used), controllers, resources, validation, policies, routes.
- Variant and inventory schema/services/movement history/reservations.
- Cart, checkout, order item snapshots, order editing, cancellation/returns.
- Media, category, collection, tag, and gender mechanisms.
- Admin React Product create/edit/list pages and component/form state conventions.
- Storefront product listing/detail/cart/checkout.
- Events, listeners, queues, after-commit behavior, retries, and idempotency.
- Testing framework, factories, database engine, CI, and load-test tooling.

## Architecture rules

- Follow `docs/architecture/architecture.md` and established patterns.
- Reuse Product/catalog infrastructure where appropriate; avoid parallel systems.
- Keep business logic in the documented layer, not controllers or UI.
- Reuse centralized inventory operations for every stock mutation.
- Pack stock calculation should be a reusable query/service, not duplicated across endpoints.
- Use targeted eager loading/query optimization; avoid N+1.
- Use events/listeners for decoupled side effects such as cache invalidation, search indexing, analytics, and notifications where appropriate.
- Prefer after-commit dispatch for side effects requiring committed data.
- Make retried queued listeners idempotent.
- Inventory validation, deduction, restoration, and order consistency must be synchronous and transactional.
- Never rely on an asynchronous listener to make checkout safe.
- Do not invent service names, schema, route conventions, or framework abstractions before inspecting the project.
- Document any architecture conflict and resolve it before proceeding.
