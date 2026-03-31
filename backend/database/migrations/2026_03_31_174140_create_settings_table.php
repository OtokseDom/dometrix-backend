<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id')->unique();

            // Inventory & costing
            $table->string('inventory_method')->default('fifo')->comment('FIFO, LIFO, etc.');
            $table->string('costing_method')->default('weighted_average')->comment('Costing calculation method');
            $table->boolean('allow_negative_stock')->default(false);
            $table->boolean('tax_inclusive_pricing')->default(true);

            // Financial
            $table->uuid('base_currency_id')->nullable();
            $table->uuid('default_tax_id')->nullable();
            $table->uuid('default_warehouse_id')->nullable();
            $table->integer('decimal_precision')->default(4);

            // Operational
            $table->string('timezone')->default('Asia/Dubai');
            $table->jsonb('metadata')->nullable()->comment('Flexible additional settings');

            $table->timestamps();

            $table->foreign('organization_id')
                ->references('id')
                ->on('organizations')
                ->onDelete('cascade');

            $table->foreign('base_currency_id')
                ->references('id')
                ->on('currencies')
                ->onDelete('restrict');

            $table->foreign('default_tax_id')
                ->references('id')
                ->on('taxes')
                ->onDelete('restrict');

            $table->foreign('default_warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
