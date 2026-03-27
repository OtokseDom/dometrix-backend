<?php

namespace App\Domain\User\Services;

use App\Domain\Organization\Models\Organization;
use App\Domain\User\Models\User;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function create(CreateUserDTO $dto): User
    {
        return User::create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'metadata' => $dto->metadata,
            'is_active' => $dto->is_active
        ]);
    }


    public function getUsers(int $perPage = 2): LengthAwarePaginator
    {
        $authUser = Auth::user();
        // Get all org IDs the auth user belongs to
        $orgIds = $authUser->organizations()->pluck('organizations.id');

        // Query users belonging to those organizations
        return User::whereIn('id', function ($query) use ($orgIds) {
            $query->select('user_id')
                ->from('organization_user')
                ->whereIn('organization_id', $orgIds);
        })->paginate($perPage);
    }

    public function findOrFail(string $id): User
    {
        $user = $this->showUser($id);
        if (!$user) {
            throw new ModelNotFoundException("User not found");
        }
        return $user;
    }

    public function showUser(string $id): ?User
    {
        return User::find($id);
    }

    public function update(User $user, UpdateUserDTO $dto): User
    {
        $data = [
            'name' => $dto->name ?? $user->name,
            'email' => $dto->email ?? $user->email,
            'metadata' => $dto->metadata ?? $user->metadata,
            'is_active' => $dto->is_active ?? $user->is_active
        ];

        if ($dto->password) {
            $data['password'] = Hash::make($dto->password);
        }

        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
