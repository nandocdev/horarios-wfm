<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Livewire;

use App\Src\Identity\Domain\ValueObjects\Password;
use App\Src\Identity\Infrastructure\Services\BcryptPasswordHasher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Configuración de Seguridad')]
class SettingsSecurity extends Component
{
    public string $currentPassword = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    public bool $canManageTwoFactor = false;
    public bool $twoFactorEnabled = false;

    public function mount(): void
    {
        $this->canManageTwoFactor = \Laravel\Fortify\Features::canManageTwoFactorAuthentication();

        if ($this->canManageTwoFactor) {
            $this->twoFactorEnabled = auth()->user()->hasEnabledTwoFactorAuthentication();
        }
    }

    protected function rules(): array
    {
        return [
            'currentPassword' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed:passwordConfirmation'],
        ];
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed:passwordConfirmation'],
        ];

        if (! $user->force_password_change) {
            $rules['currentPassword'] = ['required', 'string', 'current_password'];
        }

        try {
            $validated = $this->validate($rules);
        } catch (ValidationException $e) {
            $this->reset('currentPassword', 'password', 'passwordConfirmation');
            throw $e;
        }

        $hasher = app(BcryptPasswordHasher::class);

        Password::fromPlainText($validated['password'], $hasher);

        $user->forceFill([
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'force_password_change' => false,
        ])->save();

        $this->reset('currentPassword', 'password', 'passwordConfirmation');

        toast('Contraseña actualizada correctamente.');
        $this->redirectRoute('identity.settings.security', navigate: true);
    }

    public function render()
    {
        return view('identity::livewire.settings-security');
    }
}
