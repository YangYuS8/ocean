<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');

            $table->primary(['user_id', 'role_id']);
            $table->index('role_id', 'idx_user_roles_role_id');
            $table->foreign('user_id', 'fk_user_roles_user_id')->references('id')->on('users');
            $table->foreign('role_id', 'fk_user_roles_role_id')->references('id')->on('roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
