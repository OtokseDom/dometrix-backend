<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use Illuminate\Support\Collection;
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

    public function getUsers(string $organization_id): Collection
    {
        $query = User::query();
        if ($organization_id) {
            $query->where('organization_id', $organization_id);
        }
        return $query->get();
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
