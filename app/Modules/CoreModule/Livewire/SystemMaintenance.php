<?php

declare(strict_types=1);

namespace App\Modules\CoreModule\Livewire;

use App\Modules\CoreModule\Models\AppSetting;
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
        
        flux()->toast(
            text: "Modo mantenimiento {$status} correctamente.",
            variant: $this->enabled ? 'warning' : 'success'
        );
    }

    public function render()
    {
        return view('core::livewire.system-maintenance');
    }
}
