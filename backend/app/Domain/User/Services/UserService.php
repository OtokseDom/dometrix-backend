<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Domain\User\DTOs\CreateUserDTO;
use App\Domain\User\DTOs\UpdateUserDTO;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    public function create(CreateUserDTO $dto): User
    {
        return User::create([
            'id' => Str::uuid(),
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'role_id' => $dto->role_id,
            'organization_id' => $dto->organization_id,
            'metadata' => $dto->metadata,
            'is_active' => $dto->is_active
        ]);
    }

    public function listAll(string $organization_id)
    {
        $query = User::query();
        if ($organization_id) $query->where('organization_id', $organization_id);
        return $query->get();
    }

    public function findById(string $id): ?User
    {
        return User::find($id);
    }

    public function update(User $user, UpdateUserDTO $dto): User
    {
        $data = [
            'name' => $dto->name ?? $user->name,
            'email' => $dto->email ?? $user->email,
            'role_id' => $dto->role_id ?? $user->role_id,
            'organization_id' => $dto->organization_id ?? $user->organization_id,
            'metadata' => $dto->metadata ?? $user->metadata,
            'is_active' => $dto->is_active ?? $user->is_active
        ];

        if ($dto->password) $data['password'] = Hash::make($dto->password);

        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
