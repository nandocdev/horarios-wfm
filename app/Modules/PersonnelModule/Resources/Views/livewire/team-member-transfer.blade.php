<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route('organization.teams.show', $team) }}" variant="ghost" icon="chevron-left" wire:navigate />
            <div>
                <flux:heading size="xl">Transferir Miembros - {{ $team->name }}</flux:heading>
                <flux:subheading>Gestiona la asignación de empleados entre equipos de forma visual.</flux:subheading>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-11 gap-4 items-center">
        <!-- Panel Izquierdo (Origen) -->
        <flux:card class="lg:col-span-5 h-[600px] flex flex-col p-0 overflow-hidden">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Origen</flux:heading>
                    <flux:badge color="zinc" size="sm" inset="top bottom">{{ count($this->leftEmployees) }}</flux:badge>
                </div>
                <flux:select wire:model.live="leftFilter">
                    <option value="all">Todos los empleados</option>
                    <option value="none">Sin equipo asignado</option>
                    @foreach($this->availableTeamsNames as $teamId => $teamName)
                        <option value="{{ $teamName }}">{{ $teamName }}</option>
                    @endforeach
                </flux:select>

                <flux:input 
                    wire:model.live.debounce.300ms="leftSearch" 
                    placeholder="Buscar por nombre o email..." 
                    icon="magnifying-glass" 
                    size="sm"
                    variant="filled"
                />
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach($this->leftEmployees as $employee)
                    <div 
                        wire:key="left-{{ $employee['id'] }}"
                        wire:click="toggleSelection('left', {{ $employee['id'] }})"
                        @class([
                            'flex items-center gap-3 p-3 cursor-pointer transition-colors',
                            'bg-blue-50/50 dark:bg-blue-900/20' => in_array($employee['id'], $this->leftSelected),
                            'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' => !in_array($employee['id'], $this->leftSelected),
                        ])
                    >
                        <flux:checkbox 
                            wire:model="leftSelected" 
                            value="{{ $employee['id'] }}" 
                            wire:click.stop 
                        />
                        
                        <flux:avatar 
                            src="{{ $employee['avatar_url'] ?? '' }}" 
                            initials="{{ $this->getInitials($employee['name']) }}" 
                            size="sm"
                        />
                        
                        <div class="flex-1 min-w-0">
                            <flux:text class="font-medium truncate">{{ $employee['name'] }}</flux:text>
                            <flux:text size="xs" class="truncate">
                                {{ $employee['team'] ?? 'Sin equipo' }}
                            </flux:text>
                        </div>

                        @if($employee['team'])
                            <flux:badge size="sm" color="zinc" variant="outline" class="hidden sm:flex">
                                {{ substr($employee['team'], 0, 3) }}
                            </flux:badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </flux:card>

        <!-- Controles Centrales -->
        <div class="lg:col-span-1 flex lg:flex-col justify-center gap-2 p-2">
            <flux:button 
                wire:click="moveSelectedToRight" 
                variant="ghost" 
                size="sm" 
                icon="chevron-right" 
                class="flex-1 lg:flex-none"
                :disabled="empty($this->leftSelected) || $this->rightFilter === 'all'"
            />
            <flux:button 
                wire:click="moveAllToRight" 
                variant="ghost" 
                size="sm" 
                icon="chevron-double-right" 
                class="flex-1 lg:flex-none"
                :disabled="empty($this->leftEmployees) || $this->rightFilter === 'all'"
            />
            <flux:button 
                wire:click="moveSelectedToLeft" 
                variant="ghost" 
                size="sm" 
                icon="chevron-left" 
                class="flex-1 lg:flex-none"
                :disabled="empty($this->rightSelected) || $this->leftFilter === 'all'"
            />
            <flux:button 
                wire:click="moveAllToLeft" 
                variant="ghost" 
                size="sm" 
                icon="chevron-double-left" 
                class="flex-1 lg:flex-none"
                :disabled="empty($this->rightEmployees) || $this->leftFilter === 'all'"
            />
        </div>

        <!-- Panel Derecho (Destino) -->
        <flux:card class="lg:col-span-5 h-[600px] flex flex-col p-0 overflow-hidden">
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 space-y-3">
                <div class="flex items-center justify-between">
                    <flux:heading size="sm">Destino</flux:heading>
                    <flux:badge color="zinc" size="sm" inset="top bottom">{{ count($this->rightEmployees) }}</flux:badge>
                </div>
                <flux:select wire:model.live="rightFilter">
                    <option value="all">Todos los empleados</option>
                    <option value="none">Sin equipo asignado</option>
                    @foreach($this->availableTeamsNames as $teamId => $teamName)
                        <option value="{{ $teamName }}">{{ $teamName }}</option>
                    @endforeach
                </flux:select>

                <flux:input 
                    wire:model.live.debounce.300ms="rightSearch" 
                    placeholder="Buscar por nombre o email..." 
                    icon="magnifying-glass" 
                    size="sm"
                    variant="filled"
                />
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-800">
                @foreach($this->rightEmployees as $employee)
                    <div 
                        wire:key="right-{{ $employee['id'] }}"
                        wire:click="toggleSelection('right', {{ $employee['id'] }})"
                        @class([
                            'flex items-center gap-3 p-3 cursor-pointer transition-colors',
                            'bg-blue-50/50 dark:bg-blue-900/20' => in_array($employee['id'], $this->rightSelected),
                            'hover:bg-zinc-50 dark:hover:bg-zinc-800/50' => !in_array($employee['id'], $this->rightSelected),
                        ])
                    >
                        <flux:checkbox 
                            wire:model="rightSelected" 
                            value="{{ $employee['id'] }}" 
                            wire:click.stop 
                        />
                        
                        <flux:avatar 
                            src="{{ $employee['avatar_url'] ?? '' }}" 
                            initials="{{ $this->getInitials($employee['name']) }}" 
                            size="sm"
                        />
                        
                        <div class="flex-1 min-w-0">
                            <flux:text class="font-medium truncate">{{ $employee['name'] }}</flux:text>
                            <flux:text size="xs" class="truncate">
                                {{ $employee['team'] ?? 'Sin equipo' }}
                            </flux:text>
                        </div>

                        @if($employee['team'])
                            <flux:badge size="sm" color="zinc" variant="outline" class="hidden sm:flex">
                                {{ substr($employee['team'], 0, 3) }}
                            </flux:badge>
                        @endif
                    </div>
                @endforeach
            </div>
        </flux:card>
    </div>
</div>
