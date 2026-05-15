<?php

declare(strict_types=1);

namespace App\Modules\FilesystemModule\Actions;

use App\Modules\CoreModule\Models\User;
use App\Modules\FilesystemModule\Models\StorageQuota;
use Illuminate\Support\Facades\Cache;

class GetUserQuotaAction
{
    private const DEFAULT_QUOTA = 100 * 1024 * 1024; // 100MB por defecto

    public function execute(User $user): int
    {
        return Cache::remember("user_quota_{$user->id}", 3600, function () use ($user) {
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
