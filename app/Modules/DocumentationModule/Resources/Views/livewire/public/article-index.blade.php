<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <flux:heading size="2xl">Documentación de Usuario</flux:heading>
        <flux:subheading>Encuentra manuales, guías y respuestas a tus preguntas sobre el sistema.</flux:subheading>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        {{-- Sidebar de Filtros --}}
        <div class="md:col-span-1 space-y-6">
            <flux:card class="p-4">
                <div class="mb-4">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar..." icon="magnifying-glass" />
                </div>

                <div class="space-y-2">
                    <flux:navlist>
                        <flux:navlist.item 
                            wire:click="resetFilters" 
                            :current="!$category_id"
                            class="cursor-pointer"
                        >
                            Todas las categorías
                        </flux:navlist.item>

                        @foreach($categories as $category)
                            <flux:navlist.item 
                                wire:click="filterByCategory({{ $category->id }})" 
                                :current="$category_id == $category->id"
                                class="cursor-pointer"
                            >
                                {{ $category->name }}
                            </flux:navlist.item>
                        @endforeach
                    </flux:navlist>
                </div>
            </flux:card>
        </div>

        {{-- Lista de Artículos --}}
        <div class="md:col-span-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse($articles as $article)
                    <flux:card class="flex flex-col h-full hover:shadow-md transition-shadow">
                        <div class="flex-1">
                            <div class="flex gap-2 mb-2">
                                @foreach($article->categories as $cat)
                                    <flux:badge :color="$cat->color ?? 'blue'" size="xs" variant="subtle">
                                        {{ $cat->name }}
                                    </flux:badge>
                                @endforeach
                            </div>
                            
                            <flux:heading size="lg" class="mb-2 line-clamp-2">
                                <a href="{{ route('documentation.show', $article->slug) }}" wire:navigate class="hover:text-blue-600 transition-colors">
                                    {{ $article->title }}
                                </a>
                            </flux:heading>
                            
                            <div class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-3">
                                {{ Str::limit(strip_tags($article->content), 120) }}
                            </div>
                        </div>

                        <div class="mt-4 pt-4 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                            <flux:text size="xs" class="flex items-center gap-1">
                                <flux:icon name="eye" size="xs" class="size-3" />
                                {{ $article->view_count }}
                            </flux:text>
                            
                            <flux:button href="{{ route('documentation.show', $article->slug) }}" variant="ghost" size="sm" wire:navigate>
                                Leer más
                            </flux:button>
                        </div>
                    </flux:card>
                @empty
                    <div class="col-span-full py-12 flex flex-col items-center justify-center text-zinc-500">
                        <flux:icon name="document-magnifying-glass" size="xl" class="mb-4 opacity-20" />
                        <flux:text>No se encontraron artículos que coincidan con tu búsqueda.</flux:text>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $articles->links() }}
            </div>
        </div>
    </div>
</div>
