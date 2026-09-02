# Laravel SaaS Showcase

A public portfolio project demonstrating how I structure **multi-tenant SaaS backends with Laravel**, including tenant resolution, membership validation, authorization boundaries, REST API patterns, and feature testing.

> This repository is intentionally simplified and sanitized for portfolio use. It demonstrates architectural patterns from a larger private SaaS project without publishing proprietary product logic or business-specific code.

## What This Showcase Demonstrates

- Multi-tenant request handling
- Organization / tenant context resolution
- Membership-based access control
- Protection against cross-tenant data access
- REST API middleware patterns
- Explicit API error responses
- Dependency-injected tenant context
- Feature tests for tenant isolation
- Clean separation between authentication and tenant authorization

## Request Flow

```text
Authenticated API Request
        ↓
X-Organization-Id header
        ↓
ResolveOrganization middleware
        ↓
Validate active membership
        ↓
Resolve CurrentOrganization context
        ↓
Tenant-scoped controller / service
        ↓
JSON response
```

## Example Security Boundary

A user may belong to one or more organizations. Supplying an organization ID is not enough to access its data.

The middleware verifies that:

1. the request is authenticated;
2. an organization was selected;
3. the organization ID is valid;
4. the authenticated user has an active membership;
5. the organization itself is available;
6. only then is tenant context made available to downstream application code.

This prevents a common SaaS authorization mistake: trusting a tenant identifier supplied by the client without validating membership.

## Key Files

```text
app/
├── Http/Middleware/ResolveOrganization.php
└── Tenancy/CurrentOrganization.php

routes/
└── api.php

tests/
└── Feature/CurrentOrganizationTest.php

docs/
└── ARCHITECTURE.md
```

## API Example

A tenant-aware request includes the selected organization:

```http
GET /api/tenant
Authorization: Bearer <token>
X-Organization-Id: 42
Accept: application/json
```

Successful response:

```json
{
  "data": {
    "organization": {
      "id": 42,
      "name": "Example Organization"
    },
    "role": "owner"
  }
}
```

A user attempting to access an organization they do not belong to receives:

```json
{
  "message": "You do not have access to this organization.",
  "code": "organization_access_denied"
}
```

with HTTP status `403`.

## Testing Strategy

The included feature-test sample covers several important SaaS boundaries:

- authenticated users can list organizations available to them;
- tenant-aware endpoints require an organization selection;
- active members can access their selected organization;
- users cannot access another organization's tenant context;
- suspended memberships are denied.

These are integration-style feature tests because tenant isolation should be validated across the HTTP request, middleware, authentication, database membership, and JSON response layers together.

## Technologies Represented

- PHP
- Laravel
- Laravel Sanctum
- REST APIs
- Eloquent ORM
- PHPUnit
- Relational database design
- Git / GitHub

## Why This Repository Exists

My larger applications include business-specific workflows that I keep private. This repository provides recruiters and engineering teams with a focused code sample showing how I approach a production-relevant SaaS concern: **tenant isolation and authorization**.

The example is intentionally small enough to review quickly while still showing application structure, defensive validation, dependency injection, explicit error handling, and automated testing.

## Related Work

This showcase is based on patterns used while building a larger multi-tenant league-management SaaS application with Laravel and a Next.js frontend. The commercial/product code remains private while this repository exposes selected architectural concepts in a safe, reviewable form.

## Author

**Carlos Gonzalez**  
Software Developer | Laravel • PHP • REST APIs • SaaS Architecture
