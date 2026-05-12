<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\CreateChannelAction;
use App\Modules\ConnectModule\Actions\DeleteChannelAction;
use App\Modules\ConnectModule\Actions\UpdateChannelAction;
use App\Modules\ConnectModule\DTOs\ChannelDTO;
use App\Modules\ConnectModule\Livewire\Forms\ChannelForm;
use App\Modules\ConnectModule\Models\Channel;
use Livewire\Component;

class ListChannels extends Component
{
    public ChannelForm $form;

    public ?Channel $editing = null;

    public function mount(): void
    {
        // Instanciar el Form object correctamente vinculándolo al componente Livewire
        $this->form = new ChannelForm($this, 'form');
    }

    public function render()
    {
        $channels = Channel::orderBy('name')->get();

        return view('connect::livewire.list-channels', compact('channels'));
    }

    public function save(CreateChannelAction $createAction, UpdateChannelAction $updateAction)
    {
        $this->authorize('manage', Channel::class);

        $dto = ChannelDTO::fromArray($this->form->toArray());

        if ($this->editing) {
            $updateAction->execute($this->editing, $dto);
            $this->dispatch('notify', 'Canal actualizado');
        } else {
            $createAction->execute($dto);
            $this->dispatch('notify', 'Canal creado');
        }

        $this->editing = null;
        $this->form->reset();
    }

    public function edit(Channel $channel)
    {
        $this->editing = $channel;
        $this->form->fill($channel->toArray());
    }

    public function resetForm(): void
    {
        $this->editing = null;
        $this->form->reset();
    }

    public function delete(Channel $channel, DeleteChannelAction $action)
    {
        $this->authorize('manage', Channel::class);
        $action->execute($channel);
        $this->dispatch('notify', 'Canal eliminado');
    }
}
