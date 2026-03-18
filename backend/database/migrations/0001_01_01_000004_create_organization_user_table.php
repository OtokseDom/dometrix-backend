<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Organization_User table
        Schema::create('organization_user', function (Blueprint $table) {
            $table->uuid('organization_id');
            $table->uuid('user_id');
            $table->uuid('role_id');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->onDelete('cascade')->onUpdate('cascade');

            // Composite primary key (prevents duplicates)
            $table->primary(['organization_id', 'user_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
    }
};
