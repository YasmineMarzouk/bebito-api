---
name: admin-security-audit
description: Audit, harden, test, and verify all Bébito Laravel administrative operations for security, authorization, business integrity, concurrency safety, and operational reliability. Use when performing an admin operations security audit, reviewing admin API behavior, or improving admin-side reliability.
---

# Bébito Admin Operations Security & Reliability Audit

## Purpose

Perform an evidence-based, implementation-led audit of all administrative operations in Bébito. Discover the actual admin capabilities, identify security and integrity weaknesses, implement justified fixes, and add automated tests proving the behavior.

This is an **audit-and-remediation skill**, not a generic code review checklist and not a request to rebuild the admin dashboard.

The platform currently includes:
- Laravel backend/API.
- React admin dashboard.
- React customer storefront.
- Ecommerce features whose previous audit suites cover Authentication, Orders, Product & Inventory, and Cart & Checkout.
- Cash on Delivery (COD) as the currently implemented payment method (verify the repository before relying on this assumption).

The intended outcome is a more secure, consistent, and reliable application—not a claim that software is completely immune to attacks.

## When to use

Use this skill when:
- Auditing administrative APIs or dashboard operations.
- Securing admin authorization or preventing privilege escalation.
- Reviewing admin create/update/delete/publish/activate operations.
- Testing admin inventory, order, customer, content, coupon, or configuration operations.
- Investigating admin-side data corruption, race conditions, or partial failures.
- Extending admin security and reliability test coverage.

## Non-negotiable rules

### 1. Repository instructions and existing architecture
1. Read and follow the repository's `AGENTS.md`, `CLAUDE.md`, and applicable skills before work.
2. Inspect existing security audit skills and suites; reuse their conventions, helpers, fixtures, and test setup.
3. Inspect Git status and preserve all existing user changes. Never reset, clean, discard, overwrite, or revert unrelated work.
4. Do not commit, push, or deploy unless explicitly requested.
5. Do not rewrite architecture or introduce a new authorization framework if the existing one can be secured correctly.
6. Do not duplicate business logic already implemented in services/actions/domain layers.
7. Do not weaken middleware, policies, validation, or tests to make a test pass.
8. Keep changes scoped. Fix cross-module defects when necessary for safety, but document why they were touched.

### 2. Database schema is a moving snapshot
Any supplied database schema is supporting evidence only—not complete, immutable, or authoritative.

The project is under development. Tables, columns, constraints, relationships, and features may have changed or may not appear in the snapshot.

Before conclusions or schema changes, inspect:
- Current migrations and migration history.
- Models, casts, fillable/guarded fields, relationships, scopes, observers, and events.
- Services/actions/repositories and query logic.
- Routes, middleware, policies, gates, Form Requests, controllers.
- Current database configuration and engine.
- Existing tests and factories.
- Actual frontend API usage.

Never infer that a feature does not exist because it is absent from a supplied schema. Never change the database merely to match a snapshot. Make a migration only when the actual implementation requires it; explain its rationale, compatibility, rollback implications, and tests.

### 3. Evidence, safety, and truthful reporting
- Findings must be grounded in code, runtime behavior, or reproducible tests—not assumptions.
- Distinguish confirmed vulnerabilities from hypotheses, defense-in-depth suggestions, and untested risks.
- Never claim a test passed unless it was executed and its result inspected.
- Never claim concurrency safety from sequential tests alone.
- Do not claim complete security, exhaustive coverage, or production readiness without evidence.
- Do not run destructive commands against production or shared environments.
- Keep secrets, credentials, tokens, and sensitive customer data out of logs, reports, fixtures, and test output.
- Avoid real email, SMS, payment, or external side effects in tests; use safe fakes/stubs where appropriate.
- Do not perform destructive data changes outside an isolated test environment.

---

# Workflow

Execute the phases in order. Do not stop after producing a plan or listing findings: remediate confirmed issues and add tests unless blocked by missing information, unsafe environment, or a change requiring user/business approval.

## Phase 0 — Establish a safe baseline

1. Read repository instructions and relevant skills.
2. Inspect `git status`, current branch, and existing changes.
3. Identify backend/frontend roots and test commands from project configuration (Composer, package manifests, CI workflows, README).
4. Identify test environment and database engine. Verify that tests cannot target production.
5. Inspect previous audit suites for Authentication, Orders, Product & Inventory, and Cart & Checkout:
   - test naming and organization;
   - factories and fixtures;
   - auth helpers;
   - API test conventions;
   - database refresh strategy;
   - known invariants and regression tests.
6. Run a suitable baseline test subset if safe and practical. Record pre-existing failures separately; do not attribute them to this audit.
7. Establish a baseline of relevant application behavior before modifications.

**Stop and ask for clarification** if a required operation could affect production/shared data, credentials are needed, or a business rule cannot safely be inferred. Continue other independent, safe audit work.

## Phase 1 — Discover and inventory administrative capabilities

Discover actual admin features from the repository; do not assume the list in this skill is exhaustive.

Inspect React admin navigation, pages, forms, actions, API clients, route definitions, and backend routes/controllers. Trace each write/read operation through the backend to its authorization and business logic.

Create or update an audit inventory, preferably in a project-appropriate audit document. For each operation record:

| Field | What to record |
|---|---|
| Feature/resource | Actual admin capability |
| Operation | List/read/create/update/delete/restore/publish/activate/status-change/etc. |
| Frontend entry | Page/component/action, if applicable |
| API | HTTP method and route |
| Authentication | Middleware/guard |
| Authorization | Policy/gate/role/permission/check |
| Validation | Form Request or validator |
| Business logic | Service/action/model/event/observer |
| Data effects | Tables/models/relationships changed |
| Side effects | Files, jobs, notifications, cache, external services |
| Invariants | Rules that must remain true |
| Existing tests | Relevant test files |
| Risk/coverage | Known risk and test status |

Include read endpoints that expose sensitive data, not only write operations. Include routes not linked in the UI if they are registered and reachable.

Group discovered operations into domains where applicable:
- Admin accounts, roles, permissions, and access control.
- Products, translations, variants, SKUs, prices, media.
- Categories, collections, tags, catalog publication.
- Inventory and manual adjustments.
- Orders, statuses, cancellation, fulfillment, payment records.
- Customers, profiles, addresses, notes, order history.
- Coupons/promotions.
- Store settings, locales, currencies, countries.
- Homepage sections, blog/content, contact messages.
- Asset/file management.
- Notifications, reports, dashboards, exports/imports.
- Any additional implemented feature.

## Phase 2 — Authentication, authorization, and privilege escalation

For **every admin API endpoint**, verify backend-enforced controls. React visibility, disabled buttons, route guards, and hidden menus are not authorization.

### Authentication
Test/inspect:
- Unauthenticated request.
- Invalid, expired, or revoked credentials/session.
- Authenticated ordinary customer.
- Authenticated admin.
- Credential/session lifecycle as implemented.
- Admin endpoint direct access outside the React application.
- Consistent behavior across route groups and alternate endpoints.

Do not assume a specific token/session library; inspect the actual guard and middleware.

### Authorization
Verify:
- Admin access is enforced server-side on every protected endpoint.
- Policies/gates/permissions apply to the correct resource and action.
- Least privilege is respected where roles/permissions exist.
- Ordinary users cannot escalate by changing request fields, IDs, headers, or routes.
- Role/permission assignment itself is protected.
- Sensitive read endpoints are authorized, not just mutations.
- Bulk actions, exports, nested routes, and alternate endpoints receive equivalent checks.
- Authorization cannot be bypassed by invoking a service through an exposed route or alternate controller.
- Failure responses do not disclose sensitive resource existence or data unnecessarily.

### IDOR/BOLA and relationship authorization
Attempt cross-resource substitutions for every relevant identifier:
- order, customer, address, note;
- product, variant, inventory item, price;
- category, collection, tag;
- coupon, asset, blog post, homepage section;
- any other discovered resource.

Verify ownership/relationship consistency and authorization at the backend. Ensure an admin cannot attach a resource belonging to the wrong parent, and ordinary users cannot use admin endpoints to bypass customer ownership rules.

### Privilege escalation and mass assignment
Inspect request validation, DTOs, `$fillable`/`$guarded`, model updates, and relationship synchronization. Test protected fields such as:
- IDs and ownership/author/user attribution;
- roles, permissions, admin flags;
- internal statuses and timestamps;
- stock quantities or movement attribution;
- order totals, discounts, payment fields;
- relationship IDs and nested objects.

Allow fields only where the legitimate operation explicitly requires them. Do not blindly reject legitimate domain fields or rely solely on model mass-assignment protection.

## Phase 3 — Input validation and API robustness

For every write endpoint, verify backend validation and safe handling of:
- missing required values;
- wrong scalar/array/object types;
- null, empty, whitespace-only values;
- malformed JSON and nested payloads;
- unknown/unexpected keys;
- oversized strings, arrays, uploads, and payloads;
- numeric boundaries, overflow, negative values, decimals where integers are expected;
- invalid enum/status values;
- invalid locale/currency/country codes;
- nonexistent IDs and wrong-parent IDs;
- duplicate relationship IDs;
- invalid combinations of fields;
- Unicode, encoding, and normalization edge cases where relevant.

Confirm that frontend validation is only a usability layer. Verify consistent API error shape and no stack traces, SQL details, secrets, or sensitive records leak in production-style responses.

Use actual business rules and model constraints. Do not invent validation rules that conflict with existing behavior.

## Phase 4 — Business invariants and operation-specific audit

For each discovered operation, define:
1. Preconditions.
2. Authorized actor.
3. Allowed state(s).
4. Accepted input.
5. Expected database changes.
6. Expected relationships/history.
7. Side effects.
8. Failure/rollback behavior.
9. Idempotency/retry expectations.
10. Forbidden outcomes.

Audit actual code and test these invariants.

### Product/catalog administration
Inspect and test:
- create/update/delete/restore/archive/publish/unpublish;
- product and translation completeness;
- variant parent ownership;
- SKU uniqueness and normalization;
- price and compare-at-price validity;
- currency consistency;
- category/collection/tag attachment;
- duplicate and invalid relationship handling;
- product media association and primary/sort order;
- publishing while required data is missing;
- concurrent edit/publication;
- deletion when orders or other historical references exist;
- safe behavior for soft deletes and hard deletes.

Do not destroy historical order snapshots or records required for reporting.

### Inventory administration
Inspect and test:
- manual adjustment validation and authorization;
- atomic quantity change and movement creation;
- correct signed quantity/reason/reference;
- trusted actor attribution from authenticated identity, never arbitrary client input;
- no negative stock unless actual business rules explicitly allow it;
- concurrency with other admins and customer checkout;
- repeated requests and retry behavior;
- order cancellation/restoration interaction;
- inventory item/variant parent consistency;
- movement history integrity;
- failure between stock update and movement insert;
- reconciliation and handling of missing inventory rows.

If stock and movement history represent one operation, verify they commit or roll back together.

### Order administration
Discover actual order statuses and transition rules from code/configuration; do not invent a state machine.

Test:
- each permitted transition;
- every forbidden transition;
- repeated transition;
- concurrent transition;
- cancellation versus fulfillment;
- cancellation versus another cancellation;
- stock restoration exactly once where applicable;
- status history and authenticated actor attribution;
- payment/order consistency for COD and any other implemented methods;
- totals, coupon snapshots, and order item snapshots remain protected;
- missing/deleted related entities;
- transaction rollback and side effects;
- admin changes do not bypass customer/order module invariants.

Do not allow arbitrary edits to financial snapshots unless a specific legitimate operation supports them.

### Customer administration
Test:
- sensitive customer reads and exports;
- customer notes create/update/delete and author attribution;
- address/profile changes and relationship checks;
- access to orders and personal information;
- customer deletion/anonymization if implemented;
- no unintended changes to customer authentication or account ownership;
- safe handling of deleted/missing customer relationships.

### Coupons/promotions
Test:
- create/edit/activate/deactivate/delete;
- valid type and discount configuration;
- percentage/range constraints and any fixed amount rules actually implemented;
- uniqueness, normalization, case behavior;
- invalid or stale coupon usage;
- concurrent edits and checkout redemption;
- usage limits/records if implemented;
- safe deletion when historical orders reference a coupon;
- consistency with checkout calculation and order snapshots.

### Store settings/content/localization
For settings, homepage sections, blog/content, locales, currencies, countries, contact messages, and all other discovered features, test:
- authorization;
- schema/shape validation;
- allowed keys/types and safe content rendering;
- publication/activation states;
- unique keys/slugs and locale-scoped uniqueness;
- valid ordering and relationship references;
- destructive operations and historical references;
- malformed content and unexpected JSON;
- concurrent updates and lost-update behavior;
- safe handling of untrusted text in React rendering and any HTML/rich-text renderer.

## Phase 5 — Transactions, concurrency, idempotency, and failure recovery

Identify operations with multiple writes or shared mutable state. Evaluate transaction boundaries and concurrency controls based on actual code and database engine.

### Atomicity
Verify that logically indivisible database changes commit together. Examples:
- inventory quantity + movement record;
- order status + status history + stock restoration;
- product + translations + variants + prices + relationships;
- resource update + dependent records.

Inject or simulate failures between writes and verify rollback.

### Concurrency
For operations that can conflict, test simultaneous requests using the production database engine where possible:
- two admins adjusting the same inventory item;
- admin adjustment concurrent with checkout;
- cancellation concurrent with fulfillment;
- duplicate order transition requests;
- two admins editing the same resource;
- coupon changes concurrent with checkout;
- publication concurrent with edit/delete.

Use appropriate locking, atomic SQL updates, unique constraints, optimistic concurrency/versioning, or another justified approach. Do not add locks indiscriminately. Avoid deadlocks and verify retry behavior where used.

A sequential test is not a concurrency test. If true parallel tests cannot be executed in the current environment, add the strongest feasible integration test, clearly record the limitation, and do not claim concurrency has been proven.

### Idempotency and retries
Determine expected retry semantics for each state-changing operation:
- repeated HTTP request;
- double click;
- network timeout after server commit;
- client retry;
- queue retry;
- duplicate job/event delivery.

Ensure duplicate requests do not double-apply stock, create duplicate movements/history, restore stock twice, or repeat irreversible side effects. Use idempotency keys or state-based safeguards only where appropriate to the operation and architecture.

### External side effects
Database transactions do not roll back files, email, notifications, cache, or external APIs.

Verify correct sequencing and failure handling:
- dispatch jobs after commit where appropriate;
- avoid duplicate notifications on retry;
- clean up uploaded/replaced files after database failure;
- do not delete files before a database operation that may fail unless compensation exists;
- ensure cache invalidation reflects committed state;
- document any unavoidable partial-failure window.

## Phase 6 — File upload, asset, and content security

Inspect actual storage disks, upload routes, file serving, and asset model relationships.

Verify:
- server-side allowlists and MIME/content validation;
- size limits;
- extension/content mismatch;
- safe filenames and paths;
- path traversal and arbitrary disk/path manipulation;
- executable or active content upload;
- SVG/HTML or other active-content risks where applicable;
- unauthorized upload/read/delete;
- asset association to correct resource;
- primary image and sort order consistency;
- replacement and deletion behavior;
- orphaned files and orphaned database records;
- storage visibility and public/private access;
- cleanup after failed operations.

Never trust client MIME, filename, extension, or claimed size alone. Avoid making private files public by default. Preserve valid product images and media behavior.

## Phase 7 — Auditability and operational traceability

Inspect existing audit/activity logs, order histories, inventory movements, events, and application logging.

For sensitive operations, verify traceability appropriate to risk:
- authenticated actor;
- action and affected resource;
- meaningful before/after values or change summary;
- timestamp;
- outcome;
- request/correlation context where available.

Focus on inventory, order transitions/cancellations, financial/configuration changes, publication, destructive actions, and role/permission changes.

Ensure:
- actor attribution comes from trusted server context;
- ordinary APIs cannot forge or alter history;
- logs do not contain passwords, tokens, payment secrets, or unnecessary customer PII;
- sensitive audit data has appropriate access and retention;
- failed authorization attempts are logged appropriately without creating log floods.

Do not create a second audit-log system if an existing mechanism can satisfy the need. If traceability is insufficient, document and implement a proportionate solution.

## Phase 8 — Automated test suite

Follow existing test organization and conventions. Prefer Laravel feature/integration tests exercising real routes, middleware, validation, database behavior, and side effects. Add unit tests for isolated logic when useful, but do not rely exclusively on mocked controllers/services.

Create a discoverable admin audit test suite (use repository naming conventions; suggested grouping: authorization, validation, invariants, concurrency, failure recovery, files, regressions).

### Required test matrix

For every endpoint/operation, cover applicable cases:

**Authorization**
- unauthenticated;
- ordinary authenticated user;
- authorized admin;
- insufficient privilege if granular roles exist;
- direct API access;
- IDOR/wrong parent;
- protected-field/privilege escalation;
- sensitive read access.

**Validation**
- valid request;
- missing required fields;
- invalid type/value;
- boundary and oversized input;
- invalid IDs/relationships;
- unexpected/protected fields;
- malformed nested data;
- invalid state/field combinations.

**Business behavior**
- successful operation;
- forbidden operation;
- correct persisted state;
- correct relationships;
- history and actor;
- side effects;
- no unintended changes to unrelated records.

**Failure/recovery**
- exception during multi-write operation;
- transaction rollback;
- file cleanup/compensation;
- job/notification failure;
- retry and duplicate request;
- missing related resource.

**Concurrency**
- concurrent requests for shared state;
- conflicting transitions;
- admin/customer race for inventory;
- duplicate processing.
Use the actual production database engine for locking/constraint guarantees where possible.

**Regression**
- authentication and authorization;
- storefront product visibility;
- cart/checkout/order creation;
- inventory deduction and restoration;
- coupon calculation/redemption;
- customer order history;
- notifications and existing audited workflows.

Tests should assert response AND persisted effects, not only HTTP status. Verify relevant database rows, relationships, stock, histories, financial values, side effects, and rollback. Ensure a test would fail if the underlying invariant were violated.

Use factories and isolated fixtures. Avoid fragile tests tied to implementation details when behavior is the contract. Do not remove or skip tests to obtain a green run.

## Phase 9 — Database and environment realism

Inspect the configured test engine and compare it with production.

If production uses MySQL:
- identify tests run only on SQLite;
- verify MySQL-specific unique/index/JSON/foreign-key behavior;
- validate row locking and transaction isolation on MySQL;
- validate race-sensitive updates using parallel connections/processes where feasible;
- verify migrations against the intended engine.

Do not claim production-engine guarantees from SQLite tests.

Test setup must be isolated and safe. Confirm database name/config before migrations or cleanup. Never run destructive test commands against production or shared data.

## Phase 10 — Remediation and verification

For every confirmed finding:
1. Record reproduction and impact.
2. Add a regression test that fails before the fix where practical.
3. Implement the smallest correct fix.
4. Run the focused test.
5. Run related module tests.
6. Run the full backend test suite when feasible.
7. Run relevant frontend checks (typecheck/lint/build/tests) if admin changes affect React behavior.
8. Inspect diff and ensure no unrelated changes.
9. Document any unverified behavior or environment limitation.

Prioritize:
- critical/high authorization flaws and privilege escalation;
- customer data exposure;
- financial/order integrity;
- inventory corruption;
- destructive-operation defects;
- concurrency/data corruption;
- validation and file security;
- operational traceability and lower-risk hardening.

If a fix needs a business decision, data migration, infrastructure change, or potentially breaking behavior, explain options and ask before proceeding. Continue other independent safe work.

---

# Test execution and acceptance

## Test execution
Discover commands from the repository; do not guess. Run:
1. Focused new tests.
2. Related existing audit suites.
3. Full backend tests.
4. Frontend checks/tests if relevant.
5. Production-engine integration/concurrency tests when available.

Record exact commands, environment/engine, and actual outcomes. Separate pre-existing failures from new failures. Investigate failures caused by your changes; do not conceal them.

## Completion criteria
The audit is complete only when:
- All discovered admin endpoints and sensitive read operations are inventoried.
- Authentication and authorization are backend-enforced.
- Privilege escalation and IDOR cases are addressed.
- Inputs and mass assignment are safely controlled.
- Actual business invariants are documented and tested.
- Critical multi-write operations are atomic.
- Concurrency-sensitive operations have appropriate protections and evidence.
- Retries cannot duplicate critical effects.
- Files and external side effects have safe failure handling.
- Sensitive operations have adequate traceability.
- Automated tests cover normal, invalid, malicious, failure, and applicable concurrent scenarios.
- Relevant existing customer/admin regression suites pass, or failures are explicitly explained.
- No critical/high finding remains silently unresolved.
- Limitations and remaining risks are clearly documented.

Passing tests does not prove the absence of vulnerabilities. Report the scope and evidence accurately.

# Deliverables

Create/update audit artifacts in the repository's established documentation location (discover it; do not invent conflicting structure). At minimum provide:

## 1. Admin operations inventory
Features, routes, methods, auth/authorization, validation, business logic, data effects, side effects, and test references.

## 2. Findings register
For each finding:
- ID;
- severity (Critical/High/Medium/Low/Informational);
- status (Confirmed/Fixed/Accepted/Unresolved/Not reproducible);
- feature and endpoint;
- evidence/reproduction;
- root cause;
- impact/exploit scenario;
- remediation;
- regression test reference;
- residual risk.

Severity should be based on realistic exploitability and impact, not labels alone.

## 3. Test report
- test files and scenarios added/changed;
- exact commands executed;
- pass/fail/skip counts;
- test database engine;
- concurrency tests and limitations;
- pre-existing failures;
- regression suite results.

## 4. Implementation summary
- files changed/created;
- code fixes;
- migrations (if any) and rationale;
- tests;
- compatibility considerations.

## 5. Remaining risk register
- unresolved findings and reason;
- features/endpoints not covered;
- environment/infrastructure assumptions;
- tests not executable;
- recommended follow-up actions.

Use clear Markdown tables/lists. Do not expose secrets or customer data.

# Final response format

When work finishes, summarize:
1. Scope discovered (number of features/endpoints, with meaningful breakdown).
2. Critical/high findings and their actual status.
3. Fixes implemented.
4. Tests created and exact verification results.
5. Regression results.
6. Files/artifacts created or updated.
7. Remaining risks, blockers, and decisions needed.

Be concise but precise. Never say “fully secure,” “100% covered,” or “production-ready” without a defensible, explicitly bounded basis.

## Start now

Begin with Phase 0 and Phase 1. Then proceed through the audit, remediation, and tests. Do not return only a plan: perform the work in the repository, reporting blockers honestly.
