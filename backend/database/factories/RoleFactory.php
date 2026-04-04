<?php

namespace Database\Factories;

use App\Domain\Role\Models\Role;
use App\Domain\Organization\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'name' => $this->faker->unique()->word(),
            'permissions' => [
                'materials' => ['create', 'read', 'update', 'delete'],
                'products' => ['create', 'read', 'update', 'delete'],
                'boms' => ['create', 'read', 'update', 'delete'],
                'inventory' => ['create', 'read', 'update', 'delete'],
            ],
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Admin',
            'permissions' => [
                'materials' => ['create', 'read', 'update', 'delete'],
                'products' => ['create', 'read', 'update', 'delete'],
                'boms' => ['create', 'read', 'update', 'delete'],
                'inventory' => ['create', 'read', 'update', 'delete'],
                'organization' => ['create', 'read', 'update', 'delete'],
                'users' => ['create', 'read', 'update', 'delete'],
                'roles' => ['create', 'read', 'update', 'delete'],
            ],
        ]);
    }

    public function viewer(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Viewer',
            'permissions' => [
                'materials' => ['read'],
                'products' => ['read'],
                'boms' => ['read'],
                'inventory' => ['read'],
            ],
        ]);
    }
}
