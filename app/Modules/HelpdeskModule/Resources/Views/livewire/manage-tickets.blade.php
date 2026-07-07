<div wire:poll.5s class="space-y-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Bandeja de Soporte (Helpdesk)</flux:heading>
            <flux:subheading>Atención, priorización y resolución de tickets operativos y técnicos.</flux:subheading>
        </div>
        
        <flux:radio.group wire:model.live="statusFilter" variant="segmented">
            <flux:radio value="open_unassigned" label="Bandeja General" />
            <flux:radio value="my_assigned" label="Mis Tickets" />
            <flux:radio value="all_active" label="Todos Activos" />
            <flux:radio value="closed" label="Cerrados" />
        </flux:radio.group>
    </div>

    {{-- Filtros Rápidos --}}
    <flux:card class="flex flex-col md:flex-row items-end gap-4 p-4 bg-zinc-50/50 dark:bg-zinc-900/20">
        <flux:field class="flex-1 w-full">
            <flux:label>Buscar</flux:label>
            <flux:input wire:model.live.debounce.300ms="search" placeholder="ID, Asunto o Empleado..." icon="magnifying-glass" />
        </flux:field>

        <flux:field class="flex-1 w-full">
            <flux:label>Categoría</flux:label>
            <flux:select wire:model.live="categoryFilter" placeholder="Todas las categorías">
                <flux:select.option value="">Todas</flux:select.option>
                @foreach($categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>

        <flux:field class="flex-1 w-full">
            <flux:label>Prioridad</flux:label>
            <flux:select wire:model.live="priorityFilter" placeholder="Todas las prioridades">
                <flux:select.option value="">Todas</flux:select.option>
                <flux:select.option value="urgent">Urgente</flux:select.option>
                <flux:select.option value="high">Alta</flux:select.option>
                <flux:select.option value="medium">Media</flux:select.option>
                <flux:select.option value="low">Baja</flux:select.option>
            </flux:select>
        </flux:field>
    </flux:card>

    {{-- Lista de Tickets --}}
    <flux:card class="p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-zinc-50 dark:bg-zinc-900/50">
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">SLA / ID</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 w-1/3">Asunto y Descripción</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Categoría</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Solicitante</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-800">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-opacity {{ $ticket->priority === 'urgent' ? 'bg-red-50/20 dark:bg-red-900/5' : '' }}">
                        <td class="p-4 align-top">
                            <div class="flex flex-col gap-1">
                                <span class="font-bold text-sm text-zinc-900 dark:text-zinc-100">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-[10px] text-zinc-500" title="{{ $ticket->created_at }}">{{ $ticket->created_at->diffForHumans() }}</span>
                                
                                @if($ticket->priority === 'high' || $ticket->priority === 'urgent')
                                    <flux:badge size="sm" color="red" icon="fire" class="mt-1">{{ $ticket->priority_label }}</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc" class="mt-1">{{ $ticket->priority_label }}</flux:badge>
                                @endif
                            </div>
                        </td>
                        
                        <td class="p-4 align-top">
                            <div class="flex flex-col gap-1">
                                <span class="font-bold text-sm text-primary-700 dark:text-primary-400">{{ $ticket->subject }}</span>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $ticket->description }}</p>
                            </div>
                        </td>

                        <td class="p-4 align-top">
                            <flux:badge size="sm" :color="$ticket->category->color">{{ $ticket->category->name }}</flux:badge>
                            
                            @php
                                $statusColor = match($ticket->status) {
                                    'new' => 'zinc',
                                    'open' => 'blue',
                                    'in_progress' => 'amber',
                                    'on_hold' => 'purple',
                                    'resolved' => 'green',
                                    'closed' => 'zinc',
                                    default => 'zinc'
                                };
                            @endphp
                            <div class="mt-2">
                                <flux:badge size="sm" :color="$statusColor">{{ $ticket->status_label }}</flux:badge>
                            </div>
                        </td>

                        <td class="p-4 align-top">
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$ticket->creator->first_name . ' ' . $ticket->creator->last_name" size="sm" />
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-zinc-900 dark:text-white">{{ $ticket->creator->first_name }} {{ $ticket->creator->last_name }}</span>
                                    <span class="text-[10px] text-zinc-500">{{ $ticket->creator->position->name ?? 'Empleado' }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="p-4 text-right align-top">
                            @if(empty($ticket->assigned_agent_id) && !in_array($ticket->status, ['resolved', 'closed']))
                                <flux:button wire:click="assignToMe({{ $ticket->id }})" variant="primary" size="sm" icon="hand-raised">Tomar Ticket</flux:button>
                            @else
                                <div class="flex flex-col items-end gap-2">
                                    @if($ticket->assignedAgent)
                                        <div class="flex items-center gap-1 text-[10px] text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-2 py-1 rounded-md">
                                            <flux:icon icon="user-circle" variant="micro" class="size-3" />
                                            <span>{{ $ticket->assignedAgent->first_name }} (Soporte)</span>
                                        </div>
                                    @endif
                                    <flux:button href="{{ route('helpdesk.ticket.detail', $ticket->id) }}" wire:navigate variant="subtle" size="sm" icon="eye">Ver y Responder</flux:button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-20 text-center">
                            <div class="flex flex-col items-center justify-center opacity-50">
                                <flux:icon icon="inbox" size="lg" class="mb-4" />
                                <flux:heading>Bandeja vacía</flux:heading>
                                <flux:subheading>No hay tickets que coincidan con tus filtros actuales.</flux:subheading>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($tickets->hasPages())
            <div class="p-4 border-t border-zinc-100 dark:border-zinc-800">
                {{ $tickets->links() }}
            </div>
        @endif
    </flux:card>
</div>
