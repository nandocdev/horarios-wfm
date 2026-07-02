<div class="space-y-6">
    <flux:heading size="xl">Legajo Médico: {{ $employee->full_name }}</flux:heading>
    <flux:subheading>Registro de enfermedades, discapacidades y dependientes.</flux:subheading>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <flux:card>
            <flux:heading size="lg">Enfermedades</flux:heading>
            <flux:subheading>Registro de condiciones médicas del empleado.</flux:subheading>

            <form wire:submit="addDisease" class="mt-4 space-y-4">
                <flux:select wire:model="diseaseTypeId" :label="__('Tipo de Enfermedad')" required>
                    @foreach($diseaseTypes as $type)
                        <flux:select.option value="{{ $type->id }}">{{ $type->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="diseaseNotes" :label="__('Notas Médicas')" rows="3" />

                <flux:button type="submit" variant="primary" class="w-full">Registrar</flux:button>
            </form>

            <div class="mt-6 space-y-2">
                @forelse($employee->diseases as $disease)
                    <div class="p-3 bg-zinc-50 dark:bg-white/5 rounded-lg">
                        <flux:text weight="semibold">{{ $disease->diseaseType?->name ?? 'Tipo #'.$disease->disease_type_id }}</flux:text>
                        @if($disease->notes)
                            <flux:text size="sm" class="block mt-1">{{ $disease->notes }}</flux:text>
                        @endif
                    </div>
                @empty
                    <flux:text class="text-zinc-400 italic">Sin registros médicos.</flux:text>
                @endforelse
            </div>
        </flux:card>

        <flux:card>
            <flux:heading size="lg">Dependientes</flux:heading>
            <flux:subheading>Familiares a cargo del empleado.</flux:subheading>

            <form wire:submit="addDependent" class="mt-4 space-y-4">
                <flux:input wire:model="dependentName" :label="__('Nombre Completo')" required />
                <flux:input wire:model="dependentRelationship" :label="__('Parentesco')" placeholder="Ej. Hijo(a), Cónyuge" required />
                <flux:input wire:model="dependentBirthDate" type="date" :label="__('Fecha de Nacimiento')" />

                <flux:button type="submit" variant="primary" class="w-full">Registrar</flux:button>
            </form>

            <div class="mt-6 space-y-2">
                @forelse($employee->dependents as $dependent)
                    <div class="p-3 bg-zinc-50 dark:bg-white/5 rounded-lg">
                        <flux:text weight="semibold">{{ $dependent->name }}</flux:text>
                        <flux:text size="sm" class="block text-zinc-500">{{ $dependent->relationship }}</flux:text>
                        @if($dependent->birth_date)
                            <flux:text size="sm" class="text-zinc-400">{{ $dependent->birth_date->format('d/m/Y') }}</flux:text>
                        @endif
                    </div>
                @empty
                    <flux:text class="text-zinc-400 italic">Sin dependientes registrados.</flux:text>
                @endforelse
            </div>
        </flux:card>
    </div>
</div>
