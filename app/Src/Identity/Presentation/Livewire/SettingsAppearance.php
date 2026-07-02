<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Livewire;

use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Configuración de Apariencia')]
class SettingsAppearance extends Component
{
    public function render()
    {
        return view('identity::livewire.settings-appearance');
    }
}
