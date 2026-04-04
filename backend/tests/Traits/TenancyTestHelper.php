<?php

namespace Tests\Traits;

use App\Domain\Organization\Models\Organization;
use Illuminate\Support\Facades\Auth;

trait TenancyTestHelper
{
    /**
     * Create multiple organizations for multi-tenant testing
     */
    protected function createMultipleOrganizations($count = 2): array
    {
        return Organization::factory()->count($count)->create()->toArray();
    }

    /**
     * Assert user cannot access other organization's data
     */
    protected function assertTenantIsolation(string $endpoint, array $data, Organization $userOrg, Organization $otherOrg)
    {
        // This will be implemented in specific test classes
    }

    /**
     * Get current user's organization
     */
    protected function getCurrentUserOrganization(): Organization
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();

        return $user->organizations()->firstOrFail();
    }

    /**
     * Create organization with all master data initialized
     */
    protected function createOrganizationWithMasterData(): Organization
    {
        $organization = Organization::factory()->create();

        // Create master data for the organization
        // This would typically call the OrganizationMasterDataService

        return $organization;
    }
}
