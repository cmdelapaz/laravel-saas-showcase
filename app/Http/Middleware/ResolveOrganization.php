<?php

namespace App\Http\Middleware;

use App\Models\OrganizationMembership;
use App\Tenancy\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResolveOrganization
{
    public function __construct(
        private readonly CurrentOrganization $currentOrganization
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'code' => 'unauthenticated',
            ], 401);
        }

        $organizationId = $request->header('X-Organization-Id');

        if (! $organizationId) {
            return response()->json([
                'message' => 'An organization must be selected.',
                'code' => 'organization_required',
            ], 400);
        }

        if (! ctype_digit((string) $organizationId)) {
            return response()->json([
                'message' => 'Invalid organization.',
                'code' => 'invalid_organization',
            ], 400);
        }

        $membership = OrganizationMembership::query()
            ->with('organization')
            ->where('user_id', $user->id)
            ->where('organization_id', (int) $organizationId)
            ->where('status', 'active')
            ->whereHas(
                'organization',
                fn ($query) => $query->whereIn('status', ['active', 'trial'])
            )
            ->first();

        if (! $membership) {
            return response()->json([
                'message' => 'You do not have access to this organization.',
                'code' => 'organization_access_denied',
            ], 403);
        }

        $this->currentOrganization->set(
            $membership->organization,
            $membership
        );

        $request->attributes->set('organization', $membership->organization);
        $request->attributes->set('organization_membership', $membership);

        return $next($request);
    }
}
