<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('samples', function (Blueprint $table) {
            $table->string('main_image_path', 255)->nullable()->after('notes');
            $table->string('main_image_name', 255)->nullable()->after('main_image_path');
            $table->string('main_image_mime_type', 100)->nullable()->after('main_image_name');
            $table->unsignedInteger('main_image_size')->nullable()->after('main_image_mime_type');
            $table->unsignedInteger('main_image_version')->default(0)->after('main_image_size');
            $table->dateTime('main_image_uploaded_at')->nullable()->after('main_image_version');
        });

        Schema::table('analysis_jobs', function (Blueprint $table) {
            $table->json('suggestion_json')->nullable()->after('result_summary');
        });
    }

    public function down(): void
    {
        Schema::table('analysis_jobs', function (Blueprint $table) {
            $table->dropColumn('suggestion_json');
        });

        Schema::table('samples', function (Blueprint $table) {
            $table->dropColumn([
                'main_image_path',
                'main_image_name',
                'main_image_mime_type',
                'main_image_size',
                'main_image_version',
                'main_image_uploaded_at',
            ]);
        });
    }
};
