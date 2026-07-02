<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\Channel;
use App\Src\Connect\Domain\Repositories\ChannelRepositoryInterface;

final class EloquentChannelRepository implements ChannelRepositoryInterface
{
    public function save(Channel $channel): Channel
    {
        $data = ConnectMapper::channelToEloquent($channel);

        $eloquent = EloquentChannel::updateOrCreate(
            ['id' => $channel->id() ?? $data['id']],
            $data,
        );

        return ConnectMapper::channelToDomain($eloquent->fresh());
    }

    public function findById(string $id): ?Channel
    {
        $eloquent = EloquentChannel::find($id);
        return $eloquent ? ConnectMapper::channelToDomain($eloquent) : null;
    }

    public function findByName(string $name): ?Channel
    {
        $eloquent = EloquentChannel::where('name', $name)->first();
        return $eloquent ? ConnectMapper::channelToDomain($eloquent) : null;
    }

    public function findAll(): array
    {
        return EloquentChannel::all()
            ->map(fn (EloquentChannel $e) => ConnectMapper::channelToDomain($e))
            ->toArray();
    }

    public function findAllActive(): array
    {
        return EloquentChannel::where('is_active', true)
            ->get()
            ->map(fn (EloquentChannel $e) => ConnectMapper::channelToDomain($e))
            ->toArray();
    }

    public function delete(string $id): void
    {
        EloquentChannel::destroy($id);
    }
}
