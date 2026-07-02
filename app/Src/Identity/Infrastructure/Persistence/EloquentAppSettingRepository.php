<?php

declare(strict_types=1);

namespace App\Src\Identity\Infrastructure\Persistence;

use App\Src\Identity\Domain\Entities\AppSetting;
use App\Src\Identity\Domain\Repositories\AppSettingRepositoryInterface;

final class EloquentAppSettingRepository implements AppSettingRepositoryInterface
{
    public function findByKey(string $key): ?AppSetting
    {
        $eloquent = EloquentAppSetting::where('key', $key)->first();

        if ($eloquent === null) {
            return null;
        }

        return $this->toDomain($eloquent);
    }

    public function save(AppSetting $setting): AppSetting
    {
        $eloquent = EloquentAppSetting::updateOrCreate(
            ['key' => $setting->key()],
            [
                'value' => $setting->value(),
                'type' => $setting->type(),
                'description' => $setting->description(),
            ],
        );

        return $this->toDomain($eloquent);
    }

    public function delete(AppSetting $setting): void
    {
        EloquentAppSetting::where('key', $setting->key())->delete();
    }

    public function all(): array
    {
        return EloquentAppSetting::orderBy('key')
            ->get()
            ->map(fn ($eloquent) => $this->toDomain($eloquent))
            ->toArray();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return EloquentAppSetting::get($key, $default);
    }

    public function set(string $key, mixed $value, ?string $type = null, ?string $description = null): AppSetting
    {
        $eloquent = EloquentAppSetting::set($key, $value, $type, $description);

        return $this->toDomain($eloquent);
    }

    private function toDomain(EloquentAppSetting $eloquent): AppSetting
    {
        return AppSetting::fromDatabase(
            id: $eloquent->id,
            key: $eloquent->key,
            value: $eloquent->value,
            type: $eloquent->type ?? 'string',
            description: $eloquent->description,
            createdAt: $eloquent->created_at instanceof \DateTimeImmutable
                ? $eloquent->created_at
                : new \DateTimeImmutable($eloquent->created_at->format('Y-m-d H:i:s')),
            updatedAt: $eloquent->updated_at instanceof \DateTimeImmutable
                ? $eloquent->updated_at
                : new \DateTimeImmutable($eloquent->updated_at->format('Y-m-d H:i:s')),
        );
    }
}
