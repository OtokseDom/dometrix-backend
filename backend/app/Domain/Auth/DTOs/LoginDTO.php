<?php

namespace App\Domain\Auth\DTOs;

class LoginDTO
{
    public function __construct(
        public string $email,
        public string $password
    ) {}
}
