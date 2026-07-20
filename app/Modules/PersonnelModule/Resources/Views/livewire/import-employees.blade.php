<div class="space-y-8">
    <x-wfm.page-header title="Importar Empleados" description="Validación por filas, importación por chunks y procesamiento en cola." />

    <flux:card class="space-y-4">
        <form wire:submit="import" class="space-y-4">
            <flux:field label="Archivo CSV *" hint="Archivo CSV con datos de empleados (máx. 20MB)">
                <flux:input type="file" wire:model="form.csv" accept=".csv,text/csv" />
                <flux:error name="form.csv" />
            </flux:field>

            <flux:input type="number" min="100" max="1000" wire:model="form.chunk_size" label="Tamaño de chunk" hint="Registros por lote de procesamiento (100-1000)" />

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="import">
                    <span wire:loading.remove wire:target="import">Importar CSV</span>
                    <span wire:loading wire:target="import">
                        <flux:icon.arrow-path class="w-4 h-4 motion-safe:animate-spin inline" />
                        Importando...
                    </span>
                </flux:button>
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
                        <flux:table.cell colspan="8">
                            <x-wfm.empty icon="document-arrow-up" message="No hay importaciones registradas" />
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
