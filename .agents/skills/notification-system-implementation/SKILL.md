---
name: notification-system-implementation
description: Implement, secure, test, and integrate Bébito's complete notification system (Laravel transactional customer emails, OneSignal admin push notifications, and environment-based email safety guarding) before Platform Integration & Production Readiness.
---

# Bébito Notification System — Implementation Skill

## Mission

Implement a complete, reliable, secure, and tested notification system for Bébito before beginning Platform Integration & Production Readiness.

This is an implementation task—not merely an audit, proposal, or checklist. Inspect the existing application first, then implement the system in accordance with its real architecture and conventions.

Required channels:
1. Customer transactional email: welcome, order received, order confirmed, order dispatched.
2. Admin push notifications: OneSignal for relevant administrative events, targeted to authorized admin subscribers.
3. Notification safety guard: in guarded environments, all outbound customer email recipients must be redirected to a configured fallback. This is mandatory.

## 0. Mandatory repository rules

Before changes:
- Read and follow `AGENTS.md`, `CLAUDE.md`, `docs/architecture/ARCHITECTURE.md`, repository instructions, and relevant skills.
- Inspect Laravel/PHP versions, frontend framework, auth, queue/mail/logging configuration, tests, migrations, models, relationships, routes, policies, services, events/listeners/jobs, registration, order creation and status transitions, admin roles, and existing notification code.
- Treat any supplied database schema as an evolving/incomplete reference. Actual migrations and application code are authoritative for current behavior; identify discrepancies.
- Check Git status and preserve user work. Never reset, discard, overwrite unrelated changes, commit, push, or deploy.
- Never use production customer data or send real messages during automated tests.
- Keep secrets out of source control, frontend bundles, logs, tests, and reports.
- Do not claim tests passed unless executed.
- If credentials are unavailable, implement code and safe tests, document the missing live verification, and never fake results.

## 1. Scope and required outcomes

### Customer emails
| Trigger | Message |
|---|---|
| First successful account creation | Welcome email |
| Successful order creation | Order received |
| Valid admin confirmation transition | Order confirmed |
| Valid transition to shipped/sent to delivery | Order dispatched |

Use actual model fields, statuses, transition rules, and existing architecture. Never invent status values or bypass business rules.

### Admin OneSignal push
At minimum, notify administrators on:
- new order received;
- order confirmed;
- order cancelled, if supported;
- order sent to delivery;
- low/out-of-stock events only if already supported or reliably derivable without unrelated scope.

Push payloads should have a concise title/body, relevant entity reference, and safe admin navigation target. Do not include unnecessary customer PII. Do not implement customer push in this phase unless explicitly requested.

### Quality
The result must be secure, authorization-aware, transactionally correct, queue-friendly, idempotent where needed, observable, tested, and compatible with existing conventions.

## 2. Architecture discovery

Map the existing implementation before creating abstractions:
- registration commit point;
- order persistence and status-transition commit points;
- existing events/listeners/jobs;
- queue and worker setup;
- mail transport/provider;
- existing OneSignal/browser push support;
- admin roles/permissions and frontend routes;
- testing conventions and fakes.

Reuse sound infrastructure. Avoid duplicate event systems, duplicate order transition logic, or unnecessarily generic frameworks.

## 3. Notification infrastructure

Implement or improve a cohesive architecture:
- Use Laravel Mailables/Notifications and queues where appropriate.
- Use domain events/listeners or equivalent integration points to separate business operations from delivery.
- Dispatch only after relevant DB transactions commit.
- Never notify for failed or rolled-back operations.
- Delivery failure must not roll back a successfully committed account/order/status change.
- Avoid blocking HTTP requests on provider calls.
- Use bounded retries/backoff and safe failure handling.
- Avoid infinite retry loops and silent exception swallowing.
- Log useful sanitized metadata; never log secrets or unnecessary PII.
- Ensure queue retries cannot create duplicate business notifications.
- Document worker requirements.
- Do not rely on unsupported framework internals or fragile hacks.

## 4. Mandatory Notification Safety Guard — email recipient protection

This is critical and must not be implemented only in controllers.

### Configuration
Support:

```dotenv
NOTIFICATION_SAFETY_GUARDED_ENVS=dev,local,test
NOTIFICATION_SAFETY_EMAIL_FALLBACK=yasmine2marzouk@gmail.com
```

Expected behavior:

| APP_ENV | Email recipients |
|---|---|
| local | fallback only |
| dev | fallback only |
| test | fallback only |
| environment not in guarded list (e.g. production if configured so) | original intended recipients |

The guarded environment list is configurable, not hardcoded.

### Configuration design
- Create/extend a dedicated config file, e.g. `config/notification_safety.php`, following project conventions.
- Parse comma-separated environments safely: trim whitespace, ignore empty entries, compare exact names.
- Read environment variables through Laravel configuration; do not call `env()` in application runtime code.
- Use `config()` at runtime.
- Account for config caching and queue workers; document refresh/restart requirements.

### Central enforcement boundary
Implement a centralized recipient safety mechanism for **every outbound customer/transactional email**, including welcome, order received, confirmed, dispatched, and future transactional messages.

Choose an enforcement boundary compatible with the actual Laravel version/mail architecture (central mailer/recipient resolver/common delivery abstraction or equivalent). It must not depend on each controller/Mailable remembering to call a helper.

### Guarded recipient behavior
When environment is guarded:
- Replace every intended `To` recipient with fallback.
- Never deliver to original customer addresses.
- Prevent customer addresses leaking through CC/BCC.
- For customer transactional messages, fallback should be the only recipient unless a separately reviewed explicit internal-recipient policy exists.
- Prevent individual Mailable/Notification classes from accidentally bypassing the guard.
- Ensure queued serialization/retries cannot restore original recipients and bypass protection.
- Handle multi-recipient messages safely.

### Fail closed
If environment is guarded and fallback is missing, blank, or invalid:
- Do not send to original recipients.
- Block delivery.
- Log a clear configuration error and surface it through failure handling.
- Never silently revert to customer recipient.
- Validate early where practical, but enforce at delivery time too.
- Never expose fallback or secrets in public frontend configuration.

### Guard tests
Prove:
- local/dev/test redirect all transactional emails;
- non-guarded environment preserves intended recipient;
- exact environment matching and whitespace handling;
- To/CC/BCC cannot leak original recipients;
- missing/invalid fallback blocks delivery;
- all four email types are guarded;
- queued/retry paths remain guarded;
- config/runtime behavior is respected;
- no controller/Mailable can accidentally bypass centralized protection.

Use mail fakes/captures. Never send actual emails in tests.

## 5. Customer transactional emails

### Welcome
- Trigger only after first successful account persistence/commit.
- No email on failed registration/rollback.
- No duplicate welcome on retries/profile updates.
- Use actual account model/email semantics.
- Do not claim email verification unless implemented and completed.
- Include friendly welcome and safe account/store URL.
- Never include passwords, OTPs, sensitive tokens, or unnecessary internal IDs.

### Order received
- Trigger after successful order creation/commit.
- Use persisted order and order-item snapshot data, never frontend totals.
- Include order reference/date, items/quantities/prices, persisted totals, appropriate delivery details, shipping fee, and next steps.
- Clearly label payment as Cash on Delivery (COD), consistent with Bébito's actual checkout.
- Do not expose unnecessary customer information.

### Order confirmed
- Trigger only after a valid admin confirmation transition succeeds and commits.
- Include order reference, confirmation, summary, COD reminder if useful.
- Do not invent delivery ETA.
- No email on failed or incompatible/repeated transition.

### Order dispatched
- Trigger only after actual shipped/sent-to-delivery transition succeeds and commits.
- Include order reference and dispatch confirmation.
- Include tracking/carrier/ETA only if reliable data exists; never fabricate.

### Design
Create reusable responsive, accessible templates consistent with Bébito brand assets/tokens (warm neutral, soft premium baby-brand aesthetic). Do not redesign the brand or invent logos.
- Mobile-friendly, readable without images, safe escaping, plain-text alternative where supported.
- Subjects/preheaders clear and transactional.
- Use trusted absolute URLs from config.
- Do not include arbitrary user HTML or marketing content.
- Prioritize correctness/security over decorative polish.

## 6. OneSignal admin push

### Provider configuration
Use server-side environment/config values. If no convention exists, use clear names such as:

```dotenv
ONESIGNAL_APP_ID=
ONESIGNAL_REST_API_KEY=
```

Verify current OneSignal API/auth method and payload against official docs or installed SDK; do not invent endpoint formats or use deprecated auth. Never expose REST/API secrets in Vite/frontend variables, source, logs, or client responses.

### Admin subscription lifecycle
Inspect current authentication/frontend before implementing:
- subscription registration only for authenticated/authorized administrators;
- associate OneSignal identifiers safely using supported current model;
- never trust client-supplied user IDs/roles;
- handle unsupported browser, denied permission, disabled subscription, unsubscribe;
- provide clear enable/disable or subscription status UI;
- avoid duplicate/cross-user subscription association;
- deactivate/remove stale subscriptions when possible;
- secure endpoints with auth, authorization, validation, rate limiting as appropriate, and CSRF protections where applicable;
- push unsupported/denied must not break dashboard.

### Event delivery
- Send concise, meaningful title/body.
- Include event and relevant order/product reference plus safe admin deep link.
- Avoid customer PII.
- Target intended admin audience and respect roles/preferences.
- Dispatch after commit.
- Prevent duplicate pushes from retries/repeated transitions where practical.
- Push failure must not affect order/account operation success.

### Reliability/security
- Secrets server-side only.
- Server-authenticated provider requests.
- Validate payload and target identifiers.
- Clients cannot send arbitrary pushes or choose unrestricted audiences.
- Use timeouts, bounded retries, sanitized logs.
- Use HTTP fakes/mocks in tests.
- Do not claim live push verified without real credentials and a verified device/subscription.

## 7. Admin dashboard integration

Implement the necessary UI in existing frontend/design conventions:
- push subscription status and enable/disable;
- permission/subscription error states;
- preferences only where justified;
- safe navigation from push to relevant order/product.

Requirements:
- preserve existing UX and permissions;
- responsive and accessible;
- no oversized notification center unless existing or explicitly requested;
- don't show enabled when permission/subscription is not active;
- use established APIs/auth;
- provide loading, success, failure, and empty states.

## 8. Data model and idempotency

Inspect for existing notification delivery tracking/outbox. Do not add a table automatically if existing infrastructure suffices.

If durable tracking is necessary, follow migration conventions and store only necessary data: event/type, entity, channel, recipient/target reference with privacy care, status/attempts, timestamps, provider ID, and sanitized failure details.

Use event IDs/idempotency keys where appropriate. Define semantics from actual domain transitions; don't use order status as a simplistic deduplication key if legitimate transitions can recur.

## 9. Testing strategy

Use existing test conventions and safe fakes/mocks.

### Email
- account creation queues welcome once; failed registration queues none;
- successful order creation queues order received once; rollback queues none;
- valid confirmation queues confirmation;
- invalid/repeated transition does not duplicate;
- dispatch transition queues dispatched email;
- wrong transition sends none;
- email contents derive from persisted order/items/totals and correctly show COD;
- all emails obey safety guard;
- retries don't duplicate business notifications.

### Push
- correct events dispatch admin push;
- failed/rolled-back operations dispatch none;
- subscription endpoint requires auth;
- non-admin cannot subscribe/manipulate another admin's subscription;
- invalid input rejected;
- enable/disable lifecycle works;
- payload/deep link correct;
- provider failure does not break business operation;
- retry/dedup behavior verified;
- secrets never returned to frontend.

### End-to-end workflows
1. Account creation → welcome email queued and safely routed.
2. COD order → order persisted, order email and admin push queued.
3. Admin confirms → status committed, confirmation email and push queued.
4. Admin dispatches → status committed, dispatched email and push queued.
5. Provider failure → business operation remains successful; failure observable and retry safe.
6. Guarded env → every customer email only to fallback.
7. Non-guarded env → email to intended customer.

Test real transaction/after-commit/queue semantics, not only isolated methods.

### Test safety
- Never use live mail or OneSignal credentials.
- Use Mail/Queue/Event/HTTP fakes as suitable.
- Include integration coverage for after-commit behavior; fakes can conceal transaction mistakes.
- Use production DB engine for integration tests when practical; document SQLite limitations (locks/concurrency/transactions).

## 10. Configuration and operations documentation

Document:
- all required/optional env variables;
- safe `.env` values for local/dev/test;
- OneSignal setup: app, web platform, allowed origin/domain, permission/subscription;
- mail provider/sender/domain verification;
- queue worker and queue configuration;
- scheduler if needed;
- config cache and worker restart requirements;
- safe test procedure and provider troubleshooting;
- which checks require external credentials.

Do not commit real credentials. `.env.example` should use blank/fake placeholders, e.g.:

```dotenv
NOTIFICATION_SAFETY_GUARDED_ENVS=dev,local,test
NOTIFICATION_SAFETY_EMAIL_FALLBACK=
ONESIGNAL_APP_ID=
ONESIGNAL_REST_API_KEY=
```

Keep the actual fallback address in local/deployment secret configuration, not a public example unless repository owner explicitly approves.

## 11. Implementation workflow

### Phase A — Discovery and plan
Inspect instructions, architecture, migrations/schema, code/tests; map existing notification features; propose concise plan/files and identify external setup needed.

### Phase B — Safety foundation
Implement config and centralized email recipient guard first. Test guarded/non-guarded behavior and fail-closed before email flows.

### Phase C — Email infrastructure and welcome
Implement queue/after-commit; welcome email/template; test registration and guard.

### Phase D — Order lifecycle emails
Implement received/confirmed/dispatched; integrate with real committed transitions; test content, validity, duplicates, and rollback.

### Phase E — OneSignal backend/subscriptions
Implement secure provider service, authorized subscription lifecycle, and event push dispatch.

### Phase F — Admin UI
Implement subscription controls/status and safe deep links; test permissions/errors/responsive behavior.

### Phase G — Verification
Run focused tests, relevant full suite, lint/static analysis/build; inspect diff for secrets, PII, unsafe recipient leaks, bypasses, and unrelated changes. Fix regressions and document external setup/residual risks.

Do not stop after writing a plan. Continue implementation unless blocked by a necessary user decision, unavailable credential, or unsafe ambiguity.

## 12. Acceptance criteria

- [ ] Architecture discovery completed; existing conventions respected.
- [ ] Notification infrastructure reused or implemented coherently.
- [ ] Central email guard applies to every customer email path.
- [ ] local/dev/test redirect to fallback.
- [ ] missing/invalid fallback blocks delivery.
- [ ] non-guarded env uses intended recipient.
- [ ] welcome email after first successful account creation.
- [ ] order received after successful commit.
- [ ] order confirmed only after valid confirmation commit.
- [ ] dispatched email only after valid dispatch commit.
- [ ] emails use persisted data and correctly represent COD.
- [ ] OneSignal configured server-side; secrets protected.
- [ ] admin subscription lifecycle authenticated/authorized.
- [ ] required business events trigger admin push.
- [ ] failed operations produce no notifications.
- [ ] notification failures do not corrupt business operations.
- [ ] retries/duplicate transitions handled safely.
- [ ] automated tests cover flows, guard, authorization, and failures.
- [ ] relevant tests/build/lint executed and results reported.
- [ ] docs and environment examples updated.
- [ ] no unrelated changes, secrets, or unsafe tests introduced.

If any item is incomplete, state what, why, and what is needed. Do not call it production-ready merely because it compiles.

## 13. Final report

At completion provide:
1. Implementation summary and architectural decisions.
2. Files changed grouped backend/frontend/tests/config/docs.
3. Notification flow map (registration, order, confirmation, dispatch, admin push).
4. Safety guard proof (environments, recipient behavior, fail-closed tests).
5. OneSignal dashboard/domain/credential steps remaining.
6. Email provider/sender/domain steps remaining.
7. Exact test commands and pass/fail counts.
8. Build/static checks and results.
9. Known limitations/residual risks.
10. Safe local/dev manual verification and production smoke checklist.
11. Readiness assessment before Platform Integration & Production Readiness.

Never claim live email/push delivery verified if only mocks/fakes were used.
