<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Actions\CreateChannelAction;
use App\Modules\ConnectModule\DTOs\ChannelDTO;
use App\Modules\ConnectModule\Models\Channel;
use App\Modules\CoreModule\Models\User;
use Illuminate\Support\Str;

it('can create a channel via action', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $dto = ChannelDTO::fromArray([
        'name' => 'Canal prueba '.Str::random(4),
        'description' => 'desc',
        'is_active' => true,
    ]);

    $action = new CreateChannelAction;
    $channel = $action->execute($dto);

    expect(Channel::where('name', $channel->name)->exists())->toBeTrue();
});
