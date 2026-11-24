# Task 01 — Pack Database

Read orchestrator and architecture references first.

Inspect actual Product schema and conventions, then design and implement Pack schema/migrations/relations for:
- Pack catalog fields and lifecycle/first-publication marker.
- Components referencing standalone products only, positive quantity, unique product per Pack.
- Pack variants and explicit component variant mapping, using existing variant conventions.
- Categories, collection, tags, media relations.
- Order snapshots as required by existing order architecture.

Never add Pack stock quantity, reserved stock, or independent Pack inventory tables/balances.

Use foreign keys, indexes, unique constraints, deletion policies, and rollback-safe migrations. Document any constraint that cannot be expressed in DB and enforce it in the appropriate service layer.

Run migration and schema tests; report exact files and results.
