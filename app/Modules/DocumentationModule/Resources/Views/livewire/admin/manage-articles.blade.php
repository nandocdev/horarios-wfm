<div class="space-y-6">
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
                <flux:table.column>Título</flux:table.column>
                <flux:table.column>Categorías</flux:table.column>
                <flux:table.column>Estado</flux:table.column>
                <flux:table.column>Vistas</flux:table.column>
                <flux:table.column align="end">Acciones</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($articles as $article)
                    <flux:table.row :key="$article->id">
                        <flux:table.cell class="max-w-xs truncate font-medium">{{ $article->title }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach($article->categories as $cat)
                                    <flux:badge :color="$cat->color ?? 'blue'" size="xs" variant="subtle">
                                        {{ $cat->name }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$article->is_published ? 'green' : 'slate'" size="sm" variant="subtle">
                                {{ $article->is_published ? 'Publicado' : 'Borrador' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $article->view_count }}</flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-2">
                                <flux:button wire:click="editArticle({{ $article->id }})" variant="ghost" icon="pencil-square" size="sm" />
                                <flux:button wire:click="deleteArticle({{ $article->id }})" 
                                    wire:confirm="¿Estás seguro de eliminar este artículo?"
                                    variant="ghost" color="red" icon="trash" size="sm" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" align="center" class="py-12">
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
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $editingArticle ? 'Editar Artículo' : 'Nuevo Artículo' }}</flux:heading>
                <flux:subheading>Completa los detalles del artículo de documentación.</flux:subheading>
            </div>

            <div class="space-y-4">
                <flux:input wire:model="title" label="Título" placeholder="Ej: Guía de uso de horarios" />
                
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Categorías</flux:label>
                        <div class="mt-2 space-y-2 max-h-40 overflow-y-auto p-3 border border-zinc-200 dark:border-zinc-700 rounded-md">
                            @foreach($categories as $category)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" wire:model="selectedCategories" value="{{ $category->id }}" class="rounded border-zinc-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $category->name }}</span>
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
