<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('samples', function (Blueprint $table) {
            $table->id();
            $table->string('sample_code', 50)->unique();
            $table->unsignedBigInteger('inspection_task_id')->nullable()->index('idx_samples_inspection_task_id');
            $table->string('sample_type', 50);
            $table->string('name', 200)->nullable();
            $table->string('status', 20)->default('registered');
            $table->dateTime('collection_time')->nullable()->index('idx_samples_collection_time');
            $table->string('location_text', 255)->nullable();
            $table->unsignedBigInteger('collector_id')->nullable()->index('idx_samples_collector_id');
            $table->unsignedBigInteger('received_by')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_samples_status');
            $table->foreign('inspection_task_id', 'fk_samples_inspection_task_id')->references('id')->on('inspection_tasks');
            $table->foreign('collector_id', 'fk_samples_collector_id')->references('id')->on('users');
            $table->foreign('received_by', 'fk_samples_received_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('samples');
    }
};
