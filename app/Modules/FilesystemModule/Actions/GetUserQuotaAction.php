<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Actions;

use App\Modules\CoreModule\Models\User;
use App\Modules\FilesystemModule\Models\StorageQuota;
use App\Shared\Support\Cache\CachePolicyService;
use Illuminate\Support\Facades\Cache;

class GetUserQuotaAction
{
    private const DEFAULT_QUOTA = 100 * 1024 * 1024; // 100MB por defecto

    public function __construct(
        private readonly CachePolicyService $cachePolicy,
    ) {}

    public function execute(User $user): int
    {
        return $this->cachePolicy->remember('filesystem', 'config', "user_quota:{$user->id}", function () use ($user) {
            // 1. Buscar cuota específica de usuario
            $userQuota = StorageQuota::where('target_type', 'user')
                ->where('target_id', $user->id)
                ->first();

            if ($userQuota) {
                return (int) $userQuota->quota_limit;
            }

            // 2. Buscar cuota por rol (máxima entre sus roles)
            $roleIds = $user->roles->pluck('id');
            if ($roleIds->isNotEmpty()) {
                $maxRoleQuota = StorageQuota::where('target_type', 'role')
                    ->whereIn('target_id', $roleIds)
                    ->max('quota_limit');

                if ($maxRoleQuota) {
                    return (int) $maxRoleQuota;
                }
            }

            // 3. Cuota global por defecto
            return self::DEFAULT_QUOTA;
        });
    }
}
