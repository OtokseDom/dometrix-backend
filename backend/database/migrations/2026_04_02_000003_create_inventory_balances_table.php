<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('warehouse_id');
            $table->uuid('material_id');
            $table->uuid('batch_id')->nullable()->comment('If batch-tracked');
            $table->decimal('on_hand_qty', 20, 4)->default(0)->comment('Physical stock quantity');
            $table->decimal('reserved_qty', 20, 4)->default(0)->comment('Qty reserved for orders/production');
            $table->decimal('available_qty', 20, 4)->default(0)->comment('On-hand - reserved');
            $table->decimal('average_cost', 20, 4)->nullable()->comment('Weighted average cost per unit');
            $table->timestamp('updated_at');

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('inventory_batches')->nullOnDelete();

            $table->index(['organization_id', 'warehouse_id', 'material_id']);
            $table->unique(['organization_id', 'warehouse_id', 'material_id', 'batch_id'], 'unique_org_wh_mat_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
    }
};
