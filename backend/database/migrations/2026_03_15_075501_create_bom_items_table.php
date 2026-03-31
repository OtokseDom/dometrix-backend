<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bom_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('bom_id');
            $table->uuid('material_id')->nullable()->comment('Material used in BOM');
            $table->uuid('sub_product_id')->nullable()->comment('Optional: reference another product as subassembly');
            $table->decimal('quantity', 20, 4);
            $table->uuid('unit_id');
            $table->decimal('wastage_percent', 8, 4)->default(0);
            $table->integer('line_no');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('bom_id')->references('id')->on('boms')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('sub_product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('cascade');

            $table->index(['bom_id', 'material_id']);
            $table->index(['bom_id', 'sub_product_id']);

            // Prevent both material_id and sub_product_id from being set simultaneously
            // Add in request validation instead of DB constraint for better error handling
            // $table->check(
            //     '(material_id IS NOT NULL AND sub_product_id IS NULL) OR (material_id IS NULL AND sub_product_id IS NOT NULL)'
            // );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_items');
    }
};
