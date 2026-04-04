<?php

namespace Tests\Feature\Organization;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use Tests\Traits\TenancyTestHelper;
use App\Domain\Organization\Models\Organization;
use App\Domain\User\Models\User;
use App\Domain\Role\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;
    use TenancyTestHelper;

    /**
     * Test listing organizations
     */
    public function test_user_can_list_organizations(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->getJson('/api/v1/organizations');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Organizations retrieved',
            ])
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * Test creating a new organization
     */
    public function test_user_can_create_organization(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/organizations', [
            'name' => 'New Organization',
            'code' => 'NEW-ORG-001',
            'metadata' => ['industry' => 'Manufacturing'],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Organization created',
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'New Organization',
            'code' => 'NEW-ORG-001',
        ]);
    }

    /**
     * Test creating organization with missing required fields
     */
    public function test_create_organization_fails_with_missing_required_fields(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/organizations', [
            'name' => 'New Organization',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    /**
     * Test viewing a single organization
     */
    public function test_user_can_view_organization(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->getJson("/api/v1/organizations/{$organization->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Organization retrieved',
                'data' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'code' => $organization->code,
                ],
            ]);
    }

    /**
     * Test updating an organization
     */
    public function test_user_can_update_organization(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->patchJson("/api/v1/organizations/{$organization->id}", [
            'name' => 'Updated Organization Name',
            'metadata' => ['industry' => 'Technology'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Organization updated',
                'data' => [
                    'name' => 'Updated Organization Name',
                ],
            ]);

        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'name' => 'Updated Organization Name',
        ]);
    }

    /**
     * Test deleting an organization
     */
    public function test_user_can_delete_organization(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->deleteJson("/api/v1/organizations/{$organization->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Organization deleted',
            ]);

        $this->assertSoftDeleted('organizations', [
            'id' => $organization->id,
        ]);
    }

    /**
     * Test organization isolation - user cannot access other org's data if not member
     */
    public function test_user_cannot_access_other_organization(): void
    {
        $this->authenticateAs();
        $otherOrganization = Organization::factory()->create();

        $response = $this->getJson("/api/v1/organizations/{$otherOrganization->id}");

        // Should either be 403 or 404 depending on implementation
        $this->assertThat(
            $response->status(),
            $this->logicalOr(
                $this->equalTo(403),
                $this->equalTo(404)
            )
        );
    }
}
