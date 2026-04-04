<?php

namespace Tests\Traits;

use App\Domain\User\Models\User;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationUser;
use App\Domain\Role\Models\Role;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait AuthorizationTestHelper
{
    /**
     * Create an authenticated user for an organization
     */
    protected function createAuthenticatedUser(?Organization $organization = null, ?Role $role = null): Authenticatable
    {
        /** @var \App\Domain\Organization\Models\Organization $organization */
        $organization = $organization ?? Organization::factory()->create();

        if (!$role) {
            // Try to find existing Admin role for this organization
            $role = Role::where([
                'organization_id' => $organization->id,
                'name' => 'Admin',
            ])->first();

            // Create if doesn't exist
            if (!$role) {
                $role = Role::factory()->admin()->create([
                    'organization_id' => $organization->id,
                ]);
            }
        }

        /** @var \Illuminate\Foundation\Auth\User $user */
        $user = User::factory()->create();

        // Debug logging
        Log::debug('AuthorizationTestHelper::createAuthenticatedUser', [
            'organization_id' => $organization->id,
            'role_id' => $role->id,
            'user_id' => $user->id,
            'about_to_create_org_user' => true,
        ]);

        try {
            // Create the organization_user pivot record directly instead of using attach
            OrganizationUser::create([
                'organization_id' => $organization->id,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'status' => 'active',
            ]);
            Log::debug('AuthorizationTestHelper::OrganizationUser created successfully');
        } catch (\Exception $e) {
            Log::error('AuthorizationTestHelper::OrganizationUser creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'organization_id' => $organization->id,
            ]);
            throw $e;
        }

        return $user;
    }

    /**
     * Create and authenticate a user with a specific role
     */
    protected function authenticateAs(?Organization $organization = null, string $roleName = 'admin'): Authenticatable
    {
        if (!$organization) {
            $organization = Organization::factory()->create();
        }

        // Create role with appropriate permissions
        $permissions = match ($roleName) {
            'admin' => $this->getAdminPermissions(),
            'viewer' => $this->getViewerPermissions(),
            'editor' => $this->getEditorPermissions(),
            default => [],
        };

        // Try to find existing role first
        $role = Role::where([
            'organization_id' => $organization->id,
            'name' => $roleName,
        ])->first();

        // Create if doesn't exist
        if (!$role) {
            $role = Role::factory()->create([
                'organization_id' => $organization->id,
                'name' => $roleName,
                'permissions' => $permissions,
            ]);
        }

        $user = $this->createAuthenticatedUser($organization, $role);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Get admin permissions array
     */
    protected function getAdminPermissions(): array
    {
        return [
            'materials' => ['create', 'read', 'update', 'delete'],
            'products' => ['create', 'read', 'update', 'delete'],
            'boms' => ['create', 'read', 'update', 'delete'],
            'inventory' => ['create', 'read', 'update', 'delete'],
            'organization' => ['create', 'read', 'update', 'delete'],
            'users' => ['create', 'read', 'update', 'delete'],
            'roles' => ['create', 'read', 'update', 'delete'],
        ];
    }

    /**
     * Get viewer permissions array
     */
    protected function getViewerPermissions(): array
    {
        return [
            'materials' => ['read'],
            'products' => ['read'],
            'boms' => ['read'],
            'inventory' => ['read'],
        ];
    }

    /**
     * Get editor permissions array
     */
    protected function getEditorPermissions(): array
    {
        return [
            'materials' => ['create', 'read', 'update'],
            'products' => ['create', 'read', 'update'],
            'boms' => ['create', 'read', 'update'],
            'inventory' => ['create', 'read', 'update'],
        ];
    }
}
