<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Base de Conocimiento</flux:heading>
            <flux:subheading>Artículos de ayuda y documentación operativa.</flux:subheading>
        </div>
    </div>

    <div class="flex flex-col md:flex-row gap-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar artículos..."
            class="flex-1" icon="magnifying-glass" clearable />

        <flux:select wire:model.live="categoryId" placeholder="Todas las categorías" class="w-full md:w-64">
            @foreach($categories as $cat)
                <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($articles as $article)
            <flux:card class="hover:shadow-md transition-shadow">
                <flux:heading size="lg">{{ $article->title }}</flux:heading>
                @if($article->summary)
                    <flux:text size="sm" class="mt-2 text-zinc-500">{{ Str::limit($article->summary, 120) }}</flux:text>
                @endif
                <div class="flex flex-wrap gap-1 mt-3">
                    @foreach($article->tags as $tag)
                        <flux:badge size="xs" variant="ghost">{{ $tag->name }}</flux:badge>
                    @endforeach
                </div>
            </flux:card>
        @empty
            <div class="col-span-full text-center py-12">
                <flux:text class="text-zinc-400 italic">No se encontraron artículos.</flux:text>
            </div>
        @endforelse
    </div>
</div>
