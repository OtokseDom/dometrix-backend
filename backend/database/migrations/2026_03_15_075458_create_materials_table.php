<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('code')->comment('Material unique code per organization');
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('unit_of_measure');
            $table->jsonb('metadata')->nullable()->comment('Extra fields for ERP extensions');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
