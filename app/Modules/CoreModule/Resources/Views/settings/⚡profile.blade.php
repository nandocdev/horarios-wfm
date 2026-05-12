<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Configuración de Perfil')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    // Datos de Empleado (si aplica)
    public bool $hasEmployee = false;
    public string $employee_number = '';
    public string $position_name = '';
    public string $department_name = '';
    public string $hire_date = '';
    
    // Datos editables de Empleado
    public string $phone = '';
    public string $mobile_phone = '';
    public string $address = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $user->load('employee.position', 'employee.department');

        $this->name = $user->name;
        $this->email = $user->email;

        if ($user->employee) {
            $this->hasEmployee = true;
            $this->employee_number = $user->employee->employee_number;
            $this->position_name = $user->employee->position?->name ?? 'N/A';
            $this->department_name = $user->employee->department?->name ?? 'N/A';
            $this->hire_date = $user->employee->hire_date?->format('d/m/Y') ?? 'N/A';
            
            $this->phone = $user->employee->phone ?? '';
            $this->mobile_phone = $user->employee->mobile_phone ?? '';
            $this->address = $user->employee->address ?? '';
        }
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate(array_merge(
            $this->profileRules($user->id),
            [
                'phone' => ['nullable', 'string', 'max:20'],
                'mobile_phone' => ['nullable', 'string', 'max:20'],
                'address' => ['nullable', 'string', 'max:500'],
            ]
        ));

        DB::transaction(function () use ($user, $validated) {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            if ($user->employee) {
                $user->employee->update([
                    'phone' => $validated['phone'],
                    'mobile_phone' => $validated['mobile_phone'],
                    'address' => $validated['address'],
                ]);
            }
        });

        $this->dispatch('profile-updated', name: $user->name);
        
        toast('Perfil actualizado correctamente.');
    }

    /**
     * Send an email verification notification to the current user.
     */
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

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="max-w-4xl py-6 focus:outline-none" tabindex="-1">
    @include('core::settings.partials.heading')

    <flux:heading class="sr-only">{{ __('Configuración de Perfil') }}</flux:heading>

    <x-core::settings.layout :heading="__('Mi Perfil de Funcionario')" :subheading="__('Gestiona tu información personal e institucional.')">
        <form wire:submit="updateProfileInformation" class="space-y-8 mt-6">
            
            <!-- Sección: Información de Cuenta (Acceso) -->
            <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 space-y-4">
                <flux:heading size="lg" icon="user-circle">{{ __('Datos de Acceso') }}</flux:heading>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="name" :label="__('Nombre Completo')" type="text" required autofocus autocomplete="name" />
                    <flux:input wire:model="email" :label="__('Correo Electrónico')" type="email" required autocomplete="email" />
                </div>

                @if ($this->hasUnverifiedEmail)
                    <div class="bg-amber-50 dark:bg-amber-900/20 p-3 rounded border border-amber-200 dark:border-amber-800">
                        <flux:text size="sm" class="text-amber-800 dark:text-amber-300">
                            {{ __('Tu dirección de correo no está verificada.') }}
                            <flux:link class="ml-2" wire:click.prevent="resendVerificationNotification">
                                {{ __('Re-enviar correo de verificación.') }}
                            </flux:link>
                        </flux:text>
                        @if (session('status') === 'verification-link-sent')
                            <flux:text size="xs" class="mt-1 text-green-600 dark:text-green-400 font-medium">
                                {{ __('Se ha enviado un nuevo enlace de verificación.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Sección: Información Institucional (Solo lectura) -->
            @if($hasEmployee)
                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 space-y-4">
                    <flux:heading size="lg" icon="briefcase">{{ __('Información Institucional') }}</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <flux:input :value="$employee_number" :label="__('No. Empleado')" read-only variant="filled" icon="identification" />
                        <flux:input :value="$position_name" :label="__('Cargo Actual')" read-only variant="filled" icon="briefcase" />
                        <flux:input :value="$department_name" :label="__('Departamento')" read-only variant="filled" icon="building-office" />
                    </div>
                    <div class="pt-2 border-t border-zinc-200 dark:border-zinc-800">
                        <flux:text size="sm" variant="subtle">
                            {{ __('Fecha de Ingreso:') }} <span class="font-medium text-zinc-900 dark:text-white">{{ $hire_date }}</span>
                        </flux:text>
                    </div>
                </div>

                <!-- Sección: Información de Contacto (Editable) -->
                <div class="bg-zinc-50 dark:bg-zinc-900 p-4 rounded-lg border border-zinc-200 dark:border-zinc-800 space-y-4">
                    <flux:heading size="lg" icon="phone">{{ __('Información de Contacto') }}</flux:heading>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <flux:input wire:model="phone" :label="__('Teléfono Fijo')" placeholder="Ej. 222-2222" icon="phone" />
                        <flux:input wire:model="mobile_phone" :label="__('Teléfono Móvil')" placeholder="Ej. 6666-6666" icon="device-phone-mobile" />
                    </div>
                    <flux:textarea wire:model="address" :label="__('Dirección Residencial')" placeholder="Escribe tu dirección detallada aquí..." rows="3" icon="map-pin" />
                </div>
            @else
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
                    <flux:text color="blue" size="sm">
                        {{ __('Esta cuenta de usuario aún no está vinculada a un registro de empleado institucional.') }}
                    </flux:text>
                </div>
            @endif

            <!-- Acciones -->
            <div class="flex items-center gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-800">
                <flux:button variant="primary" type="submit" icon="check">
                    {{ __('Guardar Cambios') }}
                </flux:button>

                <x-action-message on="profile-updated">
                    {{ __('Perfil actualizado.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <div class="mt-12 pt-12 border-t border-red-100 dark:border-red-900/30">
                <livewire:pages::settings.delete-user-form />
            </div>
        @endif
    </x-core::settings.layout>
</section>
