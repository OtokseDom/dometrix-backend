<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Auth\Services\AuthService;
use App\Domain\Auth\DTOs\LoginDTO;
use App\Domain\Auth\DTOs\RegisterDTO;
use App\Domain\Auth\DTOs\PasswordResetDTO;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\PasswordResetRequest;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function register(RegisterRequest $request)
    {
        $dto = new RegisterDTO(
            name: $request->name,
            email: $request->email,
            password: $request->password,
            role_id: $request->role_id,
            organization_id: $request->organization_id
        );

        $user = $this->service->register($dto);
        return ApiResponse::send($user, "User registered successfully", 201);
    }

    public function login(LoginRequest $request)
    {
        $dto = new LoginDTO($request->email, $request->password);
        $token = $this->service->login($dto);

        if (!$token) return ApiResponse::send(null, "Invalid credentials", 401);

        return ApiResponse::send(['token' => $token], "Login successful");
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $this->service->logout($user);

        return ApiResponse::send(null, "Logged out successfully");
    }

    public function passwordReset(PasswordResetRequest $request)
    {
        $dto = new PasswordResetDTO(
            email: $request->email,
            token: $request->token,
            new_password: $request->new_password
        );

        $success = $this->service->resetPassword($dto);
        if (!$success) return ApiResponse::send(null, "Invalid token or email", 400);

        return ApiResponse::send(null, "Password reset successful");
    }
}
