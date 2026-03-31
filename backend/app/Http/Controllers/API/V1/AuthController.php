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
use App\Http\Resources\AuthUserResource;
use App\Http\Resources\OrganizationUserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            organization_name: $request->organization_name,
            organization_code: $request->organization_code,
            role_id: $request->role_id
        );

        $organizationUser = $this->service->register($dto);

        $masterData = [
            'roles' => DB::table('roles')
                ->where('organization_id', $organizationUser->organization_id)
                ->count(),
            'products' => DB::table('products')
                ->where('organization_id', $organizationUser->organization_id)
                ->count(),
            'materials' => DB::table('materials')
                ->where('organization_id', $organizationUser->organization_id)
                ->count(),
            'boms' => DB::table('boms')
                ->where('organization_id', $organizationUser->organization_id)
                ->count(),
            'warehouses' => DB::table('warehouses')
                ->where('organization_id', $organizationUser->organization_id)
                ->count(),
            'categories' => DB::table('categories')
                ->where('organization_id', $organizationUser->organization_id)
                ->count(),
            'settings' => DB::table('settings')
                ->where('organization_id', $organizationUser->organization_id)
                ->exists(),
        ];

        $data = [
            'user' => new AuthUserResource($organizationUser->user),
            'role' => $organizationUser->role,
            'master_data_counts' => $masterData,
        ];

        return ApiResponse::send(
            $data,
            "User registered successfully",
            true,
            201
        );
    }

    public function login(LoginRequest $request)
    {
        $dto = new LoginDTO($request->email, $request->password);
        $token = $this->service->login($dto);

        if (!$token) {
            return ApiResponse::send(null, "Invalid credentials", false, 401);
        }

        // Get authenticated user with organizations
        $user = $this->service->getUserWithOrgs($request->email);

        return ApiResponse::send([
            'token' => $token,
            'user' => new AuthUserResource($user),
        ], "Login successful");
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
        if (!$success) {
            return ApiResponse::send(null, "Invalid token or email", false, 500);
        }

        return ApiResponse::send(null, "Password reset successful");
    }
}
