<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Domain\Inventory\Models\InventoryBatch;
use App\Domain\Organization\Models\Organization;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryBatchController extends Controller
{
    /**
     * List all inventory batches for the user's organization
     */
    public function index(Request $request)
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();
        $organization = $user->organizations()->first();

        if (!$organization) {
            return ApiResponse::send([], 'No organization found', false, 403);
        }

        $batches = InventoryBatch::where('organization_id', $organization->id)
            ->with(['organization', 'warehouse', 'material'])
            ->paginate(15);

        return ApiResponse::send($batches, 'Inventory batches retrieved successfully');
    }

    /**
     * Show a single inventory batch
     */
    public function show(string $id)
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();
        $organization = $user->organizations()->first();

        $batch = InventoryBatch::where('id', $id)
            ->where('organization_id', $organization->id)
            ->with(['organization', 'warehouse', 'material'])
            ->firstOrFail();

        return ApiResponse::send($batch, 'Inventory batch retrieved successfully');
    }

    /**
     * Create a new inventory batch
     */
    public function store(Request $request)
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();
        $organization = $user->organizations()->first();

        $validated = $request->validate([
            'material_id' => 'required|uuid|exists:materials,id',
            'warehouse_id' => 'required|uuid|exists:warehouses,id',
            'batch_number' => 'required|string|unique:inventory_batches',
            'manufactured_date' => 'nullable|date',
            'received_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'received_qty' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'status' => 'required|string|in:ACTIVE,EXPIRED,CLOSED',
        ]);

        $batch = InventoryBatch::create([
            ...$validated,
            'organization_id' => $organization->id,
            'remaining_qty' => $validated['received_qty'],
        ]);

        return ApiResponse::send($batch, 'Inventory batch created successfully', true, 201);
    }

    /**
     * Update an inventory batch
     */
    public function update(Request $request, string $id)
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();
        $organization = $user->organizations()->first();

        $batch = InventoryBatch::where('id', $id)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $validated = $request->validate([
            'batch_number' => "sometimes|string|unique:inventory_batches,batch_number,{$id}",
            'manufactured_date' => 'sometimes|nullable|date',
            'received_date' => 'sometimes|date',
            'expiry_date' => 'sometimes|nullable|date',
            'remaining_qty' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|string|in:ACTIVE,EXPIRED,CLOSED',
        ]);

        $batch->update($validated);

        return ApiResponse::send($batch, 'Inventory batch updated successfully');
    }

    /**
     * Delete an inventory batch
     */
    public function destroy(string $id)
    {
        /** @var \App\Domain\User\Models\User $user */
        $user = Auth::user();
        $organization = $user->organizations()->first();

        $batch = InventoryBatch::where('id', $id)
            ->where('organization_id', $organization->id)
            ->firstOrFail();

        $batch->delete();

        return ApiResponse::send(null, 'Inventory batch deleted successfully');
    }
}
