<?php

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\DTOs\PasswordResetDTO;
use App\Domain\Organization\Models\Organization;
use App\Domain\Organization\Models\OrganizationUser;
use App\Domain\Organization\Services\OrganizationService;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthService
{
    protected OrganizationService $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     * @throws Throwable
     */
    public function register(RegisterDTO $dto): OrganizationUser
    {
        $result = null;

        DB::transaction(function () use ($dto, &$result) {
            $status = 'pending';

            if (!empty($dto->organization_name) && empty($dto->organization_code)) {
                $organization = Organization::create([
                    'name' => $dto->organization_name,
                    'code' => strtoupper(uniqid()),
                    'timezone' => 'UTC',
                    'currency' => 'USD',
                    'metadata' => [],
                ]);
                $status = 'active';
            } elseif (!empty($dto->organization_code) && empty($dto->organization_name)) {
                $organization = Organization::where('code', $dto->organization_code)->firstOrFail();
            } else {
                throw new \Exception("Organization not valid");
            }

            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => Hash::make($dto->password),
            ]);

            $result = $this->organizationService->addUser($organization, $user->id, $dto->role_id, $status);
        });

        return $result;
    }

    public function login(LoginDTO $dto): ?string
    {
        $user = User::where('email', $dto->email)->first();
        if (!$user || !Hash::check($dto->password, $user->password)) {
            return null;
        }

        return $user->createToken('api_token')->plainTextToken;
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    public function resetPassword(PasswordResetDTO $dto): bool
    {
        $record = DB::table('password_reset_tokens')->where('email', $dto->email)->first();
        if (!$record || $record->token !== $dto->token) {
            return false;
        }

        $user = User::where('email', $dto->email)->firstOrFail();
        $user->password = Hash::make($dto->new_password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $dto->email)->delete();

        return true;
    }

    public function getUserWithOrgs(string $email): ?User
    {
        return User::where('email', $email)
            ->with('organizations') // eager load pivot info
            ->first();
    }
}
