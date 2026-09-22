@blaze(fold: true)

@props([
    'items' => null,
])

@php
  $classes = \Flux\Flux::classes()
      ->add('flex flex-wrap items-center gap-0.5 p-1.5 bg-zinc-50 dark:bg-zinc-900/40 border-b border-zinc-200 dark:border-white/10');
@endphp

<div {{ $attributes->class($classes) }} data-flux-editor-toolbar data-slot="toolbar">
    @if($items)
        @php $tokens = preg_split('/\s+/', trim($items)); @endphp
        @foreach($tokens as $token)
            @if($token === '|' || $token === 'separator')
                <flux:editor.separator />
            @elseif($token === '~' || $token === 'spacer')
                <flux:editor.spacer />
            @else
                @includeIf('flux.editor.'.$token)
            @endif
        @endforeach
    @else
        {{ $slot }}
    @endif
</div>
