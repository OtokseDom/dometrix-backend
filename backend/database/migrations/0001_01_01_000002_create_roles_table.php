<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->string('name')->comment('Role name like Admin, Viewer, System');
            $table->jsonb('permissions')->nullable()->comment('JSON permissions per module');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('cascade')->onUpdate('cascade');
            // Composite unique for organization_id + name
            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
