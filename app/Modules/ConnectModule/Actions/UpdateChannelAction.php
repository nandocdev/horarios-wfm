<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\ChannelDTO;
use App\Modules\ConnectModule\Models\Channel;
use Illuminate\Support\Facades\DB;

class UpdateChannelAction
{
    public function execute(Channel $channel, ChannelDTO $dto): Channel
    {
        return DB::transaction(function () use ($channel, $dto) {
            $channel->update([
                'name' => $dto->name,
                'description' => $dto->description,
                'is_active' => $dto->is_active,
            ]);

            return $channel->refresh();
        });
    }
}
