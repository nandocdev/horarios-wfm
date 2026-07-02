<?php

declare(strict_types=1);

namespace App\Src\Identity\Presentation\Livewire;

use App\Src\Identity\Infrastructure\Persistence\EloquentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Configuración de Perfil')]
class SettingsProfile extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $mobilePhone = '';
    public string $address = '';

    public function mount(): void
    {
        $user = Auth::user();
        $user->load('employee');

        $this->name = $user->name;
        $this->email = $user->email;

        if ($user->employee) {
            $this->phone = $user->employee->phone ?? '';
            $this->mobilePhone = $user->employee->mobile_phone ?? '';
            $this->address = $user->employee->address ?? '';
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
            'phone' => ['nullable', 'string', 'max:20'],
            'mobilePhone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $user = EloquentUser::find(Auth::id());
        $user->fill([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->employee) {
            $user->employee->update([
                'phone' => $this->phone,
                'mobile_phone' => $this->mobilePhone,
                'address' => $this->address,
            ]);
        }

        $this->dispatch('profile-updated', name: $user->name);
        toast('Perfil actualizado correctamente.');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }

    public function render()
    {
        return view('identity::livewire.settings-profile')
            ->layout('identity::settings.profile');
    }
}
