<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Services\Concerns\PaginatesQueries;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    use PaginatesQueries;

    public function __construct(private readonly AuditTrailService $auditTrailService) {}

    public function index(array $query): array
    {
        [$page, $pageSize, $offset] = $this->pagination($query);

        $builder = DB::table('users as u')
            ->leftJoin('user_preferences as up', 'up.user_id', '=', 'u.id');

        if (! empty($query['search'])) {
            $search = trim((string) $query['search']);
            $builder->where(function ($subQuery) use ($search): void {
                $subQuery->where('u.username', 'like', '%'.$search.'%')
                    ->orWhere('u.display_name', 'like', '%'.$search.'%')
                    ->orWhere('u.email', 'like', '%'.$search.'%');
            });
        }

        if (! empty($query['status'])) {
            $builder->where('u.status', $query['status']);
        }

        if (! empty($query['role'])) {
            $builder->whereExists(function ($subQuery) use ($query): void {
                $subQuery->selectRaw('1')
                    ->from('user_roles as ur')
                    ->join('roles as r', 'r.id', '=', 'ur.role_id')
                    ->whereColumn('ur.user_id', 'u.id')
                    ->where('r.code', $query['role']);
            });
        }

        $total = (clone $builder)->distinct()->count('u.id');

        $rows = $builder->select([
            'u.id',
            'u.username',
            'u.display_name',
            'u.email',
            'u.status',
            'u.created_at',
            'u.updated_at',
            'up.language',
            'up.display_density',
            'up.default_workspace_tab',
        ])
            ->orderByDesc('u.id')
            ->distinct()
            ->offset($offset)
            ->limit($pageSize)
            ->get();

        $userIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rolesByUserId = $this->rolesByUserIds($userIds);

        $data = $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'username' => $row->username,
            'display_name' => $row->display_name,
            'email' => $row->email,
            'status' => $row->status,
            'roles' => $rolesByUserId[(int) $row->id] ?? [],
            'preferences' => [
                'language' => $row->language,
                'display_density' => $row->display_density ?? 'comfortable',
                'default_workspace_tab' => $row->default_workspace_tab,
            ],
            'created_at' => $row->created_at,
            'updated_at' => $row->updated_at,
        ])->all();

        return compact('data', 'page', 'pageSize', 'total');
    }

    public function store(array $payload): array
    {
        $roleIds = $this->resolveRoleIds($payload['roles']);

        $userId = DB::transaction(function () use ($payload, $roleIds) {
            $userId = DB::table('users')->insertGetId([
                'username' => $payload['username'],
                'display_name' => $payload['display_name'],
                'email' => $payload['email'] ?? null,
                'status' => $payload['status'],
                'password' => Hash::make($payload['password']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncRoles($userId, $roleIds);
            $this->ensureUserPreferenceRow($userId);

            $this->auditTrailService->record('user.created', 'user', $userId, null, [
                'username' => $payload['username'],
                'status' => $payload['status'],
                'roles' => array_values($payload['roles']),
            ]);

            return $userId;
        });

        return $this->show($userId);
    }

    public function show(int $id): array
    {
        $user = DB::table('users as u')
            ->leftJoin('user_preferences as up', 'up.user_id', '=', 'u.id')
            ->select([
                'u.id',
                'u.username',
                'u.display_name',
                'u.email',
                'u.status',
                'u.created_at',
                'u.updated_at',
                'up.language',
                'up.display_density',
                'up.default_workspace_tab',
                'up.settings_json',
            ])
            ->where('u.id', $id)
            ->first();

        if (! $user) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }

        return [
            'id' => (int) $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $this->rolesForUser($id),
            'preferences' => [
                'language' => $user->language,
                'display_density' => $user->display_density ?? 'comfortable',
                'default_workspace_tab' => $user->default_workspace_tab,
                'settings_json' => $this->decodeJson($user->settings_json),
            ],
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    public function update(int $id, array $payload): array
    {
        $current = DB::table('users')->where('id', $id)->first();

        if (! $current) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }

        if (array_key_exists('username', $payload) && $payload['username'] !== $current->username) {
            throw new ApiException('VALIDATION_ERROR', 'username cannot be changed', 422);
        }

        $updates = Arr::only($payload, ['display_name', 'email', 'status']);

        if (array_key_exists('password', $payload)) {
            $updates['password'] = Hash::make($payload['password']);
        }

        if ($updates !== []) {
            $updates['updated_at'] = now();

            DB::transaction(function () use ($id, $payload, $updates): void {
                DB::table('users')->where('id', $id)->update($updates);

                $this->auditTrailService->record('user.updated', 'user', $id, null, [
                    'changed_fields' => array_keys(Arr::except($payload, ['password'])),
                ]);
            });
        }

        return $this->show($id);
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function replaceRoles(int $id, array $roles): array
    {
        $this->assertUserExists($id);
        $roleIds = $this->resolveRoleIds($roles);

        DB::transaction(function () use ($id, $roleIds, $roles): void {
            DB::table('user_roles')->where('user_id', $id)->delete();
            $this->syncRoles($id, $roleIds);

            $this->auditTrailService->record('user.roles.updated', 'user', $id, null, [
                'roles' => array_values($roles),
            ]);
        });

        return $this->show($id);
    }

    public function setStatus(int $id, string $status): array
    {
        $current = DB::table('users')->select(['id', 'status'])->where('id', $id)->first();

        if (! $current) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }

        if ($current->status !== $status) {
            DB::transaction(function () use ($id, $status): void {
                DB::table('users')->where('id', $id)->update([
                    'status' => $status,
                    'updated_at' => now(),
                ]);

                $this->auditTrailService->record($status === 'active' ? 'user.activated' : 'user.deactivated', 'user', $id, null, [
                    'status' => $status,
                ]);
            });
        }

        return $this->show($id);
    }

    /**
     * @param  array<int, string>  $roleCodes
     * @return array<int, int>
     */
    private function resolveRoleIds(array $roleCodes): array
    {
        $normalized = array_values(array_unique(array_map(fn ($role) => (string) $role, $roleCodes)));

        if ($normalized === []) {
            throw new ApiException('VALIDATION_ERROR', 'roles must not be empty', 422);
        }

        $roles = DB::table('roles')->whereIn('code', $normalized)->pluck('id', 'code');

        if ($roles->count() !== count($normalized)) {
            throw new ApiException('VALIDATION_ERROR', 'one or more roles are invalid', 422);
        }

        return array_values($roles->map(fn ($id) => (int) $id)->all());
    }

    /**
     * @param  array<int, int>  $roleIds
     */
    private function syncRoles(int $userId, array $roleIds): void
    {
        $rows = array_map(fn ($roleId) => [
            'user_id' => $userId,
            'role_id' => $roleId,
        ], $roleIds);

        DB::table('user_roles')->insert($rows);
    }

    private function ensureUserPreferenceRow(int $userId): void
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

    private function assertUserExists(int $id): void
    {
        if (! DB::table('users')->where('id', $id)->exists()) {
            throw new ApiException('NOT_FOUND', 'user not found', 404);
        }
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

    /**
     * @param  array<int, int>  $userIds
     * @return array<int, array<int, string>>
     */
    private function rolesByUserIds(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $rows = DB::table('roles as r')
            ->join('user_roles as ur', 'ur.role_id', '=', 'r.id')
            ->whereIn('ur.user_id', $userIds)
            ->orderBy('r.code')
            ->get(['ur.user_id', 'r.code']);

        $grouped = [];

        foreach ($rows as $row) {
            $grouped[(int) $row->user_id] ??= [];
            $grouped[(int) $row->user_id][] = (string) $row->code;
        }

        return $grouped;
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
