<x-layouts::auth :title="__('Autenticación de dos pasos')">
    <div x-data="{ recovery: false }">
        <div class="flex flex-col gap-4">
            <x-auth-header 
                x-show="! recovery" 
                :title="__('Autenticación de dos pasos')" 
                :description="__('Confirma el acceso a tu cuenta ingresando el código de autenticación generado por tu aplicación móvil.')" 
            />

            <x-auth-header 
                x-cloak 
                x-show="recovery" 
                :title="__('Autenticación de dos pasos')" 
                :description="__('Confirma el acceso a tu cuenta ingresando uno de tus códigos de recuperación de emergencia.')" 
            />

            <form method="POST" action="{{ route('two-factor.login') }}" class="flex flex-col gap-4">
                @csrf

                <div x-show="! recovery">
                    <flux:input
                        name="code"
                        inputmode="numeric"
                        :label="__('Código de autenticación')"
                        autofocus
                        x-ref="code"
                        autocomplete="one-time-code"
                    />
                </div>

                <div x-cloak x-show="recovery">
                    <flux:input
                        name="recovery_code"
                        :label="__('Código de recuperación')"
                        x-ref="recovery_code"
                        autocomplete="one-time-code"
                    />
                </div>

                <div class="flex flex-col gap-4 items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">
                        {{ __('Iniciar sesión') }}
                    </flux:button>

                    <button type="button" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 underline cursor-pointer"
                                    x-show="! recovery"
                                    x-on:click="
                                        recovery = true;
                                        $nextTick(() => { $refs.recovery_code.focus() })
                                    ">
                        {{ __('Usar un código de recuperación') }}
                    </button>

                    <button type="button" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 underline cursor-pointer"
                                    x-cloak
                                    x-show="recovery"
                                    x-on:click="
                                        recovery = false;
                                        $nextTick(() => { $refs.code.focus() })
                                    ">
                        {{ __('Usar un código de autenticación') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::auth>
