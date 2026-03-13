<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sample_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sample_id')->index('idx_sample_results_sample_id');
            $table->string('result_type', 50)->index('idx_sample_results_result_type');
            $table->string('status', 20)->default('draft')->index('idx_sample_results_status');
            $table->json('raw_value')->nullable();
            $table->json('normalized_value')->nullable();
            $table->string('conclusion', 255)->nullable();
            $table->unsignedBigInteger('entered_by')->nullable()->index('idx_sample_results_entered_by');
            $table->dateTime('entered_at')->nullable();
            $table->string('review_status', 20)->nullable()->index('idx_sample_results_review_status');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sample_id', 'fk_sample_results_sample_id')->references('id')->on('samples');
            $table->foreign('entered_by', 'fk_sample_results_entered_by')->references('id')->on('users');
            $table->foreign('reviewed_by', 'fk_sample_results_reviewed_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sample_results');
    }
};
