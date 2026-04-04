<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Domain\User\Models\User;
use App\Domain\Organization\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration with new organization
     */
    public function test_user_can_register_with_new_organization(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization_name' => 'Test Organization',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully',
            ])
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'role',
                    'master_data_counts',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization',
        ]);
    }

    /**
     * Test registration fails with invalid email
     */
    public function test_registration_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization_name' => 'Test Organization',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration fails when required fields are missing
     */
    public function test_registration_fails_with_missing_required_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'organization_name']);
    }

    /**
     * Test registration fails with duplicate email
     */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'organization_name' => 'Test Organization',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration fails when password confirmation doesn't match
     */
    public function test_registration_fails_with_mismatched_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
            'organization_name' => 'Test Organization',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test successful user login
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'user',
                ],
            ]);

        $this->assertNotNull($response->json('data.access_token'));
    }

    /**
     * Test login fails with invalid email
     */
    public function test_login_fails_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test login fails with incorrect password
     */
    public function test_login_fails_with_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('correct_password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test login requires email and password
     */
    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test successful user logout
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logout successful',
            ]);

        // Verify token was deleted
        $this->assertFalse(
            User::find($user->id)->tokens()->exists()
        );
    }

    /**
     * Test logout requires authentication
     */
    public function test_logout_requires_authentication(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test password reset with valid token
     */
    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('old_password'),
        ]);

        // Create password reset token
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->insert([
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/password-reset', [
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'new_password' => 'new_password123',
            'new_password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Password reset successful',
            ]);

        // Verify password was updated
        $updatedUser = User::find($user->id);
        $this->assertTrue(Hash::check('new_password123', $updatedUser->password));

        // Verify token was deleted
        $this->assertFalse(
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')
                ->where('email', 'test@example.com')
                ->exists()
        );
    }

    /**
     * Test password reset fails with invalid token
     */
    public function test_password_reset_fails_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/auth/password-reset', [
            'email' => 'test@example.com',
            'token' => 'invalid-token',
            'new_password' => 'new_password123',
            'new_password_confirmation' => 'new_password123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test password reset fails when passwords don't match
     */
    public function test_password_reset_fails_with_mismatched_passwords(): void
    {
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->insert([
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/auth/password-reset', [
            'email' => 'test@example.com',
            'token' => 'valid-token-123',
            'new_password' => 'new_password123',
            'new_password_confirmation' => 'different_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_password']);
    }

    /**
     * Test protected endpoints require authentication
     */
    public function test_protected_endpoints_require_authentication(): void
    {
        $endpoints = [
            ['GET', '/api/v1/users'],
            ['GET', '/api/v1/organizations'],
            ['POST', '/api/v1/auth/logout'],
        ];

        foreach ($endpoints as [$method, $endpoint]) {
            $response = $this->json($method, $endpoint);
            $response->assertStatus(401);
        }
    }

    /**
     * Test authenticated user can access protected endpoints
     */
    public function test_authenticated_user_can_access_protected_endpoints(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        // Use insert directly to avoid relationship magic
        \Illuminate\Support\Facades\DB::table('organization_user')->insert([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'role_id' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/users');

        $response->assertStatus(200);
    }
}
