<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CurrentOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_available_organizations(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'name' => 'Example Organization',
        ]);

        OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/organizations')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Example Organization')
            ->assertJsonPath('data.0.role', 'owner');
    }

    public function test_organization_is_required_for_tenant_routes(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/tenant')
            ->assertStatus(400)
            ->assertJsonPath('code', 'organization_required');
    }

    public function test_active_member_can_access_selected_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create([
            'name' => 'Example Organization',
            'status' => 'active',
        ]);

        OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/tenant')
            ->assertOk()
            ->assertJsonPath('data.organization.name', 'Example Organization')
            ->assertJsonPath('data.role', 'owner');
    }

    public function test_user_cannot_access_another_organization(): void
    {
        $user = User::factory()->create();
        $allowedOrganization = Organization::factory()->create();
        $foreignOrganization = Organization::factory()->create();

        OrganizationMembership::factory()->create([
            'organization_id' => $allowedOrganization->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Organization-Id', (string) $foreignOrganization->id)
            ->getJson('/api/tenant')
            ->assertForbidden()
            ->assertJsonPath('code', 'organization_access_denied');
    }

    public function test_suspended_membership_cannot_access_organization(): void
    {
        $user = User::factory()->create();
        $organization = Organization::factory()->create();

        OrganizationMembership::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role' => 'staff',
            'status' => 'suspended',
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Organization-Id', (string) $organization->id)
            ->getJson('/api/tenant')
            ->assertForbidden();
    }
}
