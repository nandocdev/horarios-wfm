<div class="max-w-4xl mx-auto space-y-8 flex-1 flex flex-col">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Historial de Notificaciones</flux:heading>
            <flux:subheading>Todas tus notificaciones, ordenadas de más reciente a más antigua.</flux:subheading>
        </div>
        @php($unreadCount = Auth::user()->unreadNotifications->count())
        @if($unreadCount > 0)
            <flux:button wire:click="markAllAsRead" variant="primary" size="sm">
                Marcar todo como leído
            </flux:button>
        @endif
    </div>

    <flux:card class="p-0 overflow-hidden">
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($notifications as $notification)
                <div class="p-4 flex items-start gap-4 {{ $notification->read_at ? '' : 'bg-blue-50/50 dark:bg-blue-900/10' }}">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-sm {{ $notification->read_at ? 'text-slate-700 dark:text-slate-300' : 'text-slate-900 dark:text-white' }}">
                                {{ $notification->data['title'] ?? 'Notificación' }}
                            </span>
                            @unless($notification->read_at)
                                <span class="w-2 h-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                            @endunless
                        </div>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            {{ $notification->data['message'] ?? '' }}
                        </p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                            @if(isset($notification->data['action_url']))
                                <flux:button variant="link" size="sm" href="{{ $notification->data['action_url'] }}" wire:navigate class="p-0 h-auto text-xs">
                                    Ver detalle
                                </flux:button>
                            @endif
                        </div>
                    </div>
                    @unless($notification->read_at)
                        <flux:button wire:click="markAsRead('{{ $notification->id }}')" variant="ghost" size="sm" icon="check" class="flex-shrink-0" />
                    @endunless
                </div>
            @empty
                <div class="p-12 text-center">
                    <flux:icon icon="bell-slash" class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600" />
                    <flux:heading class="mt-4">No hay notificaciones</flux:heading>
                    <flux:subheading>No tienes notificaciones en tu historial.</flux:subheading>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $notifications->links() }}
            </div>
        @endif
    </flux:card>
</div>
