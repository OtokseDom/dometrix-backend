<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('material_id');
            $table->uuid('warehouse_id');
            $table->string('batch_number')->comment('Lot/batch identifier from supplier or production');
            $table->date('manufactured_date')->nullable()->comment('When the batch was produced');
            $table->date('received_date')->comment('When received into warehouse');
            $table->date('expiry_date')->nullable()->comment('Expiry date if applicable');
            $table->decimal('received_qty', 20, 4)->comment('Initial quantity received');
            $table->decimal('remaining_qty', 20, 4)->comment('Current available quantity');
            $table->decimal('unit_cost', 20, 4)->comment('Cost per unit for this batch');
            $table->enum('status', ['ACTIVE', 'EXPIRED', 'CLOSED'])->default('ACTIVE');
            $table->jsonb('metadata')->nullable()->comment('Batch-specific data: certifications, grade, etc.');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');

            $table->index(['organization_id', 'material_id', 'warehouse_id', 'status']);
            $table->index(['expiry_date', 'status']);
            $table->index(['received_date']); // For FIFO ordering
            $table->unique(['organization_id', 'material_id', 'warehouse_id', 'batch_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_batches');
    }
};
