<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique()->comment('Short code like kg, pcs, liter');
            $table->string('name')->comment('Full name like kilogram, piece, liter');
            $table->jsonb('metadata')->nullable()->comment('Future extensions for conversion rates');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_of_measures');
    }
};
