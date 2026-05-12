<div class="space-y-6 max-w-5xl mx-auto">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Mis Tickets de Soporte</flux:heading>
            <flux:subheading>Gestiona tus solicitudes de asistencia técnica, administrativa u operativa.</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="openCreateModal">Nuevo Ticket</flux:button>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b bg-zinc-50 dark:bg-zinc-900/50">
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">ID / Fecha</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Asunto</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Estado</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500">Asignado a</th>
                    <th class="p-4 text-xs font-bold uppercase tracking-wider text-zinc-500 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-zinc-800">
                @forelse($tickets as $ticket)
                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                        <td class="p-4 text-sm font-medium">
                            <div class="flex flex-col">
                                <span class="font-bold text-primary-600">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-[10px] text-zinc-500">{{ $ticket->created_at->format('d M Y, H:i') }}</span>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="flex flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <flux:badge size="sm" :color="$ticket->category->color">{{ $ticket->category->name }}</flux:badge>
                                    @if($ticket->priority === 'high' || $ticket->priority === 'urgent')
                                        <flux:badge size="sm" color="red" icon="fire" class="font-bold">{{ $ticket->priority_label }}</flux:badge>
                                    @endif
                                </div>
                                <span class="font-medium text-sm text-zinc-900 dark:text-zinc-100">{{ $ticket->subject }}</span>
                            </div>
                        </td>
                        <td class="p-4">
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
                            <flux:badge size="sm" :color="$statusColor">{{ $ticket->status_label }}</flux:badge>
                        </td>
                        <td class="p-4 text-sm text-zinc-500">
                            @if($ticket->assignedAgent)
                                <div class="flex items-center gap-2">
                                    <flux:avatar size="xs" :name="$ticket->assignedAgent->first_name . ' ' . $ticket->assignedAgent->last_name" />
                                    <span>{{ $ticket->assignedAgent->first_name }}</span>
                                </div>
                            @else
                                <span class="italic opacity-50">Sin asignar</span>
                            @endif
                        </td>
                        <td class="p-4 text-right">
                            <flux:button href="{{ route('helpdesk.ticket.detail', $ticket->id) }}" wire:navigate variant="subtle" size="sm" icon="eye">Ver Detalles</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-12 text-center text-zinc-500 italic">
                            No tienes tickets abiertos. ¡Excelente!
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($tickets->hasPages())
            <div class="p-4 border-t dark:border-zinc-800">
                {{ $tickets->links() }}
            </div>
        @endif
    </flux:card>

    {{-- Modal de Creación --}}
    <flux:modal name="create-ticket-modal" wire:model="showCreateModal" class="md:w-[600px] space-y-6">
        <div>
            <flux:heading size="lg">Apertura de Ticket</flux:heading>
            <flux:subheading>Proporciona los detalles de tu consulta o incidencia.</flux:subheading>
        </div>

        <form wire:submit="submit" class="space-y-6">
            <flux:field>
                <flux:label>Asunto / Resumen</flux:label>
                <flux:input wire:model="subject" placeholder="Ej. Problema con el acceso a la VPN..." />
                <flux:error name="subject" />
            </flux:field>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>Categoría</flux:label>
                    <flux:select wire:model="categoryId" placeholder="Selecciona el área...">
                        @foreach($categories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="categoryId" />
                </flux:field>

                <flux:field>
                    <flux:label>Nivel de Impacto (Prioridad)</flux:label>
                    <flux:select wire:model="priority">
                        <flux:select.option value="low">Bajo (No me impide trabajar)</flux:select.option>
                        <flux:select.option value="medium">Medio (Me afecta parcialmente)</flux:select.option>
                        <flux:select.option value="high">Alto (Bloqueo crítico)</flux:select.option>
                        <flux:select.option value="urgent">Urgente (Sistema caído)</flux:select.option>
                    </flux:select>
                    <flux:error name="priority" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Descripción detallada</flux:label>
                <flux:textarea wire:model="description" rows="5" placeholder="Explica el problema paso a paso para que el equipo de soporte pueda ayudarte rápidamente..." />
                <flux:error name="description" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-4 border-t dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="subtle">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" icon="paper-airplane">Enviar Ticket</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
