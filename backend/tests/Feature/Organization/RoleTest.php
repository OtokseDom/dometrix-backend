<?php

namespace Tests\Feature\Organization;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\Organization\Models\Organization;
use App\Domain\Role\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing roles
     */
    public function test_user_can_list_roles(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->getJson('/api/v1/roles');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Roles retrieved',
            ]);
    }

    /**
     * Test creating a new role
     */
    public function test_user_can_create_role(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $response = $this->postJson('/api/v1/roles', [
            'name' => 'Custom Role',
            'permissions' => [
                'materials' => ['create', 'read'],
                'products' => ['read'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Role created',
                'data' => [
                    'name' => 'Custom Role',
                ],
            ]);

        $this->assertDatabaseHas('roles', [
            'organization_id' => $organization->id,
            'name' => 'Custom Role',
        ]);
    }

    /**
     * Test creating role fails with duplicate name in same organization
     */
    public function test_create_role_fails_with_duplicate_name(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        Role::factory()->for($organization)->create(['name' => 'Duplicate Role']);

        $response = $this->postJson('/api/v1/roles', [
            'name' => 'Duplicate Role',
            'permissions' => [],
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test viewing a single role
     */
    public function test_user_can_view_role(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $role = Role::factory()->for($organization)->create();

        $response = $this->getJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role retrieved',
                'data' => [
                    'id' => $role->id,
                    'name' => $role->name,
                ],
            ]);
    }

    /**
     * Test updating a role
     */
    public function test_user_can_update_role(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $role = Role::factory()->for($organization)->create();

        $response = $this->patchJson("/api/v1/roles/{$role->id}", [
            'name' => 'Updated Role Name',
            'permissions' => [
                'materials' => ['create', 'read', 'update', 'delete'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role updated',
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'Updated Role Name',
        ]);
    }

    /**
     * Test deleting a role
     */
    public function test_user_can_delete_role(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();
        $role = Role::factory()->for($organization)->create();

        $response = $this->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Role deleted',
            ]);

        $this->assertSoftDeleted('roles', [
            'id' => $role->id,
        ]);
    }

    /**
     * Test role permissions structure
     */
    public function test_role_permissions_stored_correctly(): void
    {
        $user = $this->authenticateAs();
        $organization = $user->organizations()->first();

        $permissions = [
            'materials' => ['create', 'read', 'update'],
            'products' => ['read'],
            'boms' => ['create', 'read'],
        ];

        $response = $this->postJson('/api/v1/roles', [
            'name' => 'Test Role',
            'permissions' => $permissions,
        ]);

        $response->assertStatus(201);

        $role = Role::where('name', 'Test Role')->first();
        $this->assertEquals($permissions, $role->permissions);
    }
}
