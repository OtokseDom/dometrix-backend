<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationUser;
use App\Domain\Organization\DTOs\CreateOrganizationDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
     * List all users in an organization with pivot info
     */
    public function listUsers(?Organization $organization = null): Collection
    {
        $authUser = Auth::user();

        if (!$organization) {
            $organization = $authUser->organizations()->first();
        }

        if (!$organization) {
            return collect();
        }

        return OrganizationUser::with(['user', 'role'])
            ->where('organization_id', $organization->id)
            ->get();
    }

    /**
     * Attach a user to an organization with a role
     */
    public function addUser(
        Organization $organization,
        string $userId,
        string $roleId,
        string $status
    ): OrganizationUser {
        OrganizationUser::create([
            'organization_id' => $organization->id,
            'user_id' => $userId,
            'role_id' => $roleId,
            'status' => $status,
        ]);
        return OrganizationUser::with(['organization', 'user', 'role'])
            ->where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    /**
     * Update a user's role in an organization
     */
    public function updateOrganizationUser(
        Organization $organization,
        string $userId,
        string $roleId,
        string $status
    ): OrganizationUser {
        OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->update(['role_id' => $roleId, 'status' => $status]);
        return OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)->firstOrFail();
    }

    /**
     * Remove a user from an organization
     */
    public function removeUser(Organization $organization, string $userId): void
    {
        OrganizationUser::where('organization_id', $organization->id)
            ->where('user_id', $userId)
            ->delete();
    }

}
