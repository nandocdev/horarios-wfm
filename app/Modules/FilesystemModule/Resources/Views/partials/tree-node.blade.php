@props(['folder', 'depth' => 0])

<div x-data="{ open: @js($currentFolderId == $folder->id || $folder->children->pluck('id')->contains($currentFolderId)) }">
    <div class="flex items-center group">
        <!-- Espaciado por profundidad -->
        @for($i = 0; $i < $depth; $i++)
            <div class="w-4 flex-none border-l border-zinc-200 dark:border-zinc-800 h-8 ml-2"></div>
        @endfor

        <div class="flex-1 flex items-center gap-1 py-1 pr-2 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800/50 transition-opacity cursor-pointer {{ $currentFolderId == $folder->id ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-zinc-600 dark:text-zinc-400' }}">
            @if($folder->children->isNotEmpty())
                <button @click.stop="open = !open" class="p-1 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded transition-transform" :class="open ? 'rotate-90' : ''">
                    <flux:icon name="chevron-right" variant="mini" class="w-3 h-3" />
                </button>
            @else
                <div class="w-5"></div>
            @endif

            <div class="flex items-center gap-2 flex-1 min-w-0" wire:click="navigateTo({{ $folder->id }})">
                <flux:icon name="folder" variant="mini" class="{{ $currentFolderId == $folder->id ? 'text-blue-500' : 'text-zinc-400' }}" />
                <span class="text-xs truncate font-medium">{{ $folder->name }}</span>
            </div>
        </div>
    </div>

    @if($folder->children->isNotEmpty())
        <div x-show="open" x-collapse>
            @foreach($folder->children as $child)
                @include('filesystem::partials.tree-node', ['folder' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
