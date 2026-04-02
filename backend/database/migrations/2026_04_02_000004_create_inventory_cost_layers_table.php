<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('warehouse_id');
            $table->uuid('material_id');
            $table->uuid('batch_id')->nullable()->comment('Batch if available');
            $table->uuid('source_movement_id')->comment('The incoming movement that created this layer');
            $table->decimal('original_qty', 20, 4)->comment('Qty when layer created');
            $table->decimal('remaining_qty', 20, 4)->comment('Qty still available for COGS');
            $table->decimal('unit_cost', 20, 4)->comment('Cost per unit for COGS calculation');
            $table->timestamp('received_at')->comment('Used for FIFO ordering');
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('inventory_batches')->nullOnDelete();
            $table->foreign('source_movement_id')->references('id')->on('inventory_movements')->onDelete('cascade');

            $table->index(['organization_id', 'warehouse_id', 'material_id']);
            $table->index(['organization_id', 'material_id', 'received_at']); // For FIFO ordering
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
    }
};
