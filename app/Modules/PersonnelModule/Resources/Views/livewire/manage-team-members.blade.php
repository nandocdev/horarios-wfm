<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <flux:link href="{{ route('organization.teams.show', $team) }}" variant="ghost">
                        ← Volver al equipo
                    </flux:link>
                    <h1 class="text-2xl font-bold text-gray-900">Gestionar Miembros - {{ $team->name }}</h1>
                </div>
                <flux:button wire:click="openAssignModal" variant="primary" size="sm">
                    Agregar Miembro
                </flux:button>
            </div>
        </div>

        <div class="p-6">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Miembros Actuales ({{ $team->users->count() }})</h3>

                @if($team->users->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($team->users as $employee)
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
                                            <span class="text-white font-medium text-sm">
                                                {{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $employee->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $employee->email }}</div>
                                        <div class="text-xs text-gray-400">
                                            Código: {{ $employee->employee_number }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <flux:link href="{{ route('employees.show', $employee) }}" variant="ghost" size="sm">
                                        Ver
                                    </flux:link>
                                    <flux:button wire:click="openRemoveModal({{ $employee->id }})"
                                        variant="outline" size="sm" color="red">
                                        Remover
                                    </flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-400 mb-2">
                            <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <p class="text-gray-500">No hay miembros en este equipo.</p>
                        <p class="text-sm text-gray-400 mt-1">Haz clic en "Agregar Miembro" para asignar empleados.</p>
                    </div>
                @endif
            </div>

            <!-- Historial de asignaciones -->
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-4">Historial de Asignaciones</h3>

                @if($team->members->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Empleado
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Inicio
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Fin
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($team->members->sortByDesc('start_date') as $member)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="font-medium text-gray-900">{{ $member->employee->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $member->employee->email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $member->start_date->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $member->end_date?->format('d/m/Y') ?? 'Activo' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($member->is_active)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No hay historial de asignaciones.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal para asignar empleado -->
    <flux:modal wire:model="showAssignModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Asignar Empleado al Equipo</flux:heading>
                <flux:subheading>Selecciona un empleado y la fecha de inicio de la asignación.</flux:subheading>
            </div>

            <form wire:submit="assignEmployee" class="space-y-6">
                <flux:field>
                    <flux:label>Empleado</flux:label>
                    <flux:select wire:model="employee_id" placeholder="Selecciona un empleado">
                        @foreach($availableEmployees as $employee)
                                <flux:select.option value="{{ $employee->id }}">
                                {{ $employee->name }} ({{ $employee->employee_number }})
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="employee_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Inicio</flux:label>
                    <flux:input wire:model="start_date" type="date" />
                    <flux:error name="start_date" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de Fin (Opcional)</flux:label>
                    <flux:input wire:model="end_date" type="date" />
                    <flux:error name="end_date" />
                </flux:field>

                <div class="flex justify-end space-x-2">
                    <flux:button wire:click="closeAssignModal" variant="ghost">
                        Cancelar
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Asignar
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    <!-- Modal para remover empleado -->
    <flux:modal wire:model="showRemoveModal" class="md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remover Empleado del Equipo</flux:heading>
                <flux:subheading>Confirma la fecha de fin de la asignación.</flux:subheading>
            </div>

            @if($selectedEmployeeId)
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">
                        Empleado: <strong>{{ $team->users->find($selectedEmployeeId)?->name }}</strong>
                    </p>
                </div>
            @endif

            <form wire:submit="removeEmployee" class="space-y-6">
                <flux:field>
                    <flux:label>Fecha de Fin</flux:label>
                    <flux:input wire:model="remove_end_date" type="date" />
                    <flux:error name="remove_end_date" />
                </flux:field>

                <div class="flex justify-end space-x-2">
                    <flux:button wire:click="closeRemoveModal" variant="ghost">
                        Cancelar
                    </flux:button>
                    <flux:button type="submit" variant="destructive">
                        Remover
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
