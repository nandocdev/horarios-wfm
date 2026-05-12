<div class="max-w-5xl mx-auto space-y-6">
    {{-- Header del Ticket --}}
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-2">
                <flux:badge size="sm" :color="$ticket->category->color">{{ $ticket->category->name }}</flux:badge>
                @php
                    $statusColor = match ($ticket->status) {
                        'new' => 'zinc', 'open' => 'blue', 'in_progress' => 'amber',
                        'on_hold' => 'purple', 'resolved' => 'green', 'closed' => 'zinc', default => 'zinc'
                    };
                @endphp
                <flux:badge size="sm" :color="$statusColor">{{ $ticket->status_label }}</flux:badge>
                @if($ticket->priority === 'high' || $ticket->priority === 'urgent')
                    <flux:badge size="sm" color="red" icon="fire">{{ $ticket->priority_label }}</flux:badge>
                @endif
                <span class="text-xs text-zinc-500 ml-2">#{{ str_pad($ticket->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
            <flux:heading size="xl">{{ $ticket->subject }}</flux:heading>
            <div class="flex items-center gap-2 mt-2 text-xs text-zinc-500">
                <span>Creado por <strong>{{ $ticket->creator->first_name }} {{ $ticket->creator->last_name }}</strong>
                    el {{ $ticket->created_at->format('d M, Y H:i') }}</span>
                <span>•</span>
                <span>Asignado a:
                    <strong>{{ $ticket->assignedAgent ? $ticket->assignedAgent->first_name . ' ' . $ticket->assignedAgent->last_name : 'Nadie' }}</strong></span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <flux:button href="{{ $isSupport ? route('helpdesk.manage') : route('helpdesk.my-tickets') }}" wire:navigate
                variant="subtle" icon="arrow-left">Volver a Bandeja</flux:button>

            @if($isSupport && empty($ticket->assigned_agent_id) && !in_array($ticket->status, ['resolved', 'closed']))
                <flux:button wire:click="takeTicket" variant="primary" icon="hand-raised">Tomar Ticket</flux:button>
            @endif

            @if($isSupport && !in_array($ticket->status, ['resolved', 'closed']))
                <flux:dropdown>
                    <flux:button variant="primary" icon-trailing="chevron-down">Cambiar Estado</flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="changeStatus('in_progress')">Marcar En Progreso</flux:menu.item>
                        <flux:menu.item wire:click="changeStatus('on_hold')">Poner En Espera</flux:menu.item>
                        <flux:menu.separator />
                        <flux:menu.item wire:click="changeStatus('resolved')" icon="check">Marcar Resuelto</flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            @endif

            @if(!$isSupport && !in_array($ticket->status, ['resolved', 'closed']))
                <flux:button wire:click="changeStatus('closed')"
                    wire:confirm="¿Deseas cerrar este ticket? Esto indica que tu problema ha sido solucionado."
                    variant="primary" icon="check">Cerrar Ticket</flux:button>
            @endif
        </div>
    </div>

    {{-- Hilo de Conversación --}}
    <div class="space-y-4">
        {{-- Mensaje Original --}}
        <flux:card class="bg-zinc-50 dark:bg-zinc-900/50 border-zinc-200 dark:border-zinc-800">
            <div class="flex items-start gap-4">
                <flux:avatar :name="$ticket->creator->first_name . ' ' . $ticket->creator->last_name" />
                <div class="flex-1 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-zinc-900 dark:text-white">{{ $ticket->creator->first_name }}
                            {{ $ticket->creator->last_name }}</span>
                        <span class="text-xs text-zinc-500"
                            title="{{ $ticket->created_at }}">{{ $ticket->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="prose dark:prose-invert max-w-none text-sm">
                        {{ nl2br(e($ticket->description)) }}
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Comentarios --}}
        @foreach($comments as $comment)
            @php
                $isAuthorMe = $comment->author_id === auth()->user()->employee?->id;
                $isSupportAgent = $comment->author_id === $ticket->assigned_agent_id;
            @endphp

            @if(!$comment->is_internal || ($comment->is_internal && $isSupport))
                <div class="flex items-start gap-4 {{ $isAuthorMe ? 'flex-row-reverse' : '' }}">
                    <flux:avatar :name="$comment->author->first_name . ' ' . $comment->author->last_name" size="sm"
                        class="{{ $comment->is_internal ? 'ring-2 ring-yellow-400' : '' }}" />

                    <div class="flex flex-col {{ $isAuthorMe ? 'items-end' : 'items-start' }} max-w-[80%]">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300">
                                {{ $comment->author->first_name }} {{ $comment->author->last_name }}
                                @if($isSupportAgent)
                                    <span class="text-primary-500 ml-1">(Soporte)</span>
                                @endif
                            </span>
                            <span class="text-[10px] text-zinc-500"
                                title="{{ $comment->created_at }}">{{ $comment->created_at->diffForHumans() }}</span>
                            @if($comment->is_internal)
                                <flux:badge size="sm" color="amber" icon="lock-closed">Nota Interna</flux:badge>
                            @endif
                        </div>

                        @php
                            $bubbleClasses = match (true) {
                                $comment->is_internal => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800/50 text-amber-900 dark:text-amber-200',
                                $isAuthorMe => 'bg-primary-600 text-white rounded-tr-none',
                                default => 'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-tl-none'
                            };
                        @endphp

                        <div class="p-3 rounded-lg text-sm shadow-sm text-wrap {{ $bubbleClasses }}">
                            <flux:text>{!! nl2br(e($comment->content)) !!}</flux:text>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Caja de Respuesta --}}
    @if(!in_array($ticket->status, ['resolved', 'closed']))
        <flux:card class="mt-6 border-zinc-200 dark:border-zinc-700">
            <form wire:submit="addComment" class="space-y-4">
                <flux:field>
                    <flux:label>Escribir una respuesta</flux:label>
                    <flux:textarea wire:model="newComment" rows="4"
                        placeholder="Describe los detalles o provee instrucciones..." />
                    <flux:error name="newComment" />
                </flux:field>

                <div class="flex items-center justify-between">
                    <div>
                        @if($isSupport)
                            <flux:checkbox wire:model="isInternalNote" label="Nota Interna (Sólo visible para WFM/Soporte)" />
                        @endif
                    </div>
                    <flux:button type="submit" variant="primary" icon="paper-airplane">Enviar Respuesta</flux:button>
                </div>
            </form>
        </flux:card>
    @else
        <flux:card class="mt-6 bg-zinc-50 dark:bg-zinc-900/30 text-center border-dashed">
            <flux:icon icon="lock-closed" class="mx-auto size-6 text-zinc-400 mb-2" />
            <flux:heading>Este ticket ha sido resuelto o cerrado.</flux:heading>
            <flux:subheading>Ya no es posible añadir más comentarios. Si el problema persiste, por favor abre un nuevo
                ticket haciendo referencia al #{{ $ticket->id }}.</flux:subheading>
        </flux:card>
    @endif
</div>