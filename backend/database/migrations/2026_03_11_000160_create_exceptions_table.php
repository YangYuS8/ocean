<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exceptions', function (Blueprint $table) {
            $table->id();
            $table->string('resource_type', 50);
            $table->unsignedBigInteger('resource_id');
            $table->string('category', 50)->index('idx_exceptions_category');
            $table->string('severity', 20)->default('medium')->index('idx_exceptions_severity');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('open')->index('idx_exceptions_status');
            $table->unsignedBigInteger('reported_by')->nullable()->index('idx_exceptions_reported_by');
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['resource_type', 'resource_id'], 'idx_exceptions_resource');
            $table->index('created_at', 'idx_exceptions_created_at');
            $table->foreign('reported_by', 'fk_exceptions_reported_by')->references('id')->on('users');
            $table->foreign('resolved_by', 'fk_exceptions_resolved_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exceptions');
    }
};
