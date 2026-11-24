---
name: admin-order-management
description: Implement and audit the Bébito admin order details page, order financial breakdown, coupon application, order modifications, order lifecycle transitions, cancellation, and customer notifications. Use when working on order management in the React admin dashboard or Laravel backend.
---

# Bébito Admin Order Management Implementation Skill

## 1. Objective

Implement a fully functional, secure, reliable, and production-ready Admin Order Details module for Bébito.

The administrator must be able to:

- View all relevant details of an order.
- Understand the complete financial breakdown.
- Apply a coupon to an existing order.
- Confirm an order.
- Mark an order as ready for delivery.
- Update an order.
- Cancel an order.
- Ensure the customer receives the appropriate emails.
- Preserve financial consistency, inventory consistency, auditability, and data integrity.

This is an implementation task, not a UI-only task.

The implementation must work end-to-end across:

- Laravel backend API.
- React admin dashboard.
- Database.
- Existing authentication and authorization.
- Inventory and coupon systems.
- Order lifecycle.
- Customer email notifications.

## 2. Mandatory preliminary investigation

Before implementing anything, inspect the existing repository and understand the current implementation.

### Backend

Inspect:

- Order models and relationships.
- Order controllers.
- Order services and business logic.
- Order repositories, if present.
- Order validation.
- Order resources and serializers.
- Order status enums.
- Order status history.
- Order items and product variants.
- Inventory management.
- Coupon implementation.
- Payment implementation.
- Address snapshots.
- Email notification classes.
- Queue configuration.
- Existing authorization policies and middleware.
- Existing database migrations.
- Existing tests.

### Admin frontend

Inspect:

- Admin order listing.
- Existing order details route/page.
- Order status UI.
- API client and request conventions.
- Authentication and authorization.
- Loading, error, and success states.
- Existing reusable components.
- Form validation conventions.
- Existing design system.

### Existing project constraints

- Bébito sells exclusively in Tunisia.
- The current active currency is TND.
- Payment is cash on delivery (COD).
- There is no online payment gateway.
- Customer authentication and admin authentication are isolated.
- Existing authentication, orders, product/inventory, and cart/checkout audits have already been performed.

Use existing audited implementations rather than duplicating or bypassing them.

Do not assume an earlier uploaded database schema is complete or reflects every current implementation detail.

Inspect actual migrations, models, database constraints, and code.

Do not create a new architecture unnecessarily.

## 3. Important business rules

### Existing order statuses

The existing order status enum has included:

- pending
- processing
- shipped
- delivered
- cancelled
- not_picked_up

Inspect the current implementation before using these values.

The administrator-facing actions must support these business concepts:

| Admin action | Business meaning | Customer email |
|---|---|---|
| Confirm order | Confirm the order | Yes |
| Ready for delivery | Order is ready for delivery | No |
| Update order | Modify order information | Yes |
| Cancel order | Cancel the order | Yes |

Do not introduce additional statuses unless required by the actual implementation and explicitly justified.

Map "ready for delivery" to an existing status only if its semantics are correct.

Do not silently interpret ready for delivery as dispatched/shipped.

If the current status model cannot accurately represent this business state, identify the issue and request clarification before introducing an incompatible lifecycle.

### Order lifecycle

Implement lifecycle transitions through a centralized backend service.

Never allow the frontend to directly manipulate order status.

The backend must enforce:

- Valid current status.
- Valid target status.
- Allowed transition.
- Business prerequisites.
- Authorization.
- Inventory consequences.
- Payment consequences.
- Notification consequences.

Do not allow arbitrary status changes through generic update endpoints.

## 4. Order details page

Implement a complete order details page in the admin dashboard.

### A. Order information

Display:

- Order number.
- Order creation date.
- Current status.
- Customer information.
- Customer name.
- Customer email.
- Customer phone.
- Delivery recipient information.
- Delivery phone.
- Delivery address.
- Relevant customer/order notes, if implemented.
- Payment method.
- Payment status.
- Order items.
- Product name.
- Product SKU.
- Product variant.
- Quantity.
- Unit price.
- Line total.

Use order-item snapshots for historical order details.

Do not rely exclusively on current product information because products, names, prices, and variants may change after purchase.

### B. Financial breakdown

Display a clear and accurate financial breakdown:

- Original unit price per item.
- Quantity.
- Original line subtotal.
- Item discounts, if applicable.
- Coupon information.
- Coupon code.
- Coupon discount amount.
- Delivery fee.
- Subtotal.
- Total discount.
- Final order total.
- COD amount due.

The displayed values must come from authoritative backend calculations and persisted order values.

The frontend must not independently recalculate the final amount.

Use the actual order schema and existing financial conventions.

Do not assume that a field named subtotal or discount exists.

Inspect how totals are currently calculated and stored.

### Financial invariants

The backend must preserve the project's financial invariants.

At minimum, ensure:

- Order item quantities and prices remain consistent.
- Line totals match the persisted pricing rules.
- Subtotal is consistent with order items.
- Discounts are correctly represented.
- Delivery fees are correctly represented.
- Coupon discount is applied only once.
- Total is consistent with all financial components.
- Payment amount remains consistent with the order total.
- COD amount due is accurate.
- All monetary calculations use safe decimal/money handling.
- Floating-point arithmetic is not used for financial calculations.

Use existing monetary representations and precision.

Do not introduce rounding inconsistencies between the order, order items, coupon, and payment records.

## 5. Applying a coupon to an existing order

The administrator must be able to apply a coupon to a specific order.

Implement this feature through a dedicated backend operation or an existing appropriate service.

### Coupon validation

The backend must validate:

- Coupon exists.
- Coupon is active.
- Coupon has not expired.
- Coupon is applicable to the order.
- Minimum order amount is satisfied.
- Coupon usage limit is respected.
- Coupon is compatible with existing order discounts.
- Coupon has not already been applied incorrectly.
- Coupon discount does not exceed the allowed amount.
- The order is in a status where financial modifications are permitted.

Use the existing coupon types:

- Percentage discount.
- Fixed amount discount.

Respect existing coupon calculation rules.

### Coupon usage consistency

Applying a coupon must correctly update:

- Order coupon reference.
- Coupon code snapshot, if supported.
- Coupon discount amount.
- Order total.
- Payment amount, if applicable.
- Coupon usage counter or usage records.
- Related audit/history records.

Ensure that coupon usage is not incremented multiple times when:

- The same coupon is reapplied.
- A request is retried.
- An API request times out.
- The administrator submits the operation twice.
- A transaction is rolled back.

If replacing or removing a coupon is supported, usage accounting must be correct in both directions.

If the current schema cannot correctly support applying coupons to existing orders, identify the missing requirement instead of implementing an unsafe workaround.

### Concurrency

Coupon usage limits must be enforced atomically.

Concurrent administrators must not exceed the coupon usage limit.

Use appropriate transaction boundaries and database locking or equivalent concurrency-safe mechanisms.

## 6. Confirm order

Implement an explicit confirmation action.

The backend must:

1. Authenticate the administrator.
2. Authorize the administrator to manage the order.
3. Validate the current order status.
4. Validate the permitted transition.
5. Validate any confirmation prerequisites.
6. Update the order status.
7. Record status history.
8. Preserve inventory and financial consistency.
9. Dispatch the customer confirmation email after successful commit.

The customer must receive a confirmation email communicating:

"Your order is confirmed."

Use the existing email templates, branding, queue, and notification conventions.

Do not send the email before the database transaction commits.

Do not send confirmation emails when:

- The transition is invalid.
- The order is already confirmed.
- The transaction fails.
- The administrator lacks permission.

Ensure duplicate requests do not produce duplicate status history or unintended duplicate notifications.

## 7. Ready for delivery

Implement an explicit ready-for-delivery action.

The backend must:

- Validate administrator authorization.
- Validate the current order status.
- Validate the permitted transition.
- Apply any required business prerequisites.
- Update the status.
- Record status history.
- Preserve inventory consistency.
- Preserve financial consistency.

**This action must not send a customer notification.**

Do not trigger:

- Customer confirmation email.
- Customer dispatch email.
- Customer SMS.
- Customer push notification.

Do not accidentally reuse a generic status-change notification handler that sends customer notifications for every transition.

If an existing internal admin notification is part of the established architecture, preserve it only when appropriate.

## 8. Update order

Implement an explicit and secure order update operation.

The administrator must be able to update the order according to the existing business requirements and supported schema.

Investigate which fields can legitimately be modified:

- Customer/delivery information.
- Delivery address.
- Delivery recipient.
- Delivery phone.
- Order items.
- Product variants.
- Quantities.
- Coupon.
- Order notes.
- Other existing editable fields.

Do not assume every field is editable.

## 8.1 Order item management: Add, Remove, and Change Quantity

The administrator must be able to modify the products contained in an existing order.

Supported operations:

- Add a product to the order.
- Remove a product from the order.
- Change the quantity of an existing product.
- Change the selected product variant, if supported and permitted by the existing order lifecycle.

These operations must be implemented through secure backend business operations, not by directly manipulating order items from the React frontend.

### A. Add product

The administrator must be able to select an existing product and its applicable variant, then specify a quantity.

The backend must validate:

- Product exists.
- Product is active and eligible for sale according to existing business rules.
- Selected variant exists and belongs to the selected product.
- Variant is available for sale.
- Requested quantity is valid.
- Stock is sufficient.
- Order is eligible for modification.
- Product pricing is obtained from the authoritative pricing logic.

When adding a product:

- Create the appropriate order item.
- Persist the correct product and variant references.
- Preserve historical product/variant snapshots.
- Persist the correct unit price.
- Calculate the line total correctly.
- Adjust inventory according to the existing inventory lifecycle.
- Recalculate order subtotal.
- Recalculate applicable discounts.
- Recalculate coupon discount if applicable.
- Recalculate order total.
- Reconcile COD payment amount.
- Preserve order and inventory consistency.

Do not accept a unit price supplied by the admin frontend as authoritative.

### B. Remove product

The administrator must be able to remove a product from an order.

The backend must validate:

- Order exists.
- Order is eligible for modification.
- Order item belongs to the specified order.
- Removal is permitted by the order lifecycle.
- Inventory consequences are correctly handled.

When removing a product:

- Remove or appropriately mark the order item according to the existing architecture.
- Preserve historical information when required by audit or financial traceability.
- Restore inventory only when the existing inventory lifecycle requires it.
- Reconcile inventory movements.
- Recalculate subtotal.
- Recalculate item discounts.
- Recalculate coupon discount.
- Recalculate order total.
- Reconcile COD payment amount.
- Preserve coupon usage consistency.

Never restore inventory twice.

Never remove an order item belonging to another order.

Never leave an order with zero items unless the existing business rules explicitly allow it.

### C. Change quantity

The administrator must be able to increase or decrease the quantity of an existing order item.

The backend must validate:

- Order exists.
- Order item belongs to the order.
- Order is eligible for modification.
- New quantity is valid.
- Product and variant remain valid.
- Stock is sufficient when increasing quantity.

Inventory handling:

- Increasing quantity must correctly account for additional stock consumption or reservation.
- Decreasing quantity must correctly release or restore the appropriate stock.
- Repeated requests must not duplicate stock movements.
- Concurrent modifications must not oversell inventory.

Pricing handling:

- Recalculate line totals.
- Apply existing pricing rules.
- Recalculate applicable item discounts.
- Recalculate order subtotal.
- Recalculate coupon discount.
- Recalculate order total.
- Reconcile COD payment amount.

Do not assume that the original unit price must remain unchanged or that it must be updated to the current product price.

Investigate the existing order pricing policy and implement the correct behavior.

### D. Coupon recalculation after product modifications

Product changes may invalidate an existing coupon.

After adding, removing, or changing quantities:

- Revalidate coupon eligibility.
- Revalidate minimum order amount.
- Revalidate applicable products or categories.
- Recalculate coupon discount.
- Remove or invalidate the coupon if no longer applicable, according to existing business rules.
- Correct coupon usage accounting.
- Reconcile payment amount.
- Ensure financial totals remain consistent.

Do not preserve an invalid coupon merely because it was valid when the original order was created.

### E. Transactional consistency

Product modifications must be atomic.

The following must succeed or fail together:

- Order item changes.
- Inventory changes.
- Inventory movements.
- Order subtotal.
- Order discounts.
- Coupon discount.
- Order total.
- COD payment amount.
- Audit/history records.

Use the existing inventory service and order pricing service.

Use appropriate database locking to prevent concurrent modifications from causing overselling, duplicated inventory restoration, or inconsistent order totals.

### F. Admin frontend

The order details page must provide:

- An Add Product action.
- Product and variant selection.
- Quantity selection.
- A Remove Product action for each order item.
- A quantity editing control for each order item.
- Validation and stock availability feedback.
- Confirmation before removing a product.
- Updated financial breakdown after each successful operation.
- Updated coupon information.
- Updated COD amount.
- Clear success and error feedback.

The frontend must display the authoritative order returned by the backend after successful modification.

Prevent accidental double submissions.

### G. Additional tests

Add tests for:

- Adding a product to an order.
- Adding a product with a variant.
- Adding an unavailable product.
- Adding an invalid variant.
- Adding a product with insufficient stock.
- Removing an existing order item.
- Removing an item belonging to another order.
- Removing the last order item.
- Increasing quantity.
- Decreasing quantity.
- Increasing quantity beyond available stock.
- Invalid quantities.
- Correct inventory restoration.
- Correct additional stock consumption.
- No duplicate inventory movements.
- Correct unit price and line total.
- Correct subtotal.
- Correct item discounts.
- Correct coupon recalculation.
- Correct coupon usage accounting.
- Correct COD amount.
- Correct order total.
- Concurrent quantity modifications.
- Concurrent product additions.
- Failed transactions leaving no partial modifications.
- Customer notification after successful modifications.
- No customer notification after failed modifications.


### Update validation

The backend must validate:

- Order existence.
- Administrator authorization.
- Current order status.
- Allowed editable fields.
- Product and variant existence.
- Product/variant availability.
- Stock availability.
- Quantity constraints.
- Coupon validity.
- Financial consistency.
- Payment consistency.
- Delivery-fee rules.
- Whether the order is already delivered or cancelled.

Prevent unauthorized changes to:

- Customer identity.
- Order ownership.
- Order number.
- Historical snapshots.
- Payment status.
- Financial fields.
- Status.
- Coupon usage counters.
- Inventory records.

These values must be changed only through appropriate business operations.

### Inventory consistency

When order items or quantities change:

- Correctly account for previously reserved or deducted inventory.
- Correctly account for newly requested quantities.
- Prevent negative stock.
- Prevent overselling.
- Handle concurrent order modifications.
- Respect the existing inventory lifecycle.
- Use the audited inventory service.

Do not directly modify inventory quantities if the project uses inventory movements or a centralized stock service.

All related inventory changes must be atomic with the order update.

### Financial recalculation

When an update affects pricing:

- Recalculate subtotal.
- Recalculate item discounts.
- Recalculate coupon discount.
- Recalculate delivery fee when applicable.
- Recalculate order total.
- Reconcile the COD payment amount.
- Update all required financial snapshots.
- Preserve financial invariants.

Use the existing pricing and checkout services whenever appropriate.

Do not duplicate checkout pricing logic in the admin controller.

### Customer notification

After a successful order update, send an appropriate customer email.

The email should:

- Clearly communicate that the order was updated.
- Identify the order.
- Explain relevant changes when possible.
- Reflect the actual persisted order state.
- Use the existing Bébito email branding and conventions.

Do not send an email for a failed update.

Do not send the email before commit.

Avoid duplicate emails caused by retries.

## 9. Cancel order

Implement a dedicated order cancellation operation.

Cancellation must be handled by a centralized backend service.

### Validation

Validate:

- Order existence.
- Administrator authorization.
- Current order status.
- Cancellation eligibility.
- Inventory consequences.
- Payment consequences.
- Existing cancellation history.

Do not allow cancellation of terminal orders unless explicitly supported by existing business rules.

### Inventory

Cancellation must correctly restore inventory when required by the existing inventory lifecycle.

Do not restore stock twice.

Do not restore stock for inventory that was never deducted or reserved.

Use existing inventory movements and stock services.

### Payment

Because Bébito uses COD only:

- Do not create a fictitious online refund.
- Do not invoke a payment gateway.
- Correctly reconcile the COD payment record according to its actual schema and lifecycle.
- Ensure the cancelled order no longer represents an amount collectible from the customer.

### Database consistency

Cancellation must atomically preserve:

- Order status.
- Order status history.
- Inventory.
- Inventory movements.
- Payment consistency.
- Coupon usage consistency, according to existing coupon rules.
- Financial totals.
- Audit records.

### Customer notification

Send a cancellation email after successful commit.

The email must clearly communicate that the order has been cancelled.

Do not send the email when cancellation fails.

Prevent duplicate cancellation emails on retries.

## 10. Backend architecture

Use the existing Laravel architecture.

Prefer:

- Dedicated order management service.
- Dedicated lifecycle transition service.
- Explicit application operations.
- Form Requests for validation.
- Policies or equivalent authorization.
- API Resources for serialization.
- Existing domain/business exceptions.
- Existing logging conventions.
- Existing queue and notification architecture.

Do not put complex business logic directly into controllers.

Do not create multiple competing implementations of:

- Order pricing.
- Coupon calculations.
- Stock management.
- Status transitions.
- Cancellation.
- Payment reconciliation.

The backend must remain the authoritative source of truth.

### Transactional requirements

Use database transactions for operations involving multiple related records.

Examples:

- Applying a coupon.
- Updating order items.
- Recalculating financial totals.
- Changing payment amounts.
- Updating inventory.
- Cancelling orders.
- Changing order status.
- Creating status history.

All database changes belonging to one business operation must succeed or fail together.

Use locking where necessary to prevent concurrent operations from corrupting state.

### Notification requirements

Notifications must be triggered only after successful database commits.

Prefer after-commit queueing and appropriate outbox/idempotency patterns if supported by the existing architecture.

Consider the failure scenario:

1. Database transaction succeeds.
2. Email queue dispatch fails.

Ensure the system does not silently lose a required notification.

Do not introduce an elaborate outbox architecture unless necessary; inspect the existing implementation first.

## 11. Admin frontend requirements

Implement the React admin order details page using existing UI conventions.

### Layout

Organize the page into clear sections:

1. Order header.
2. Status and actions.
3. Customer information.
4. Delivery information.
5. Order items.
6. Financial breakdown.
7. Coupon information.
8. Payment information.
9. Order history.
10. Admin notes or audit information, if supported.

### Actions

Provide appropriate actions:

- Confirm order.
- Ready for delivery.
- Update order.
- Apply coupon.
- Cancel order.

Actions must depend on the order's actual status and permissions.

Do not rely exclusively on frontend visibility to enforce authorization.

### User experience

Implement:

- Loading state.
- Error state.
- Empty state when appropriate.
- Success feedback.
- Validation feedback.
- Confirmation dialogs for destructive operations.
- Clear error messages.
- Disabled actions during requests.
- Protection against accidental double submissions.
- Refreshing or updating the order after successful operations.

For cancellation, clearly explain the consequence before confirmation.

For order updates, display validation errors correctly.

For coupon application, show the resulting discount and updated total.

For all financial operations, ensure the administrator sees the authoritative persisted result.

## 12. Security requirements

The implementation must be secure from both:

- Admin dashboard/frontend manipulation.
- Direct API manipulation.

### Authorization

- Use the existing isolated admin authentication.
- Require appropriate admin permissions.
- Verify authorization on every backend endpoint.
- Verify order-management permissions.
- Prevent customer users from accessing admin operations.
- Prevent unauthorized access to another customer's order information.
- Avoid trusting frontend role checks.

### Input validation

- Validate all incoming fields.
- Reject unexpected or forbidden fields.
- Validate IDs and relationships.
- Validate quantities and monetary inputs.
- Validate order status transitions.
- Prevent mass assignment.
- Prevent modification of protected financial fields.
- Prevent modification of customer ownership.

### API security

- Do not expose internal implementation details.
- Do not trust client-calculated totals.
- Do not trust client-provided discounts.
- Do not trust client-provided coupon usage counters.
- Do not trust client-provided inventory values.
- Do not trust client-provided order statuses.
- Ensure appropriate rate limiting where applicable.
- Ensure proper error handling and logging.

## 13. Audit trail

Every meaningful administrative operation must be traceable.

Inspect existing order status history and audit mechanisms.

Record, where supported:

- Previous status.
- New status.
- Administrator who performed the action.
- Timestamp.
- Action type.
- Reason for cancellation.
- Relevant changes.
- Financial changes.
- Coupon changes.
- Inventory-related changes.

Do not overwrite historical order information without preserving appropriate snapshots or audit records.

Avoid logging sensitive customer data unnecessarily.

## 14. Testing requirements

Do not consider the task complete until meaningful tests are implemented and passing.

Use existing test conventions.

### Order details tests

- Authorized admin can retrieve an order.
- Unauthorized admin cannot retrieve restricted orders.
- Customer cannot access admin order endpoints.
- Order items and historical snapshots are correctly serialized.
- Financial breakdown is accurate.
- COD payment information is correct.

### Coupon tests

- Valid coupon can be applied.
- Invalid coupon is rejected.
- Inactive coupon is rejected.
- Expired coupon is rejected.
- Coupon usage limit is respected.
- Minimum order amount is respected.
- Percentage discount is calculated correctly.
- Fixed discount is calculated correctly.
- Discount is capped according to business rules.
- Coupon usage is recorded correctly.
- Reapplying a coupon does not double-count usage.
- Concurrent applications cannot exceed usage limits.
- Failed transactions do not partially modify coupon/order/payment data.

### Confirmation tests

- Valid pending order can be confirmed.
- Invalid transition is rejected.
- Confirmation status is persisted.
- Status history is created.
- Customer confirmation email is queued after commit.
- Failed transactions do not send emails.
- Duplicate requests do not create duplicate transitions.
- Unauthorized administrators cannot confirm orders.

### Ready-for-delivery tests

- Valid transition succeeds.
- Invalid transition is rejected.
- Status history is created.
- Inventory remains consistent.
- Financial totals remain consistent.
- No customer notification is dispatched.
- Duplicate requests are handled safely.

### Order update tests

- Valid update succeeds.
- Invalid data is rejected.
- Unauthorized fields are rejected.
- Stock is adjusted correctly.
- Stock cannot become negative.
- Concurrent updates cannot oversell.
- Financial totals are recalculated correctly.
- Coupon usage remains consistent.
- COD amount remains consistent.
- Status restrictions are enforced.
- Customer update email is queued after commit.
- Failed updates do not send emails.
- Failed transactions do not partially update inventory or financial records.

### Cancellation tests

- Eligible order can be cancelled.
- Ineligible order cannot be cancelled.
- Cancellation history is recorded.
- Inventory is restored correctly.
- Stock is not restored twice.
- COD payment is reconciled correctly.
- Coupon usage is reconciled according to business rules.
- Customer cancellation email is queued after commit.
- Failed transactions do not send emails.
- Duplicate requests cannot cancel or restore inventory twice.

### Integration tests

Test complete admin workflows:

1. Admin opens an order.
2. Admin applies a coupon.
3. Order totals are recalculated.
4. Admin confirms the order.
5. Customer confirmation email is dispatched.
6. Admin marks the order ready for delivery.
7. No customer email is dispatched for this transition.
8. Admin updates the order.
9. Order totals and inventory remain consistent.
10. Customer update email is dispatched.
11. Admin cancels an eligible order.
12. Inventory is correctly reconciled.
13. Customer cancellation email is dispatched.

Also test invalid workflows and concurrency-sensitive scenarios.

Use database assertions, queue assertions, inventory assertions, and financial assertions.

## 15. Implementation workflow

Follow this sequence.

### Phase 1 — Investigation

- Inspect the database schema.
- Inspect the existing order implementation.
- Inspect the existing admin dashboard.
- Inspect the existing API.
- Inspect lifecycle and notification services.
- Identify gaps and inconsistencies.

Produce a concise implementation plan.

### Phase 2 — Backend

- Implement missing order management services.
- Implement lifecycle transitions.
- Implement coupon application.
- Implement secure order updates.
- Implement cancellation.
- Implement customer email notifications.
- Implement authorization.
- Implement transactional consistency.
- Implement API endpoints/resources.
- Add backend tests.

### Phase 3 — Admin frontend

- Implement the order details page.
- Implement financial breakdown.
- Implement coupon application.
- Implement confirmation action.
- Implement ready-for-delivery action.
- Implement update functionality.
- Implement cancellation.
- Integrate with backend APIs.
- Add validation and error handling.

### Phase 4 — Integration

- Test the complete workflow.
- Verify database consistency.
- Verify inventory consistency.
- Verify coupon usage.
- Verify COD payment consistency.
- Verify customer emails.
- Verify authorization.
- Verify concurrency behavior.

### Phase 5 — Final audit

Review:

- Security.
- Business rules.
- Financial correctness.
- Inventory correctness.
- Notification correctness.
- Database consistency.
- API consistency.
- Frontend/backend integration.
- Test coverage.
- Regression risks.

## 16. Critical implementation constraints

- Do not implement a frontend-only solution.
- Do not bypass existing order services.
- Do not duplicate checkout pricing logic.
- Do not trust frontend financial calculations.
- Do not manipulate inventory directly outside existing inventory services.
- Do not allow arbitrary status changes.
- Do not send emails before database commits.
- Do not send customer emails for ready-for-delivery.
- Do not duplicate emails on retries.
- Do not allow duplicate coupon usage.
- Do not allow duplicate stock restoration.
- Do not introduce unsupported business rules.
- Do not assume database fields exist without checking.
- Do not replace existing architecture without justification.
- Do not stop after writing code without running tests.

## 17. Definition of Done

The implementation is complete only when:

- The admin can view complete order details.
- Financial breakdown is accurate.
- Coupons can be applied securely.
- Order confirmation works correctly.
- Ready-for-delivery works without customer notification.
- Order updates work correctly.
- Cancellation works correctly.
- Customer emails are correctly dispatched.
- Inventory remains consistent.
- Coupon usage remains consistent.
- COD payments remain consistent.
- Status history is accurate.
- All operations are authorized.
- All important business operations are transactional.
- Concurrent requests are handled safely.
- Backend tests pass.
- Frontend tests pass where applicable.
- Integration tests pass.
- No critical security or business-rule issue remains.

## 18. Final report

At completion, provide a structured report containing:

1. Features implemented.
2. Backend files modified.
3. Frontend files modified.
4. Database migrations added or modified.
5. APIs created or modified.
6. Business rules implemented.
7. Notifications implemented.
8. Security measures implemented.
9. Tests added.
10. Tests executed and results.
11. Any unresolved issues.
12. Any business decisions requiring administrator confirmation.
13. Any remaining production risks.

Do not claim production readiness if important tests, business rules, or implementation requirements remain unresolved.
