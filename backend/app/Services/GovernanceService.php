<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class GovernanceService
{
    private const SYSTEM_WORKER_HEADER = 'ocean-analysis-worker';

    /** @var array<string, array<int, string>> */
    private const ROLE_PERMISSIONS = [
        'admin' => ['*'],
        'inspector' => [
            'inspection_task.start',
            'inspection_task.submit',
            'sample.create',
            'sample.image.upload',
            'exception.create',
        ],
        'analyst' => [
            'sample_result.create',
            'exception.create',
            'exception.resolve',
            'analysis_job.create',
            'analysis_job.cancel',
            'analysis_job.retry',
        ],
        'worker' => [
            'analysis_job.start',
            'analysis_job.succeed',
            'analysis_job.fail',
        ],
    ];

    /**
     * @return array{id:int, username:string|null, display_name:string|null, roles:array<int,string>}
     */
    public function actorFromHeader(?string $actorIdHeader): array
    {
        if ($actorIdHeader === null || trim($actorIdHeader) === '') {
            throw new ApiException('UNAUTHENTICATED', 'actor identity header is required', 401);
        }

        if (! ctype_digit((string) $actorIdHeader)) {
            throw new ApiException('UNAUTHENTICATED', 'actor identity header must be a user id', 401);
        }

        return $this->actorForUserId((int) $actorIdHeader);
    }

    /**
     * @return array{id:int, username:string|null, display_name:string|null, roles:array<int,string>}
     */
    public function actorForUserId(int $userId): array
    {
        $user = DB::table('users')->select(['id', 'username', 'display_name', 'status'])->where('id', $userId)->first();

        if (! $user || $user->status !== 'active') {
            throw new ApiException('UNAUTHENTICATED', 'actor identity is not active', 401);
        }

        return [
            'id' => (int) $user->id,
            'username' => $user->username,
            'display_name' => $user->display_name,
            'roles' => $this->rolesForUser((int) $user->id),
        ];
    }

    /**
     * @return array{id:int, username:string|null, display_name:string|null, roles:array<int,string>}
     */
    public function workerActorFromHeader(?string $workerHeader): array
    {
        if ($workerHeader !== self::SYSTEM_WORKER_HEADER) {
            throw new ApiException('UNAUTHENTICATED', 'worker identity header is required', 401);
        }

        $user = DB::table('users')->select(['id', 'username', 'display_name', 'status'])->where('username', 'worker01')->first();

        if (! $user || $user->status !== 'active') {
            throw new ApiException('UNAUTHENTICATED', 'worker identity is not active', 401);
        }

        return $this->actorForUserId((int) $user->id);
    }

    /**
     * @return array<int, array{code:string, name:string, permissions:array<int,string>}>
     */
    public function roleCatalog(): array
    {
        $roles = DB::table('roles')->select(['code', 'name'])->orderBy('code')->get();

        return $roles->map(fn ($role) => [
            'code' => $role->code,
            'name' => $role->name,
            'permissions' => self::ROLE_PERMISSIONS[$role->code] ?? [],
        ])->all();
    }

    /**
     * @param  array<int, string>  $roles
     */
    public function can(array $roles, string $permission): bool
    {
        foreach ($roles as $role) {
            $permissions = self::ROLE_PERMISSIONS[$role] ?? [];

            if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
                return true;
            }
        }

        return false;
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
}
