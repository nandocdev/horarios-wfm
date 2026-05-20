<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire;

use App\Modules\CoreModule\Models\AppSetting;
use App\Modules\CoreModule\Models\User;
use App\Modules\CoreModule\Notifications\MaintenanceModeNotification;
use App\Shared\DTOs\NotificationDTO;
use Illuminate\Support\Facades\Notification;
use Livewire\Component;

class SystemMaintenance extends Component
{
    public bool $enabled = false;

    public string $message = '';

    public function mount(): void
    {
        $config = AppSetting::get('maintenance_mode', ['enabled' => false, 'message' => '']);
        $this->enabled = $config['enabled'] ?? false;
        $this->message = $config['message'] ?? 'El sistema se encuentra en mantenimiento.';
    }

    public function toggle(): void
    {
        AppSetting::set('maintenance_mode', [
            'enabled' => $this->enabled,
            'message' => $this->message,
        ]);

        $status = $this->enabled ? 'activado' : 'desactivado';

        if ($this->enabled) {
            $dto = new NotificationDTO(
                title: 'Mantenimiento del Sistema',
                message: $this->message ?: 'El sistema entrará en mantenimiento en breve.',
                icon: 'wrench-screwdriver',
                level: 'warning'
            );

            // Notificar a todos los usuarios
            $users = User::all();
            Notification::send($users, new MaintenanceModeNotification($dto));
        }

        \Flux::toast(
            text: "Modo mantenimiento {$status} correctamente.",
            variant: $this->enabled ? 'warning' : 'success'
        );
    }

    public function render()
    {
        return view('core::livewire.system-maintenance');
    }
}
