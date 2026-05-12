@extends('layouts.app')

@section('title', 'Importar empleados')

@section('content')
    <div class="space-y-6">
        <div>
            <flux:heading size="xl">Importar empleados CSV</flux:heading>
            <flux:subheading>Proceso masivo chunked y queueable con reporte de rechazados.</flux:subheading>
        </div>

        @livewire('employees.import-employees')
    </div>
@endsection
