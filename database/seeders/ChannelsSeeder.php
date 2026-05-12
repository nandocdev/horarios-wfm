<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\ConnectModule\Models\Channel;
use Illuminate\Database\Seeder;

class ChannelsSeeder extends Seeder
{
    /**
     * Seed the channels table with default contact center channels.
     */
    public function run(): void
    {
        $names = [
            'Inbound',
            'Outbound',
            'WhatsApp',
            'Telegram',
            'Webchat',
        ];

        foreach ($names as $name) {
            Channel::updateOrCreate([
                'name' => $name,
            ], [
                'description' => sprintf('Canal predeterminado: %s', $name),
                'is_active' => true,
            ]);
        }
    }
}
