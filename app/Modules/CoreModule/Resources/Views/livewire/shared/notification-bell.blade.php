<div wire:poll.30s>
    <flux:dropdown position="bottom" align="start">
        {{-- Trigger para Desktop (Sidebar) --}}
        <flux:sidebar.item icon="bell" class="hidden lg:flex relative cursor-pointer">
            Notificaciones
            @if($this->unreadCount > 0)
                <flux:badge size="sm" color="red" class="ml-auto">{{ $this->unreadCount }}</flux:badge>
            @endif
        </flux:sidebar.item>

        {{-- Trigger para Mobile (Header) --}}
        <flux:button variant="ghost" icon="bell" class="lg:hidden relative">
            @if($this->unreadCount > 0)
                <span class="absolute top-0 right-0 flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                </span>
            @endif
        </flux:button>

        <flux:menu class="w-80">
            <div class="px-4 py-2 flex justify-between items-center border-b border-zinc-200 dark:border-zinc-700">
                <flux:heading size="sm">Notificaciones</flux:heading>
                @if($this->unreadCount > 0)
                    <flux:button variant="ghost" size="sm" wire:click="markAllAsRead">
                        Marcar todo como leído
                    </flux:button>
                @endif
            </div>

            @forelse($this->notifications as $notification)
                <div class="px-4 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-opacity border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                    <div class="flex justify-between items-start gap-2">
                        <div class="flex-1">
                            <flux:text size="sm" weight="semibold">{{ $notification->data['title'] ?? 'Notificación' }}</flux:text>
                            <flux:text size="xs" class="block mt-1">{{ $notification->data['message'] ?? '' }}</flux:text>
                            <flux:text size="xs" variant="subtle" class="mt-2 block">
                                {{ $notification->created_at->diffForHumans() }}
                            </flux:text>
                        </div>
                        <flux:button variant="ghost" size="sm" icon="check" wire:click="markAsRead('{{ $notification->id }}')" />
                    </div>
                    @if(isset($notification->data['action_url']))
                        <flux:button variant="link" size="sm" href="{{ $notification->data['action_url'] }}" class="mt-2 p-0 h-auto" wire:navigate>
                            Ver detalle
                        </flux:button>
                    @endif
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <flux:icon icon="bell-slash" class="mx-auto h-8 w-8 text-zinc-300 dark:text-zinc-600" />
                    <flux:text variant="subtle" class="mt-2">No tienes notificaciones pendientes</flux:text>
                </div>
            @endforelse

            <flux:menu.separator />
            {{-- Enlace a historial completo si existiera --}}
            <div class="p-2">
                <flux:button variant="ghost" class="w-full text-center" disabled>
                    Ver todas las notificaciones
                </flux:button>
            </div>
        </flux:menu>
    </flux:dropdown>
</div>
