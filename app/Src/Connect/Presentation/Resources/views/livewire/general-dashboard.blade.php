<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <flux:heading size="xl">Dashboard General</flux:heading>
            <flux:subheading>Monitoreo macro de la operación del Contact Center</flux:subheading>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">En Espera</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $metrics['total_waiting'] }}</flux:heading>
                </div>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                    <flux:icon.clock class="w-5 h-5" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">Agentes Conectados</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $metrics['total_logged'] }}</flux:heading>
                </div>
                <div class="p-2 bg-green-50 text-green-600 rounded-lg">
                    <flux:icon.user class="w-5 h-5" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">Disponibles</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $metrics['total_ready'] }}</flux:heading>
                </div>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <flux:icon.check-circle class="w-5 h-5" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">En Llamada</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $metrics['total_talking'] }}</flux:heading>
                </div>
                <div class="p-2 bg-blue-50 text-blue-600 rounded-lg">
                    <flux:icon.phone class="w-5 h-5" />
                </div>
            </div>
        </flux:card>

        <flux:card>
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500 font-medium text-sm">No Disponibles</flux:text>
                    <flux:heading size="xl" class="mt-2">{{ $metrics['total_not_ready'] }}</flux:heading>
                </div>
                <div class="p-2 bg-red-50 text-red-600 rounded-lg">
                    <flux:icon.phone-x-mark class="w-5 h-5" />
                </div>
            </div>
        </flux:card>
    </div>

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Estadísticas por CSQ</flux:heading>
            <flux:subheading>Métricas en tiempo real por cola de servicio</flux:subheading>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>CSQ</flux:table.column>
                <flux:table.column>Espera</flux:table.column>
                <flux:table.column>Mayor Espera</flux:table.column>
                <flux:table.column>Conectados</flux:table.column>
                <flux:table.column>Hablando</flux:table.column>
                <flux:table.column>Disponibles</flux:table.column>
                <flux:table.column>No Disp.</flux:table.column>
                <flux:table.column>ACW</flux:table.column>
                <flux:table.column>SLA Corto</flux:table.column>
                <flux:table.column>SLA Largo</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($csqStats as $stat)
                    <flux:table.row :key="$stat->id">
                        <flux:table.cell class="font-medium">{{ $stat->csq_name }}</flux:table.cell>
                        <flux:table.cell>{{ $stat->calls_waiting }}</flux:table.cell>
                        <flux:table.cell>{{ $stat->longest_call_in_queue }}s</flux:table.cell>
                        <flux:table.cell>{{ $stat->agents_logged_on }}</flux:table.cell>
                        <flux:table.cell>{{ $stat->agents_talking }}</flux:table.cell>
                        <flux:table.cell>{{ $stat->agents_ready }}</flux:table.cell>
                        <flux:table.cell>{{ $stat->agents_not_ready }}</flux:table.cell>
                        <flux:table.cell>{{ $stat->agents_after_call_work }}</flux:table.cell>
                        <flux:table.cell>{{ $stat->service_level_short_term }}%</flux:table.cell>
                        <flux:table.cell>{{ $stat->service_level_long_term }}%</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="10" class="text-center py-6 text-zinc-500">
                            No hay estadísticas disponibles.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
