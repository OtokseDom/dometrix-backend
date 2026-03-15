<?php

namespace App\Domain\Role\DTOs;

class UpdateRoleDTO
{
    public function __construct(
        public ?string $name = null,
        public ?array $permissions = null
    ) {}
}
