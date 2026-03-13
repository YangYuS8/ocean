<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('analysis_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sample_id')->index('idx_analysis_jobs_sample_id');
            $table->string('job_type', 50)->index('idx_analysis_jobs_job_type');
            $table->string('status', 20)->default('queued')->index('idx_analysis_jobs_status');
            $table->json('params_json')->nullable();
            $table->text('result_summary')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('queued_by')->nullable()->index('idx_analysis_jobs_queued_by');
            $table->dateTime('queued_at')->nullable()->index('idx_analysis_jobs_queued_at');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('sample_id', 'fk_analysis_jobs_sample_id')->references('id')->on('samples');
            $table->foreign('queued_by', 'fk_analysis_jobs_queued_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_jobs');
    }
};
