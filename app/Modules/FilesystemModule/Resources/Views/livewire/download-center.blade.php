<div class="flex w-full flex-col">
    <!-- Hero Section -->
    <section class="relative overflow-hidden border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
        <div class="absolute inset-x-0 top-0 h-64 bg-slate-100 dark:bg-slate-900"></div>

        <div class="relative mx-auto max-w-[85rem] px-4 py-8 sm:px-4 lg:px-8 lg:py-16">
            <div class="space-y-4 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-black uppercase tracking-widest">
                    <flux:icon name="folder-arrow-down" class="w-4 h-4" />
                    Recursos del Sistema
                </div>
                <flux:heading size="xl" class="text-3xl font-bold leading-tight">
                    Centro de <span class="text-slate-900 dark:text-white">Descargas</span>
                </flux:heading>
                <flux:text class="text-sm max-w-2xl mx-auto text-slate-600 dark:text-slate-400">
                    Accede a todos los documentos, manuales y archivos compartidos de forma pública.
                </flux:text>
            </div>

            <div class="mt-8 max-w-xl mx-auto">
                <flux:input wire:model.live="search" icon="magnifying-glass" placeholder="Buscar archivos..." class="shadow-md" />
            </div>
        </div>
    </section>

    <!-- Files List -->
    <div class="mx-auto w-full max-w-[85rem] px-4 py-12 sm:px-4 lg:px-8">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @forelse ($files as $file)
                <flux:card class="group flex flex-col p-4 transition-opacity duration-150 hover:shadow-md hover:-translate-y-1 border-none bg-white dark:bg-slate-800/50">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 rounded-md bg-slate-100 dark:bg-slate-700 text-slate-500 group-hover:bg-blue-500 group-hover:text-white transition-opacity">
                            @php
                                $icon = match($file->extension) {
                                    'pdf' => 'document-text',
                                    'xls', 'xlsx', 'csv' => 'table-cells',
                                    'doc', 'docx' => 'document-text',
                                    'zip', 'rar' => 'archive-box',
                                    'jpg', 'png', 'svg' => 'photo',
                                    default => 'document',
                                };
                            @endphp
                            <flux:icon name="{{ $icon }}" class="w-8 h-8" />
                        </div>
                        <flux:badge color="blue" size="sm" class="uppercase tracking-tighter">{{ $file->extension }}</flux:badge>
                    </div>

                    <div class="flex-1 space-y-1">
                        <flux:heading size="sm" class="line-clamp-1 font-bold">{{ $file->name }}</flux:heading>
                        <flux:text class="text-xs text-slate-500">{{ $file->formatted_size }}</flux:text>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-3">
                        <flux:button wire:click="download({{ $file->id }})" variant="primary" icon="arrow-down-tray" size="sm" class="flex-1">
                            Descargar
                        </flux:button>
                    </div>
                </flux:card>
            @empty
                <div class="col-span-full py-20 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-md">
                    <flux:icon name="document-minus" class="w-12 h-12 mx-auto text-slate-300 mb-4" />
                    <flux:heading size="md">No se encontraron archivos</flux:heading>
                    <flux:text class="mt-1">Intenta con otro término de búsqueda o contacta al administrador.</flux:text>
                </div>
            @endforelse
        </div>
    </div>
</div>
