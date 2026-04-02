<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('warehouse_id');
            $table->uuid('material_id');
            $table->uuid('batch_id')->nullable()->comment('Lot/batch reference for traceability');
            $table->string('reference_type')->comment('Order, Production, Adjustment, Transfer, etc.');
            $table->string('reference_id')->nullable()->comment('PO ID, Work Order ID, etc.');
            $table->enum('movement_type', [
                'PURCHASE_RECEIPT',
                'PRODUCTION_CONSUMPTION',
                'PRODUCTION_OUTPUT',
                'SALES_ISSUE',
                'ADJUSTMENT_IN',
                'ADJUSTMENT_OUT',
                'TRANSFER_IN',
                'TRANSFER_OUT',
                'RETURN_IN',
                'RETURN_OUT',
                'SCRAP_OUT'
            ]);
            $table->decimal('quantity', 20, 4)->comment('Absolute quantity moved');
            $table->uuid('unit_of_measure_id');
            $table->decimal('unit_cost', 20, 4)->nullable()->comment('Unit cost at time of movement');
            $table->decimal('total_cost', 20, 4)->nullable()->comment('Total transaction cost');
            $table->decimal('running_balance', 20, 4)->nullable()->comment('Snapshot of balance after movement');
            $table->enum('direction', ['IN', 'OUT'])->comment('Direction of stock flow');
            $table->uuid('performed_by')->nullable()->comment('User who created this movement');
            $table->text('remarks')->nullable();
            $table->jsonb('metadata')->nullable()->comment('Extended data: serial numbers, lot details, etc.');
            $table->timestamps();
            $table->softDeletes();

            // Indexes for common queries
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
            $table->foreign('unit_of_measure_id')->references('id')->on('units')->onDelete('restrict');
            $table->foreign('performed_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['organization_id', 'warehouse_id', 'material_id', 'created_at']);
            $table->index(['organization_id', 'reference_type', 'reference_id']);
            $table->index(['movement_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
