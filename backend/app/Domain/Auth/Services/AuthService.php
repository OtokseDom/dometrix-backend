<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\DTOs\PasswordResetDTO;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function register(RegisterDTO $dto): User
    {
        return User::create([
            'id' => Str::uuid(),
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'role_id' => $dto->role_id,
            'organization_id' => $dto->organization_id
        ]);
    }

    public function login(LoginDTO $dto): ?string
    {
        $user = User::where('email', $dto->email)->first();
        if (!$user || !Hash::check($dto->password, $user->password)) return null;

        return $user->createToken('api_token')->plainTextToken;
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    public function resetPassword(PasswordResetDTO $dto): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $dto->email)->first();
        if (!$record || $record->token !== $dto->token) return false;

        $user = User::where('email', $dto->email)->firstOrFail();
        $user->password = Hash::make($dto->new_password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $dto->email)->delete();

        return true;
    }
}
