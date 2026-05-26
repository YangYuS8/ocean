<?php

namespace App\Services;

use App\Support\ActorContext;
use Illuminate\Support\Facades\DB;

class AuditTrailService
{
    public function __construct(private readonly ActorContext $actorContext) {}

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(string $eventType, string $resourceType, int $resourceId, ?int $actorId = null, ?array $metadata = null): void
    {
        DB::table('audit_events')->insert([
            'event_type' => $eventType,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'actor_id' => $actorId ?? $this->actorContext->id(),
            'actor_source' => $this->actorContext->source(),
            'metadata_json' => $metadata === null ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
    }
}
