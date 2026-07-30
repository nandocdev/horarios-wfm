<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire\Admin;

use App\Shared\Services\NotificationConfigService;
use Flux;
use Livewire\Component;

class NotificationAdmin extends Component
{
    public array $configs = [];

    public ?string $editingEventType = null;

    public bool $editIsEnabled = true;

    public array $editChannels = [];

    public function mount(NotificationConfigService $service): void
    {
        $this->loadConfigs($service);
    }

    public function loadConfigs(NotificationConfigService $service): void
    {
        $this->configs = array_map(function ($config) {
            return [
                'id' => $config->id,
                'event_type' => $config->event_type,
                'label' => $config->label,
                'description' => $config->description,
                'is_enabled' => $config->is_enabled,
                'channels' => $config->channels ?? ['database', 'broadcast'],
            ];
        }, $service->getAllConfigs());
    }

    public function startEdit(string $eventType): void
    {
        $config = collect($this->configs)->firstWhere('event_type', $eventType);

        if (! $config) {
            return;
        }

        $this->editingEventType = $eventType;
        $this->editIsEnabled = $config['is_enabled'];
        $this->editChannels = $config['channels'];
    }

    public function cancelEdit(): void
    {
        $this->editingEventType = null;
        $this->reset('editIsEnabled', 'editChannels');
    }

    public function save(NotificationConfigService $service): void
    {
        if (! $this->editingEventType) {
            return;
        }

        $service->upsert($this->editingEventType, [
            'is_enabled' => $this->editIsEnabled,
            'channels' => $this->editChannels,
        ]);

        $this->loadConfigs($service);
        $this->cancelEdit();

        Flux::toast('Configuración de notificación actualizada.', variant: 'success');
    }

    public function render()
    {
        $channelOptions = [
            'database' => 'Base de Datos',
            'broadcast' => 'Tiempo Real (Push)',
            'webex' => 'Webex',
            'mail' => 'Correo Electrónico',
        ];

        return view('core::livewire.admin.notification-admin', [
            'channelOptions' => $channelOptions,
        ])->layout('layouts.app', ['title' => 'Administración de Notificaciones']);
    }
}
