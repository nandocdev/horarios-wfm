@extends('layouts.app')

@section('title', 'Empleados')

@section('content')
    <div class="space-y-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl">Empleados</flux:heading>
                <flux:subheading>Gestiona la información de los empleados de la organización</flux:subheading>
            </div>

            <div class="flex items-center gap-2">
                @can('import', \App\Modules\PersonnelModule\Models\Employee::class)
                    <flux:button href="{{ route('employees.import') }}" variant="outline" wire:navigate>
                        Importar CSV
                    </flux:button>
                @endcan

                @can('create', \App\Modules\PersonnelModule\Models\Employee::class)
                    <flux:button href="{{ route('employees.create') }}" icon="plus" wire:navigate>
                        Nuevo Empleado
                    </flux:button>
                @endcan
            </div>
        </div>

        <!-- Lista de empleados -->
        @livewire('employees.list-employees')
    </div>
@endsection
