<?php

namespace Tests\Feature\Organization;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Organization\Models\Organization;
use App\Domain\User\Models\User;
use App\Domain\Role\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrganizationUserTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing organization users
     */
    public function test_user_can_list_organization_users(): void
    {
        $organization = Organization::factory()->create();
        $user = $this->createAuthenticatedUser($organization);
        $this->actingAs($user);

        $response = $this->getJson('/api/v1/organization-users');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Organization users retrieved successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'user_id',
                        'role_id',
                        'status',
                    ],
                ],
            ]);
    }

    /**
     * Test adding a user to organization
     */
    public function test_user_can_add_user_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createAuthenticatedUser($organization);
        // Get the admin role that was created, or create a different role
        $role = Role::where(['organization_id' => $organization->id, 'name' => 'Admin'])->first()
            ?? Role::factory()->for($organization)->create(['name' => 'Manager']);
        $newUser = User::factory()->create();

        $this->actingAs($admin);

        $response = $this->postJson("/api/v1/organization-users", [
            'user_id' => $newUser->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User added to organization successfully',
            ]);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $newUser->id,
            'role_id' => $role->id,
        ]);
    }

    /**
     * Test adding user with invalid user_id fails
     */
    public function test_add_user_fails_with_invalid_user_id(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createAuthenticatedUser($organization);
        $role = Role::factory()->for($organization)->create();

        $this->actingAs($admin);

        $response = $this->postJson("/api/v1/organization-users", [
            'user_id' => 'invalid-uuid-12345',
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test updating user role in organization
     */
    public function test_user_can_update_organization_user_role(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createAuthenticatedUser($organization);
        $userToUpdate = $this->createAuthenticatedUser($organization);
        $newRole = Role::factory()->viewer()->for($organization)->create();

        $this->actingAs($admin);

        $response = $this->patchJson("/api/v1/organization-users/$organization->id/{$userToUpdate->id}", [
            'role_id' => $newRole->id,
            'status' => 'active',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User role updated successfully',
            ]);

        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $userToUpdate->id,
            'role_id' => $newRole->id,
        ]);
    }

    /**
     * Test removing a user from organization
     */
    public function test_user_can_remove_user_from_organization(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createAuthenticatedUser($organization);
        $userToRemove = $this->createAuthenticatedUser($organization);

        $this->actingAs($admin);

        $response = $this->deleteJson("/api/v1/organization-users/$organization->id/{$userToRemove->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User removed from organization successfully',
            ]);

        $this->assertDatabaseMissing('organization_user', [
            'organization_id' => $organization->id,
            'user_id' => $userToRemove->id,
        ]);
    }

    /**
     * Test user status can be set to pending
     */
    public function test_user_status_can_be_set_to_pending(): void
    {
        $organization = Organization::factory()->create();
        $admin = $this->createAuthenticatedUser($organization);
        $role = Role::factory()->for($organization)->create();
        $newUser = User::factory()->create();

        $this->actingAs($admin);

        $response = $this->postJson("/api/v1/organization-users", [
            'user_id' => $newUser->id,
            'role_id' => $role->id,
            'status' => 'pending',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('organization_user', [
            'user_id' => $newUser->id,
            'status' => 'pending',
        ]);
    }
}
