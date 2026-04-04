<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Role\Services\RoleService;
use App\Domain\Role\DTOs\CreateRoleDTO;
use App\Domain\Role\DTOs\UpdateRoleDTO;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Http\Resources\RoleCollection;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    protected RoleService $service;

    public function __construct(RoleService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return ApiResponse::send(new RoleCollection($this->service->getRoles()), "Roles retrieved");
    }

    public function store(StoreRoleRequest $request)
    {
        // Get organization from authenticated user
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();
        $organization = $user->organizations()->first();

        $dto = new CreateRoleDTO(
            name: $request->name,
            permissions: $request->permissions ?? null,
            organization_id: $organization?->id
        );

        $role = $this->service->create($dto);
        return ApiResponse::send(new RoleResource($role), "Role created", true, 201);
    }

    public function show($id)
    {
        $role = $this->service->showRole($id);
        if (!$role) {
            return ApiResponse::send(null, "Role not found", false, 404);
        }
        return ApiResponse::send(new RoleResource($role), "Role retrieved");
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        $role = $this->service->showRole($id);
        if (!$role) {
            return ApiResponse::send(null, "Role not found", false, 404);
        }

        $dto = new UpdateRoleDTO(
            name: $request->name ?? null,
            permissions: $request->permissions ?? null
        );

        $role = $this->service->update($role, $dto);
        return ApiResponse::send(new RoleResource($role), "Role updated");
    }

    public function destroy($id)
    {
        $role = $this->service->showRole($id);
        if (!$role) {
            return ApiResponse::send(null, "Role not found", false, 404);
        }

        $this->service->delete($role);
        return ApiResponse::send(null, "Role deleted");
    }
}
