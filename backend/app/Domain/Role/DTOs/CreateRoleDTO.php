<?php

namespace App\Domain\Role\DTOs;

class CreateRoleDTO
{
    public function __construct(
        public string $name,
        public ?array $permissions = null,
        public ?string $organization_id = null
    ) {}
}
