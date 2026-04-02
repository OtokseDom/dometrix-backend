<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('user_id')->nullable();
            $table->string('module')->comment('Module: inventory, manufacturing, purchase, etc.');
            $table->string('entity_type')->comment('Entity: Material, Order, Movement, etc.');
            $table->uuid('entity_id')->comment('ID of the affected entity');
            $table->enum('action', ['CREATE', 'UPDATE', 'DELETE', 'APPROVE', 'VOID', 'RECEIVE', 'ISSUE', 'TRANSFER', 'ADJUST', 'CONSUME'])->comment('Action performed');
            $table->jsonb('old_values')->nullable()->comment('State before change');
            $table->jsonb('new_values')->nullable()->comment('State after change');
            $table->text('remarks')->nullable()->comment('Business reason or description');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Indexes for audit trails
            $table->index(['organization_id', 'module', 'entity_type']);
            $table->index(['organization_id', 'user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
