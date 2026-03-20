<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\User\Services\UserService;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Helpers\ApiResponse;

class UserController extends Controller
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index($organization_id = null)
    {
        $users = $this->service->getUsers($organization_id);
        return ApiResponse::send(new UserCollection($users), "Users retrieved");
    }

    public function store(StoreUserRequest $request)
    {
        $dto = new CreateUserDTO(
            name: $request->name,
            email: $request->email,
            password: $request->password,
            role_id: $request->role_id,
            organization_id: $request->organization_id,
            metadata: $request->metadata ?? null,
            is_active: $request->is_active ?? true
        );

        $user = $this->service->create($dto);
        return ApiResponse::send(new UserResource($user), "User created", 201);
    }

    public function show($id)
    {
        $user = $this->service->showUser($id);
        if (!$user) {
            return ApiResponse::send(null, "User not found", 404);
        }
        return ApiResponse::send(new UserResource($user), "User retrieved");
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = $this->service->showUser($id);
        if (!$user) {
            return ApiResponse::send(null, "User not found", 404);
        }

        $dto = new UpdateUserDTO(
            name: $request->name ?? null,
            email: $request->email ?? null,
            password: $request->password ?? null,
            role_id: $request->role_id ?? null,
            organization_id: $request->organization_id ?? null,
            metadata: $request->metadata ?? null,
            is_active: $request->is_active ?? null
        );

        $user = $this->service->update($user, $dto);
        return ApiResponse::send(new UserResource($user), "User updated");
    }

    public function destroy($id)
    {
        $user = $this->service->showUser($id);
        if (!$user) {
            return ApiResponse::send(null, "User not found", 404);
        }

        $this->service->delete($user);
        return ApiResponse::send(null, "User deleted");
    }
}
