<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Livewire;

use App\Src\Connect\Application\DTOs\ChannelDTO;
use App\Src\Connect\Application\Handlers\CreateChannelHandler;
use App\Src\Connect\Application\Handlers\DeleteChannelHandler;
use App\Src\Connect\Application\Handlers\UpdateChannelHandler;
use App\Src\Connect\Infrastructure\Persistence\EloquentChannel;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Canales')]
class ListChannels extends Component
{
    public string $name = '';
    public string $type = 'voice';
    public bool $is_active = true;
    public ?EloquentChannel $editing = null;

    public function mount(): void
    {
        $this->authorize('viewAny', EloquentChannel::class);
    }

    public function save(
        CreateChannelHandler $createAction,
        UpdateChannelHandler $updateAction,
    ): void {
        if ($this->editing) {
            $this->authorize('update', $this->editing);
        } else {
            $this->authorize('create', EloquentChannel::class);
        }

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $dto = new ChannelDTO(
            name: $this->name,
            type: $this->type,
            isActive: $this->is_active,
        );

        if ($this->editing) {
            $updateAction->handle($this->editing->id, $dto);
            flux()->toast('Canal actualizado.', variant: 'success');
        } else {
            $createAction->handle($dto);
            flux()->toast('Canal creado.', variant: 'success');
        }

        $this->resetForm();
    }

    public function edit(string $channelId): void
    {
        $channel = EloquentChannel::findOrFail($channelId);
        $this->authorize('update', $channel);

        $this->editing = $channel;
        $this->name = $channel->name;
        $this->type = $channel->type ?? 'voice';
        $this->is_active = $channel->is_active;
    }

    public function delete(DeleteChannelHandler $action, string $channelId): void
    {
        $channel = EloquentChannel::findOrFail($channelId);
        $this->authorize('delete', $channel);

        $action->handle($channelId);
        $this->resetForm();
        flux()->toast('Canal eliminado.', variant: 'success');
    }

    public function resetForm(): void
    {
        $this->name = '';
        $this->type = 'voice';
        $this->is_active = true;
        $this->editing = null;
    }

    public function getChannelsProperty()
    {
        return EloquentChannel::orderBy('name')->get();
    }

    public function render()
    {
        return view('connect::livewire.list-channels', [
            'channels' => $this->channels,
        ]);
    }
}
