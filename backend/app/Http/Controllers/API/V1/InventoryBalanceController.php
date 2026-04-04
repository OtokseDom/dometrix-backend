<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Inventory\Models\InventoryBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;

class InventoryBalanceController extends Controller
{
    /**
     * List all inventory balances for the user's organization
     */
    public function index()
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();
        $organization = $user->organizations()->first();

        if (!$organization) {
            return ApiResponse::send([], 'No organization found', false, 403);
        }

        $balances = InventoryBalance::where('organization_id', $organization->id)
            ->with(['organization', 'warehouse', 'material', 'batch'])
            ->paginate(15);

        return ApiResponse::send($balances, 'Inventory balances retrieved successfully');
    }

    /**
     * Show a single inventory balance
     */
    public function show(string $id)
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();
        $organization = $user->organizations()->first();

        $balance = InventoryBalance::where('id', $id)
            ->where('organization_id', $organization->id)
            ->with(['organization', 'warehouse', 'material', 'batch'])
            ->firstOrFail();

        return ApiResponse::send($balance, 'Inventory balance retrieved successfully');
    }
}
