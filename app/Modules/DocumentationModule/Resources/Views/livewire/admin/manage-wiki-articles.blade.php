<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Gestión de Documentación</flux:heading>
            <flux:subheading>Administra los manuales y guías de usuario del sistema.</flux:subheading>
        </div>
        <flux:button wire:click="createArticle" variant="primary" icon="plus">
            Nuevo Artículo
        </flux:button>
    </div>

    <flux:card>
        <div class="mb-4">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar artículo..." icon="magnifying-glass" />
        </div>

        <flux:table :paginate="$articles">
            <flux:table.columns>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Título</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Categorías</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Estado</flux:table.column>
                <flux:table.column class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Vistas</flux:table.column>
                <flux:table.column align="end" class="sticky top-0 z-10 bg-slate-50 dark:bg-slate-900">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($articles as $article)
                    <flux:table.row :key="$article->id" class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 py-2">
                        <flux:table.cell class="py-2 max-w-xs truncate font-medium">{{ $article->title }}</flux:table.cell>
                        <flux:table.cell class="py-2">
                            <div class="flex flex-wrap gap-1">
                                @foreach($article->categories as $cat)
                                    <flux:badge :color="$cat->color ?? 'blue'" size="xs" variant="subtle">
                                        {{ $cat->name }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">
                            <flux:badge :color="$article->is_published ? 'green' : 'slate'" size="sm" variant="subtle">
                                {{ $article->is_published ? 'Publicado' : 'Borrador' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="py-2">{{ $article->view_count }}</flux:table.cell>
                        <flux:table.cell align="end" class="py-2">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="editArticle({{ $article->id }})" variant="ghost" icon="pencil-square" size="sm" />
                                <flux:button wire:click="deleteArticle({{ $article->id }})" 
                                    wire:confirm="¿Estás seguro de eliminar este artículo?"
                                    variant="ghost" color="red" icon="trash" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 py-2">
                        <flux:table.cell class="py-2" colspan="5" align="center" class="py-12">
                            <flux:text>No hay artículos registrados.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="mt-4">
            {{ $articles->links() }}
        </div>
    </flux:card>

    {{-- Modal de Edición/Creación --}}
    <flux:modal wire:model="showModal" class="md:w-[800px]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $editingArticle ? 'Editar Artículo' : 'Nuevo Artículo' }}</flux:heading>
                <flux:subheading>Completa los detalles del artículo de documentación.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="title" label="Título" placeholder="Ej: Guía de uso de horarios" />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Categorías</flux:label>
                        <div class="mt-2 space-y-2 max-h-40 overflow-y-auto p-3 border border-slate-200 dark:border-slate-700 rounded-md">
                            @foreach($categories as $category)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="selectedCategories" value="{{ $category->id }}" class="rounded border-slate-300 text-slate-950 shadow-sm focus:border-slate-400 focus:ring focus:ring-slate-200 focus:ring-opacity-50">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ $category->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </flux:field>

                    <div class="space-y-4">
                        <flux:input type="number" wire:model="sort_order" label="Orden de aparición" />
                        
                        <div class="flex items-center gap-2 pt-4">
                            <flux:switch wire:model="is_published" label="Publicar inmediatamente" />
                        </div>
                    </div>
                </div>

                <flux:field>
                    <flux:label>Contenido (HTML soportado)</flux:label>
                    <flux:textarea wire:model="content" rows="12" placeholder="Escribe el contenido del manual aquí..." />
                    <flux:error name="content" />
                </flux:field>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="$set('showModal', false)" variant="ghost">Cancelar</flux:button>
                <flux:button wire:click="save" variant="primary">Guardar Artículo</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
