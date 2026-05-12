<div class="space-y-6">
    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Carga de archivo CSV</flux:heading>
            <flux:subheading>Validación por filas, importación por chunks y procesamiento en cola.</flux:subheading>
        </div>

        <form wire:submit="import" class="space-y-4">
            <!-- TODO: Refactor to FluxUI -->
            <div class="space-y-2">
                <label for="csv" class="text-sm font-medium text-zinc-700">Archivo CSV</label>
                <input id="csv" type="file" wire:model="form.csv" accept=".csv,text/csv"
                    class="block w-full rounded-md border-zinc-300" />
                @error('form.csv')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <flux:input type="number" min="100" max="1000" wire:model="form.chunk_size" label="Tamaño de chunk" />
            @error('form.chunk_size')
                <p class="text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary">Importar CSV</flux:button>
            </div>
        </form>
    </flux:card>

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Historial de importaciones</flux:heading>
        </div>

        <flux:table :paginate="$importBatches">
            <flux:table.columns>
                <flux:table.column>Lote</flux:table.column>
                <flux:table.column>Archivo</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Procesadas</flux:table.column>
                <flux:table.column>Importadas</flux:table.column>
                <flux:table.column>Rechazadas</flux:table.column>
                <flux:table.column>Creado por</flux:table.column>
                <flux:table.column>Fecha</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($importBatches as $batch)
                    <flux:table.row :key="$batch->id">
                        <flux:table.cell>{{ $batch->id }}</flux:table.cell>
                        <flux:table.cell>{{ $batch->original_filename }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :variant="match($batch->status) {
                                    'completed' => 'success',
                                    'completed_with_errors' => 'warning',
                                    'failed' => 'danger',
                                    default => 'ghost'
                                }">
                                {{ $batch->status }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $batch->processed_rows }}/{{ $batch->total_rows }}</flux:table.cell>
                        <flux:table.cell>{{ $batch->imported_rows }}</flux:table.cell>
                        <flux:table.cell>{{ $batch->rejected_rows }}</flux:table.cell>
                        <flux:table.cell>{{ $batch->creator?->name ?? 'Sistema' }}</flux:table.cell>
                        <flux:table.cell>{{ $batch->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="8" class="text-center py-8">No hay importaciones registradas.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
