<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Services\OrganizationService;
use App\Http\Requests\StoreOrganizationUserRequest;
use App\Http\Requests\UpdateOrganizationUserRequest;
use App\Http\Resources\OrganizationUserResource;
use App\Helpers\ApiResponse;

class OrganizationUserController extends Controller
{
    public function __construct(private OrganizationService $service)
    {
    }

    /**
     * List all users in an organization
     */
    public function index()
    {
        try {
            $users = $this->service->listUsers();

            return ApiResponse::send(
                OrganizationUserResource::collection($users),
                'Organization users retrieved successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::send(null, $e->getMessage(), 500);
        }
    }

    /**
     * Add a user to an organization
     */
    public function store(StoreOrganizationUserRequest $request, Organization $organization)
    {
        try {
            $orgUser = $this->service->addUser(
                $organization,
                $request->user_id,
                $request->role_id,
                $request->status
            );

            return ApiResponse::send(
                new OrganizationUserResource($orgUser),
                'User added to organization successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::send(null, $e->getMessage(), 500);
        }
    }

    /**
     * Update a user's role in the organization
     */
    public function update(UpdateOrganizationUserRequest $request, Organization $organization, string $user)
    {
        try {
            $orgUser = $this->service->updateOrganizationUser(
                $organization,
                $user,
                $request->role_id,
                $request->status
            );

            return ApiResponse::send(
                new OrganizationUserResource($orgUser),
                'User role updated successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::send(null, $e->getMessage(), 500);
        }
    }

    /**
     * Remove a user from the organization
     */
    public function destroy(Organization $organization, string $user)
    {
        try {
            $this->service->removeUser($organization, $user);

            return ApiResponse::send(
                null,
                'User removed from organization successfully'
            );
        } catch (\Exception $e) {
            return ApiResponse::send(null, $e->getMessage(), 500);
        }
    }
}
