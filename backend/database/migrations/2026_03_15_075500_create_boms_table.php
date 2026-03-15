<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('boms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('product_id');
            $table->string('version')->comment('BOM version code');
            $table->boolean('is_active')->default(false)->comment('Only one BOM per product should be active');
            $table->jsonb('metadata')->nullable()->comment('Extra fields for ERP extensions');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'product_id', 'version']);
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};
