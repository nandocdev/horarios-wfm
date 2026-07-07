<div class="space-y-8">
    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Carga de archivo CSV</flux:heading>
            <flux:subheading>Validación por filas, importación por chunks y procesamiento en cola.</flux:subheading>
        </div>

        <form wire:submit="import" class="space-y-4 max-w-4xl mx-auto">
            <!-- TODO: Refactor to FluxUI -->
            <div class="space-y-2">
                <label for="csv" class="text-sm font-medium text-slate-700">Archivo CSV</label>
                <input id="csv" type="file" wire:model="form.csv" accept=".csv,text/csv"
                    class="block w-full rounded-md border-slate-300" />
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
            <flux:table.columns class="sticky top-0 z-10 bg-white">
                <flux:table.column class="sticky top-0 z-10 bg-white">Lote</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-white">Archivo</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-white">Estado</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-white">Procesadas</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-white">Importadas</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-white">Rechazadas</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-white">Creado por</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-white">Fecha</flux:table.column>
            </flux:table.columns>

            <flux:table.rows class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                @forelse($importBatches as $batch)
                    <flux:table.row :key="$batch->id" class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                        <flux:table.cell class="py-2">{{ $batch->id }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $batch->original_filename }}</flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge :variant="match($batch->status) {
                                    'completed' => 'success',
                                    'completed_with_errors' => 'warning',
                                    'failed' => 'danger',
                                    default => 'ghost'
                                }">
                                {{ $batch->status }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">{{ $batch->processed_rows }}/{{ $batch->total_rows }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $batch->imported_rows }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $batch->rejected_rows }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $batch->creator?->name ?? 'Sistema' }}</flux:table.cell>
                        <flux:table.cell class="py-2">{{ $batch->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row class="hover:bg-slate-50/50 transition-colors duration-150 ease-out">
                        <flux:table.cell colspan="8" class="text-center py-8">No hay importaciones registradas.
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
