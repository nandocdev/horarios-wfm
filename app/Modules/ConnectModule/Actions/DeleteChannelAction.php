<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\Channel;
use Illuminate\Support\Facades\DB;

class DeleteChannelAction
{
    public function execute(Channel $channel): void
    {
        DB::transaction(function () use ($channel) {
            // soft delete not implemented; remove safely
            $channel->delete();
        });
    }
}
