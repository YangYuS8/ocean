<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('language', 20)->nullable();
            $table->string('display_density', 30)->default('comfortable');
            $table->string('default_workspace_tab', 50)->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'fk_user_preferences_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
