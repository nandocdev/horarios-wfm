<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\CreateChannelAction;
use App\Modules\ConnectModule\Actions\DeleteChannelAction;
use App\Modules\ConnectModule\Actions\UpdateChannelAction;
use App\Modules\ConnectModule\DTOs\ChannelDTO;
use App\Modules\ConnectModule\Livewire\Forms\ChannelForm;
use App\Modules\ConnectModule\Models\Channel;
use App\Shared\Support\ManageCatalog;
use Livewire\Component;

class ListChannels extends Component
{
    use ManageCatalog;

    public ChannelForm $form;

    public ?Channel $editing = null;

    protected function catalogModel(): string
    {
        return Channel::class;
    }

    protected function catalogLabel(): string
    {
        return 'Canal';
    }

    public function create(): void
    {
        $this->editing = null;
        $this->form->reset();
    }

    public function edit(int $id): void
    {
        $this->editing = Channel::findOrFail($id);
        $this->form->fill($this->editing->toArray());
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

    protected function performDelete(object $record): void
    {
        $action = app(DeleteChannelAction::class);
        $action->execute($record);
        $this->dispatch('notify', 'Canal eliminado');
    }

    protected function resetForm(): void
    {
        $this->editing = null;
        $this->form->reset();
    }

    protected function loadForm(object $record): void
    {
        // Inline form — loadForm is handled by edit()
    }

    public function render()
    {
        return view('connect::livewire.list-channels', [
            'channels' => Channel::when($this->search, fn ($q) => $q->where('name', 'ilike', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
        ]);
    }
}
