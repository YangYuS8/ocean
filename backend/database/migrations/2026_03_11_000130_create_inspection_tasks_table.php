<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inspection_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_code', 50)->unique();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('task_type', 50);
            $table->string('priority', 20)->default('normal');
            $table->string('status', 20)->default('assigned');
            $table->string('location_text', 255)->nullable();
            $table->dateTime('planned_at')->nullable()->index('idx_inspection_tasks_planned_at');
            $table->dateTime('due_at')->nullable()->index('idx_inspection_tasks_due_at');
            $table->unsignedBigInteger('assigned_to')->nullable()->index('idx_inspection_tasks_assigned_to');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_inspection_tasks_status');
            $table->foreign('assigned_to', 'fk_inspection_tasks_assigned_to')->references('id')->on('users');
            $table->foreign('created_by', 'fk_inspection_tasks_created_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_tasks');
    }
};
