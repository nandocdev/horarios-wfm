<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <flux:button href="{{ route('documentation.index') }}" variant="ghost" icon="arrow-left" size="sm" wire:navigate>
                Volver a la lista
            </flux:button>
            
            <flux:separator vertical />
            
            <div class="flex gap-2">
                @foreach($article->categories as $cat)
                    <flux:badge :color="$cat->color ?? 'blue'" size="sm" variant="subtle">
                        {{ $cat->name }}
                    </flux:badge>
                @endforeach
            </div>
        </div>

        <flux:heading size="3xl" class="mb-2">{{ $article->title }}</flux:heading>
        
        <div class="flex items-center gap-4 text-sm text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1">
                <flux:icon name="user" size="xs" class="size-3" />
                {{ $article->author->name }}
            </span>
            <span>•</span>
            <span class="flex items-center gap-1">
                <flux:icon name="calendar" size="xs" class="size-3" />
                {{ $article->updated_at->format('d M, Y') }}
            </span>
            <span>•</span>
            <span class="flex items-center gap-1">
                <flux:icon name="eye" size="xs" class="size-3" />
                {{ $article->view_count }} vistas
            </span>
        </div>
    </div>

    <flux:card class="p-8 prose prose-zinc dark:prose-invert max-w-none">
        {!! $article->content !!}
    </flux:card>

    <div class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-700">
        <flux:heading size="lg" class="mb-4">¿Te resultó útil este artículo?</flux:heading>
        <div class="flex gap-4">
            <flux:button icon="hand-thumb-up" variant="outline">Sí, gracias</flux:button>
            <flux:button icon="hand-thumb-down" variant="outline">No del todo</flux:button>
        </div>
    </div>
</div>
