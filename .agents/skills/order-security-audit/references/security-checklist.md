# Order Security & Reliability Audit Checklist

Use one status per item:
- PASS: verified with evidence.
- FAIL: confirmed issue.
- NOT IMPLEMENTED: protection/feature is absent.
- NOT VERIFIED: not tested or insufficient evidence.
- NOT APPLICABLE: feature does not exist; explain why.

## 1. Scope and environment
- [ ] Repository instructions and skills read.
- [ ] Order scope documented.
- [ ] Actual lifecycle documented.
- [ ] Test database confirmed isolated from production/shared systems.
- [ ] Baseline git status recorded; user changes preserved.
- [ ] Test data is synthetic.
- [ ] Production secrets are not printed or copied.

## 2. Identity and authorization
- [ ] Customer/admin authentication contexts remain separate.
- [ ] Customer order ownership enforced server-side.
- [ ] Admin route middleware and permissions correct.
- [ ] Read/write permissions separated where needed.
- [ ] IDOR tested across read/update/cancel/details/items.
- [ ] Session expiration/logout/revocation behavior checked.
- [ ] Mass assignment of ownership/status/payment fields prevented.
- [ ] Authorization is checked at service/policy boundary where appropriate.

## 3. Checkout and data validation
- [ ] All user input validated.
- [ ] Product/variant/size relationship verified.
- [ ] Product availability verified at checkout.
- [ ] Address ownership verified.
- [ ] Quantity and request limits enforced.
- [ ] Price, discount, shipping, and total calculated server-side.
- [ ] Currency/rounding rules deterministic.
- [ ] Order snapshots preserve historical data.

## 4. Inventory and transactions
- [ ] Stock deduction is atomic/concurrency-safe.
- [ ] Database constraints support invariants.
- [ ] Critical operations use appropriate transaction boundaries.
- [ ] Row locks/atomic updates used where justified.
- [ ] Rollback verified on injected failures.
- [ ] Stock cannot become negative.
- [ ] Cancellation/restock is idempotent.
- [ ] Stock reconciliation can detect discrepancies.

## 5. Idempotency and lifecycle
- [ ] Checkout retry is safe.
- [ ] Duplicate checkout protection is defined.
- [ ] Idempotency keys are scoped and unique where implemented.
- [ ] Valid order state machine documented.
- [ ] Invalid transitions rejected.
- [ ] Cancellation/fulfillment race behavior verified.
- [ ] Repeated operations have no duplicate side effects.

## 6. COD integrity
- [ ] COD is the only payment method.
- [ ] Customer cannot set paid/cash-collected status.
- [ ] Authorized staff/admin controls cash collection.
- [ ] Payment status transitions are valid and idempotent.
- [ ] Delivery and payment collection are not conflated unless business rules explicitly require it.
- [ ] COD changes are auditable.
- [ ] No online payment gateway assumptions/tests remain in scope.

## 7. API and information security
- [ ] Rate limits configured.
- [ ] CSRF and CORS behavior appropriate for cookie auth.
- [ ] SQL injection tested.
- [ ] XSS output encoding checked.
- [ ] Error handling does not leak internals.
- [ ] API resources prevent data leakage.
- [ ] Logs redact secrets and unnecessary PII.
- [ ] Pagination and payload limits enforced.

## 8. Resilience and operations
- [ ] Notifications/jobs do not corrupt committed order state.
- [ ] Failed jobs are monitored and retry-safe.
- [ ] Deadlocks/timeouts handled.
- [ ] Database indexes and query patterns reviewed.
- [ ] Load profile and service limits documented.
- [ ] Backups/recovery processes are known (operational owner to verify).
- [ ] Monitoring exists for checkout errors, failed jobs, stock discrepancies, and order-state anomalies.

## 9. Evidence and release gate
- [ ] Critical automated tests executed.
- [ ] Full suite result recorded.
- [ ] Static analysis result recorded where applicable.
- [ ] Concurrency tests use real DB behavior.
- [ ] Load test results are from approved isolated environment.
- [ ] All findings have severity, evidence, impact, and remediation.
- [ ] Residual risks are explicit.
- [ ] GO / CONDITIONAL GO / NO-GO recommendation justified.

## Suggested severity
- Critical: exploitable cross-customer access, unauthorized order/payment manipulation, systemic overselling/data corruption.
- High: checkout integrity bypass, duplicate financial/inventory effects, unauthorized admin actions.
- Medium: limited data leakage, inconsistent lifecycle, abuse without strong impact.
- Low: hardening, observability, minor performance or UX-adjacent issues.
