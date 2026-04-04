<?php

namespace Database\Factories;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Organization\Models\Organization;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $actions = ['CREATE', 'UPDATE', 'DELETE', 'APPROVE', 'VOID', 'RECEIVE', 'ISSUE', 'TRANSFER', 'ADJUST', 'CONSUME'];
        $modules = ['inventory', 'manufacturing', 'purchase', 'organization', 'settings'];
        $entityTypes = ['Material', 'Product', 'Bom', 'InventoryMovement', 'InventoryBatch', 'Order'];

        return [
            'id' => Str::uuid(),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'module' => $this->faker->randomElement($modules),
            'entity_type' => $this->faker->randomElement($entityTypes),
            'entity_id' => Str::uuid(),
            'action' => $this->faker->randomElement($actions),
            'old_values' => [
                'status' => 'ACTIVE',
                'quantity' => 100,
            ],
            'new_values' => [
                'status' => 'INACTIVE',
                'quantity' => 50,
            ],
            'remarks' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => 'Mozilla/5.0 (Testing)',
        ];
    }
}
