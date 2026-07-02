<?php

declare(strict_types=1);

namespace App\Src\Identity\Domain\Repositories;

use App\Src\Identity\Domain\Entities\AppSetting;

interface AppSettingRepositoryInterface
{
    public function findByKey(string $key): ?AppSetting;

    public function save(AppSetting $setting): AppSetting;

    public function delete(AppSetting $setting): void;

    public function all(): array;

    public function get(string $key, mixed $default = null): mixed;

    public function set(string $key, mixed $value, ?string $type = null, ?string $description = null): AppSetting;
}
