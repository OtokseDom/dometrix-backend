<?php

namespace App\Domain\Role\Services;

use App\Domain\Role\Models\Role;
use App\Domain\Role\DTOs\CreateRoleDTO;
use App\Domain\Role\DTOs\UpdateRoleDTO;
use Illuminate\Support\Str;

class RoleService
{
    public function create(CreateRoleDTO $dto): Role
    {
        return Role::create([
            'id' => Str::uuid(),
            'name' => $dto->name,
            'permissions' => $dto->permissions
        ]);
    }

    public function listAll()
    {
        return Role::all();
    }

    public function findById(string $id): ?Role
    {
        return Role::find($id);
    }

    public function update(Role $role, UpdateRoleDTO $dto): Role
    {
        $role->update([
            'name' => $dto->name ?? $role->name,
            'permissions' => $dto->permissions ?? $role->permissions,
        ]);

        return $role;
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
