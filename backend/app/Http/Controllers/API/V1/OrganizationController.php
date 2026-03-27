<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Domain\Organization\Services\OrganizationService;
use App\Domain\Organization\DTOs\CreateOrganizationDTO;
use App\Domain\Organization\DTOs\UpdateOrganizationDTO;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\OrganizationCollection;
use App\Helpers\ApiResponse;

class OrganizationController extends Controller
{
    protected OrganizationService $service;

    public function __construct(OrganizationService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $organizations = $this->service->getOrganizations();
        return ApiResponse::send(new OrganizationCollection($organizations), "Organizations retrieved");
    }

    public function store(StoreOrganizationRequest $request)
    {
        $dto = new CreateOrganizationDTO(
            name: $request->name,
            code: $request->code,
            timezone: $request->timezone ?? 'UTC',
            currency: $request->currency ?? 'USD',
            metadata: $request->metadata ?? null
        );

        $organization = $this->service->createOrganization($dto);

        return ApiResponse::send(new OrganizationResource($organization), "Organization created", true, 201);
    }

    public function show($id)
    {
        $organization = $this->service->showOrganization($id);
        if (!$organization) {
            return ApiResponse::send(null, "Organization not found", false, 404);
        }

        return ApiResponse::send(new OrganizationResource($organization), "Organization retrieved");
    }

    public function update(UpdateOrganizationRequest $request, $id)
    {
        $organization = $this->service->showOrganization($id);
        if (!$organization) {
            return ApiResponse::send(null, "Organization not found", false, 404);
        }

        $dto = new UpdateOrganizationDTO(
            name: $request->name,
            code: $request->code,
            timezone: $request->timezone,
            currency: $request->currency,
            metadata: $request->metadata
        );
        $organization = $this->service->updateOrganization($organization, (array) $dto);

        return ApiResponse::send(new OrganizationResource($organization), "Organization updated");
    }

    public function destroy($id)
    {
        $organization = $this->service->showOrganization($id);
        if (!$organization) {
            return ApiResponse::send(null, "Organization not found", false, 404);
        }

        $this->service->deleteOrganization($organization);

        return ApiResponse::send(null, "Organization deleted", 200);
    }
}
