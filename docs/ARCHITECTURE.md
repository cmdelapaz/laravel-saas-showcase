# Architecture Notes

This repository focuses on one specific SaaS concern: resolving and enforcing the current organization for an authenticated API request.

## Design Goals

The example is designed around four goals:

1. Never trust a client-supplied tenant ID by itself.
2. Keep tenant resolution in one reusable middleware layer.
3. Make the resolved tenant available through an explicit application context object.
4. Test cross-tenant denial at the HTTP boundary.

## Tenant Selection

The client sends the selected organization using the `X-Organization-Id` request header.

The header identifies the organization the user wants to work with, but it does **not** grant access. Access is determined from the authenticated user's membership record.

## Membership Validation

`ResolveOrganization` checks the authenticated user's membership using both `user_id` and `organization_id`. The example also requires the membership to be active and the organization to be in an allowed status.

This makes authorization dependent on server-side relational data rather than request input.

## CurrentOrganization Context

After validation, the middleware places the organization and membership into `CurrentOrganization`.

Application code can then depend on this object instead of repeatedly parsing headers or reimplementing membership checks.

Examples:

```php
$currentOrganization->id();
$currentOrganization->role();
$currentOrganization->hasRole('owner', 'admin');
```

The context throws a `LogicException` if accessed before resolution. This is intentional: tenant-aware services should fail clearly when invoked outside the expected middleware boundary.

## Cross-Tenant Protection

Consider a user who belongs to organization `10` but sends:

```http
X-Organization-Id: 25
```

The middleware searches for an active membership matching both the authenticated user and organization `25`. If none exists, the request returns HTTP `403`.

The implementation does not accept an organization merely because that organization exists.

## Authentication vs. Authorization

These are separate concerns in the example:

- Laravel Sanctum determines **who the user is**.
- `ResolveOrganization` determines **which tenant that user may act within**.

Keeping those concerns separate makes tenant rules easier to reason about and test.

## Why Feature Tests

Tenant isolation can fail even when individual classes look correct. Routing, authentication, middleware registration, database state, and response behavior all participate in the security boundary.

For that reason, the showcase emphasizes feature tests that exercise the complete HTTP flow.

## Production Considerations

A larger production application may extend this pattern with policies, permission matrices, tenant-aware query scopes, domain or subdomain resolution, audit logs, caching, background-job tenant propagation, and database-level isolation strategies.

Those concerns are intentionally outside the scope of this small public repository. The goal here is to provide a focused and reviewable example rather than reproduce an entire private SaaS codebase.
