@extends('layouts.app')

@section('title', 'Editar Empleado')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Editar Empleado</flux:heading>
                <flux:subheading>Modifica la información de {{ $employee->full_name }}</flux:subheading>
            </div>

            <div class="flex items-center gap-3">
                <flux:button href="{{ route('employees.show', $employee) }}" variant="ghost" wire:navigate>
                    Ver detalles
                </flux:button>

                <flux:button href="{{ route('employees.index') }}" variant="ghost" wire:navigate>
                    ← Volver a empleados
                </flux:button>
            </div>
        </div>

        <!-- Formulario de edición -->
        @livewire('employees.edit-employee', ['employee' => $employee])
    </div>
@endsection
