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
use Exception;

class UserController extends Controller
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index($organization_id = "")
    {
        try {
            $users = $this->service->getUsers($organization_id);
            return ApiResponse::send(data: new UserCollection($users), message: "Users retrieved");
        } catch (Exception $e) {
            return ApiResponse::send(
                data: null,
                message: "Something went wrong",
                success: false,
                code: 500,
                errors: [
                    "exception" => [$e->getMessage()]
                ]
            );
        }
    }

    public function store(StoreUserRequest $request)
    {
        try {
            $dto = new CreateUserDTO(
                name: $request->name,
                email: $request->email,
                password: $request->password,
                metadata: $request->metadata ?? null,
                is_active: $request->is_active ?? true
            );

            $user = $this->service->create($dto);
            return ApiResponse::send(data: new UserResource($user), message: "User created", success: true, code: 201);
        } catch (Exception $e) {
            return ApiResponse::send(
                data: null,
                message: "Something went wrong",
                success: false,
                code: 500,
                errors: [
                    $e->getMessage()
                ]
            );
        }
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
