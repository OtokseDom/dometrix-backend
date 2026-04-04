<?php

namespace Tests\Feature\Organization;

use Tests\TestCase;
use Tests\Traits\AuthorizationTestHelper;
use App\Domain\User\Models\User;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;
    use AuthorizationTestHelper;

    /**
     * Test listing users
     */
    public function test_user_can_list_users(): void
    {
        $this->authenticateAs();

        $response = $this->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Users retrieved',
            ])
            ->assertJsonStructure([
                'data',
            ]);
    }

    /**
     * Test creating a new user
     */
    public function test_user_can_create_user(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User created',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
    }

    /**
     * Test creating user fails with invalid email
     */
    public function test_create_user_fails_with_invalid_email(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'not-an-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test creating user fails with duplicate email
     */
    public function test_create_user_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test viewing a single user
     */
    public function test_user_can_view_user(): void
    {
        $user = $this->authenticateAs();
        $targetUser = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User retrieved',
                'data' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                ],
            ]);
    }

    /**
     * Test updating a user
     */
    public function test_user_can_update_user(): void
    {
        $this->authenticateAs();
        $userToUpdate = User::factory()->create();

        $response = $this->patchJson("/api/v1/users/{$userToUpdate->id}", [
            'name' => 'Updated Name',
            'is_active' => false,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User updated',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $userToUpdate->id,
            'name' => 'Updated Name',
            'is_active' => false,
        ]);
    }

    /**
     * Test deleting a user
     */
    public function test_user_can_delete_user(): void
    {
        $this->authenticateAs();
        $userToDelete = User::factory()->create();

        $response = $this->deleteJson("/api/v1/users/{$userToDelete->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User deleted',
            ]);

        $this->assertSoftDeleted('users', [
            'id' => $userToDelete->id,
        ]);
    }

    /**
     * Test user can be marked as inactive
     */
    public function test_user_can_be_marked_inactive(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => false,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);
    }

    /**
     * Test user's password is hashed
     */
    public function test_user_password_is_hashed(): void
    {
        $this->authenticateAs();

        $response = $this->postJson('/api/v1/users', [
            'name' => 'New User',
            'email' => 'password-test@example.com',
            'password' => 'plain_password',
            'password_confirmation' => 'plain_password',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'password-test@example.com')->first();
        $this->assertTrue(Hash::check('plain_password', $user->password));
        $this->assertNotEquals('plain_password', $user->password);
    }

    /**
     * Test metadata is stored correctly
     */
    public function test_user_metadata_stored_correctly(): void
    {
        $this->authenticateAs();

        $metadata = ['department' => 'IT', 'phone' => '123-456-7890'];

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Metadata User',
            'email' => 'metadata@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'metadata' => $metadata,
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'metadata@example.com')->first();
        $this->assertEquals($metadata, $user->metadata);
    }
}
