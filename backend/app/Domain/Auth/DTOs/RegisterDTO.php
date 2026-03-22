<?php

namespace App\Domain\Auth\DTOs;

class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $organization_name = null,
        public ?string $organization_code = null,
        public ?string $role_id = null,
    ) {
    }
}
