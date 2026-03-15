<?php

namespace App\Domain\Organization\Services;

use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\DTOs\CreateOrganizationDTO;
use Illuminate\Support\Str;

class OrganizationService
{
    public function createOrganization(CreateOrganizationDTO $dto): Organization
    {
        return Organization::create([
            'id' => Str::uuid(),
            'name' => $dto->name,
            'code' => $dto->code,
            'timezone' => $dto->timezone,
            'currency' => $dto->currency,
            'metadata' => $dto->metadata,
        ]);
    }

    public function listOrganizations()
    {
        return Organization::all();
    }

    public function findById(string $id): ?Organization
    {
        return Organization::find($id);
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
}
