<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationUser;
use App\Domain\Organization\DTOs\CreateOrganizationDTO;
use Illuminate\Support\Collection;

class OrganizationService
{
    // ----------------------
    // Organization CRUD
    // ----------------------

    public function getOrganizations(): Collection
    {
        return Organization::all();
    }

    public function showOrganization(string $id): ?Organization
    {
        return Organization::find($id);
    }

    public function createOrganization(CreateOrganizationDTO $dto): Organization
    {
        return Organization::create([
            'name' => $dto->name, 'code' => $dto->code, 'timezone' => $dto->timezone, 'currency' => $dto->currency,
            'metadata' => $dto->metadata,
        ]);
    }

    public function updateOrganization(Organization $organization, array $data): Organization
    {
        $organization->update($data);
        return $organization;
    }

    public function deleteOrganization(Organization $organization): void
    {
        $organization->delete();
    }

    // ----------------------
    // User-Pivot Methods
    // ----------------------

    /**
     * Attach a user to an organization with a role
     */
    public function addUser(Organization $organization, string $userId, string $roleId): OrganizationUser
    {
        $organization->users()->attach($userId, ['role_id' => $roleId]);

        return OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)->firstOrFail();
    }

    /**
     * Update a user's role in an organization
     */
    public function updateUserRole(Organization $organization, string $userId, string $roleId): OrganizationUser
    {
        $organization->users()->updateExistingPivot($userId, ['role_id' => $roleId]);

        return OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)->firstOrFail();
    }

    /**
     * Remove a user from an organization
     */
    public function removeUser(Organization $organization, string $userId): void
    {
        $organization->users()->detach($userId);
    }

    /**
     * List all users in an organization with pivot info
     */
    public function listUsers(Organization $organization): Collection
    {
        return $organization->users()->with('role')->get();
    }

    /**
     * Get a user's role in an organization
     */
    public function getUserRole(Organization $organization, string $userId): ?string
    {
        $pivot = $organization->users()->where('user_id', $userId)->first()?->pivot;

        return $pivot?->role_id;
    }
}
