<?php

namespace App\Domain\User\DTOs;

class UpdateUserDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $password = null,
        public ?string $role_id = null,
        public ?string $organization_id = null,
        public ?array $metadata = null,
        public ?bool $is_active = null
    ) {}
}
