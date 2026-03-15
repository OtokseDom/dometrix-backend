<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('material_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('material_id');
            $table->decimal('price', 20, 4)->comment('Material price at effective date');
            $table->date('effective_date')->comment('Price effective from this date');
            $table->uuid('created_by')->nullable()->comment('User who created this price record');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');

            $table->index(['material_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_prices');
    }
};
