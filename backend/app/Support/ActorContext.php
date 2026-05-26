<?php

namespace App\Support;

use Illuminate\Http\Request;

class ActorContext
{
    public function __construct(private readonly Request $request) {}

    public function id(): ?int
    {
        $actor = $this->request->attributes->get('ocean_actor');

        if (! is_array($actor) || ! isset($actor['id'])) {
            return null;
        }

        return (int) $actor['id'];
    }

    /**
     * @return array{id:int, username:string|null, display_name:string|null, roles:array<int,string>}|null
     */
    public function actor(): ?array
    {
        $actor = $this->request->attributes->get('ocean_actor');

        if (! is_array($actor) || ! isset($actor['id'])) {
            return null;
        }

        return $actor;
    }

    public function resolveActorId(array $payload, string $field): ?int
    {
        return $this->id() ?? (isset($payload[$field]) ? (int) $payload[$field] : null);
    }

    public function source(): string
    {
        return $this->id() === null ? 'payload' : 'request_header';
    }
}
