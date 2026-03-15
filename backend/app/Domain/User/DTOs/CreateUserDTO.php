<?php

namespace App\Domain\User\DTOs;

class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $role_id = null,
        public ?string $organization_id = null,
        public ?array $metadata = null,
        public ?bool $is_active = true
    ) {}
}
