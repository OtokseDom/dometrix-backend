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

    public function index()
    {
        //        $users = $this->service->getUsers();
        //        return ApiResponse::send([
        //            'users' => UserResource::collection($users->items()),
        //            'meta' => [
        //                'current_page' => $users->currentPage(),
        //                'last_page' => $users->lastPage(),
        //                'per_page' => $users->perPage(),
        //                'total' => $users->total(),
        //            ]
        //        ], "Users retrieved");

        $users = $this->service->getUsers(); // returns LengthAwarePaginator

        return ApiResponse::send(
            new UserCollection($users),
            "Users retrieved"
        );
    }

    public function store(StoreUserRequest $request)
    {
        $dto = new CreateUserDTO(
            name: $request->name,
            email: $request->email,
            password: $request->password,
            metadata: $request->metadata ?? null,
            is_active: $request->is_active ?? true
        );

        $user = $this->service->create($dto);

        return ApiResponse::send(data: new UserResource($user), message: "User created", success: true, code: 201);
    }

    public function show($id)
    {
        $user = $this->service->showUser($id);
        $this->service->findOrFail($id);
        return ApiResponse::send(new UserResource($user), "User retrieved");
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = $this->service->showUser($id);
        $this->service->findOrFail($id);

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
        $this->service->findOrFail($id);
        $this->service->delete($user);
        return ApiResponse::send(null, "User deleted");
    }
}
