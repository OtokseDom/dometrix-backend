<?php

namespace App\Domain\Auth\DTOs;

class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $role_id = null,
        public ?string $organization_id = null
    ) {}
}
