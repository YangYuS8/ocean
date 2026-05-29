<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Support\ActorContext;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function __construct(
        private readonly ActorContext $actorContext,
        private readonly AuditTrailService $auditTrailService
    ) {}

    public function showProfile(): array
    {
        $userId = $this->currentUserId();

        return $this->userWithPreferences($userId);
    }

    public function updateProfile(array $payload): array
    {
        $userId = $this->currentUserId();
        $updates = Arr::only($payload, ['display_name', 'email']);

        if ($updates !== []) {
            DB::transaction(function () use ($userId, $payload, $updates): void {
                DB::table('users')->where('id', $userId)->update(array_merge($updates, [
                    'updated_at' => now(),
                ]));

                $this->auditTrailService->record('profile.updated', 'user', $userId, null, [
                    'changed_fields' => array_keys($payload),
                ]);
            });
        }

        return $this->userWithPreferences($userId);
    }

    public function showSettings(): array
    {
        $userId = $this->currentUserId();

        return $this->preferencesForUser($userId);
    }

    public function updateSettings(array $payload): array
    {
        $userId = $this->currentUserId();
        $this->ensurePreferenceRow($userId);

        if ($payload !== []) {
            DB::transaction(function () use ($userId, $payload): void {
                $updates = [];

                if (array_key_exists('language', $payload)) {
                    $updates['language'] = $payload['language'];
                }

                if (array_key_exists('display_density', $payload)) {
                    $updates['display_density'] = $payload['display_density'];
                }

                if (array_key_exists('default_workspace_tab', $payload)) {
                    $updates['default_workspace_tab'] = $payload['default_workspace_tab'];
                }

                if (array_key_exists('settings_json', $payload)) {
                    $updates['settings_json'] = $payload['settings_json'] === null
                        ? null
                        : json_encode($payload['settings_json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $updates['updated_at'] = now();

                DB::table('user_preferences')->where('user_id', $userId)->update($updates);

                $this->auditTrailService->record('settings.updated', 'user', $userId, null, [
                    'changed_fields' => array_keys($payload),
                ]);
            });
        }

        return $this->preferencesForUser($userId);
    }

    private function currentUserId(): int
    {
        $actor = $this->actorContext->actor();

        if (! is_array($actor) || ! isset($actor['id'])) {
            throw new ApiException('UNAUTHENTICATED', 'login is required', 401);
        }

        return (int) $actor['id'];
    }

    private function userWithPreferences(int $userId): array
    {
        $user = DB::table('users')->select(['id', 'username', 'display_name', 'email', 'status', 'created_at', 'updated_at'])->where('id', $userId)->first();

        if (! $user) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }

        return [
            'id' => (int) $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $this->rolesForUser($userId),
            'preferences' => $this->preferencesForUser($userId),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function preferencesForUser(int $userId): array
    {
        $this->ensurePreferenceRow($userId);

        $preference = DB::table('user_preferences')->where('user_id', $userId)->first();

        return [
            'user_id' => $userId,
            'language' => $preference->language,
            'display_density' => $preference->display_density ?? 'comfortable',
            'default_workspace_tab' => $preference->default_workspace_tab,
            'settings_json' => $this->decodeJson($preference->settings_json),
            'updated_at' => $preference->updated_at,
        ];
    }

    private function ensurePreferenceRow(int $userId): void
    {
        if (DB::table('user_preferences')->where('user_id', $userId)->exists()) {
            return;
        }

        DB::table('user_preferences')->insert([
            'user_id' => $userId,
            'display_density' => 'comfortable',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function rolesForUser(int $userId): array
    {
        return DB::table('roles as r')
            ->join('user_roles as ur', 'ur.role_id', '=', 'r.id')
            ->where('ur.user_id', $userId)
            ->orderBy('r.code')
            ->pluck('r.code')
            ->map(fn ($role) => (string) $role)
            ->all();
    }

    private function decodeJson(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
