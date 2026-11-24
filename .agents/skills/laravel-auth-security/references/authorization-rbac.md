# Authorization and Existing RBAC Reference

## Authentication vs authorization

- Authentication answers: who is this principal?
- Authorization answers: may this principal perform this action on this resource?

A successful staff login must not imply all staff permissions. A customer login must not imply access to every customer-owned record.

## Preserve the existing RBAC model

Default schema:
- `users.user_type` (`staff` or `customer`)
- `roles`
- `user_role`
- `sections`
- `role_section`
- `permissions`
- `role_section_permission`

Do not replace it with Spatie Permission, a new role table, new user table, or a simplified boolean `is_admin` unless expressly approved.

Audit:
- role assignment and revocation
- section-to-role and section-to-permission mapping
- role inheritance/combination semantics
- permission identifiers and route/action mapping
- soft deletes, tenant/company scope, and data visibility
- cache invalidation after role/permission changes
- seeders and default assignments
- super-admin behavior and its explicit definition
- missing/unknown permission handling

## Enforcement layers

1. **Authentication guard/provider**: only authenticates the intended user type.
2. **Route middleware**: requires the expected auth context.
3. **Permission middleware or policy**: verifies required capability.
4. **Resource policy/ownership**: verifies the principal can access this specific record.
5. **Service-level safeguards**: critical domain operations do not trust caller-supplied IDs or role claims.

Use defense in depth without duplicating inconsistent logic.

## Staff authorization

- Require staff identity on admin routes.
- Check permission for each sensitive action, not merely dashboard access.
- Verify permissions on read, write, export, delete, impersonation, and bulk operations.
- Define super-admin bypass narrowly and audit its use.
- Fail closed if role/permission lookup fails.
- Ensure cached permissions are invalidated on assignment changes.
- Do not trust role/permission values submitted by frontend.

## Customer authorization

- Require customer identity on customer routes.
- Enforce ownership for orders, addresses, carts, wishlists, profiles, uploads, returns, and other customer resources.
- Derive customer ID from authenticated principal, not request body/query.
- For staff access to customer resources, require explicit staff permission and audit trail.
- Prevent IDOR by testing another customer's IDs and nested child IDs.
- Validate ownership at every nested resource boundary.

## Shared routes

A route may be shared only if:
- it is intentionally shared,
- each principal type has a defined behavior,
- the correct guard is selected,
- authorization is explicit,
- data returned is appropriate for each principal,
- tests cover both principal types and anonymous access.

Avoid ambiguous endpoints that accept either guard implicitly.

## Fail-closed rules

Deny if:
- user is null or guard is missing
- user_type is unknown
- role/permission is missing or malformed
- policy evaluation fails
- ownership cannot be established
- resource is outside tenant/company scope
- auth context is ambiguous

Do not catch authorization exceptions and return success or an empty privileged result.

## Audit logging

Log privileged changes (role grants/revocations, staff account changes, sensitive exports, impersonation, policy overrides) with actor, action, target identifier, timestamp, outcome, and correlation ID. Avoid credentials, session IDs, tokens, reset links, full sensitive payloads, and unnecessary personal data.
