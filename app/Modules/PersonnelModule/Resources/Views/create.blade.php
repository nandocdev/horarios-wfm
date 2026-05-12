@extends('layouts.app')

@section('title', 'Crear Empleado')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Crear Empleado</flux:heading>
                <flux:subheading>Agrega un nuevo empleado a la organización</flux:subheading>
            </div>

            <flux:button href="{{ route('employees.index') }}" variant="ghost" wire:navigate>
                ← Volver a empleados
            </flux:button>
        </div>

        <!-- Formulario de creación -->
        @livewire('employees.create-employee')
    </div>
@endsection
