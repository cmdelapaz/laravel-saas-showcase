<?php

use App\Http\Middleware\ResolveOrganization;
use App\Tenancy\CurrentOrganization;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function (): void {
    Route::get('/organizations', function () {
        $user = request()->user();

        return response()->json([
            'data' => $user->organizationMemberships()
                ->where('status', 'active')
                ->with('organization')
                ->get()
                ->map(fn ($membership) => [
                    'id' => $membership->organization->id,
                    'name' => $membership->organization->name,
                    'role' => $membership->role,
                ]),
        ]);
    });

    Route::middleware(ResolveOrganization::class)->group(function (): void {
        Route::get('/tenant', function (CurrentOrganization $currentOrganization) {
            return response()->json([
                'data' => [
                    'organization' => [
                        'id' => $currentOrganization->id(),
                        'name' => $currentOrganization->organization()->name,
                    ],
                    'role' => $currentOrganization->role(),
                ],
            ]);
        });
    });
});
