<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 80);
            $table->string('resource_type', 80);
            $table->unsignedBigInteger('resource_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_source', 40)->default('payload');
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['resource_type', 'resource_id'], 'idx_audit_events_resource');
            $table->index(['event_type', 'created_at'], 'idx_audit_events_event_created');
            $table->index('actor_id', 'idx_audit_events_actor_id');
            $table->foreign('actor_id', 'fk_audit_events_actor_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
