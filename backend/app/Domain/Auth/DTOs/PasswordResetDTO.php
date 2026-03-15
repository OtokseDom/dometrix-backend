<?php

namespace App\Domain\Auth\DTOs;

class PasswordResetDTO
{
    public function __construct(
        public string $email,
        public string $token,
        public string $new_password
    ) {}
}
