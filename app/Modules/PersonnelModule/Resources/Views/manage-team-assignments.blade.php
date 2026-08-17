<div class="space-y-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">Gestión de Equipos</flux:heading>
            <p class="text-slate-600">Asigna empleados a equipos de trabajo</p>
        </div>
        <x-wfm.tour-button :tour="'personnel.team-assignments'" />
    </div>

    <div class="bg-white rounded-md shadow">
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <flux:field>
                    <flux:label for="selectedTeamId">Seleccionar Equipo</flux:label>
                    <flux:select wire:model.live="selectedTeamId" id="selectedTeamId"
                        placeholder="-- Seleccionar equipo --">
                        @foreach($teams as $team)
                            <flux:select.option value="{{ $team->id }}">{{ $team->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('selectedTeamId')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                @if($selectedTeamId)
                    <flux:field>
                        <flux:label for="supervisor_id">Supervisor / Superior Directo (Team Lead)</flux:label>
                        <div class="flex gap-2">
                            <flux:select wire:model="supervisor_id" id="supervisor_id" placeholder="-- Seleccionar superior --" class="flex-1">
                                <flux:select.option value="">Sin supervisor</flux:select.option>
                                @foreach($this->supervisors as $supervisor)
                                    <flux:select.option value="{{ $supervisor->id }}">{{ $supervisor->full_name }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:button wire:click="updateSupervisor" variant="outline" icon="check" />
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Al asignar un supervisor, se actualizará el reporte directo de todos los miembros del equipo.</p>
                    </flux:field>
                @endif
            </div>

            @if($selectedTeamId)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Columna Izquierda: Empleados sin asignar -->
                    <div data-tour="team-assign-unassigned" class="space-y-4">
                        <h3 class="text-xl font-semibold text-slate-900">Empleados Disponibles</h3>
                        <div class="border rounded-md p-4 max-h-96 overflow-y-auto">
                            @if($unassignedEmployees->isEmpty())
                                <p class="text-slate-500 text-sm">No hay empleados disponibles</p>
                            @else
                                @foreach($unassignedEmployees as $employee)
                                    <flux:field>
                                        <flux:checkbox wire:model="selectedUnassigned" value="{{ $employee->id }}" />
                                        <flux:label>{{ $employee->full_name }} ({{ $employee->employee_number }})</flux:label>
                                    </flux:field>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Centro: Botones de acción -->
                    <div class="flex flex-col justify-center items-center space-y-4">
                        <flux:button wire:click="assignToTeam" variant="primary" icon="arrow-right"
                            :disabled="empty($selectedUnassigned)">
                            Asignar →
                        </flux:button>

                        <flux:button wire:click="unassignFromTeam" variant="danger" icon="arrow-left"
                            :disabled="empty($selectedAssigned)">
                            ← Desasignar
                        </flux:button>
                    </div>

                    <!-- Columna Derecha: Empleados asignados -->
                    <div data-tour="team-assign-boards" class="space-y-4">
                        <h3 class="text-xl font-semibold text-slate-900">Empleados en el Equipo</h3>
                        <div class="border rounded-md p-4 max-h-96 overflow-y-auto">
                            @if($assignedEmployees->isEmpty())
                                <p class="text-slate-500 text-sm">No hay empleados asignados</p>
                            @else
                                @foreach($assignedEmployees as $employee)
                                    <flux:field>
                                        <flux:checkbox wire:model="selectedAssigned" value="{{ $employee->id }}" />
                                        <flux:label>{{ $employee->full_name }} ({{ $employee->employee_number }})</flux:label>
                                    </flux:field>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-slate-500">Selecciona un equipo para gestionar sus miembros</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Mensajes de éxito/error -->
    @if(session()->has('success'))
        <div class="bg-green-50 border border-green-200 rounded-md p-4">
            <div class="text-green-800">{{ session('success') }}</div>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-md p-4">
            <flux:error>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:error>
        </div>
    @endif
</div>
