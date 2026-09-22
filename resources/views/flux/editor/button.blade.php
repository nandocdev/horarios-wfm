@blaze(fold: true, unsafe: ['icon:variant'])

@props([
    'icon' => null,
    'iconVariant' => 'micro',
    'tooltip' => null,
    'disabled' => false,
    'active' => false,
])

@php
  $classes = \Flux\Flux::classes()
      ->add('inline-flex items-center justify-center size-7 rounded-md')
      ->add('text-zinc-600 dark:text-zinc-400 hover:bg-zinc-200/70 dark:hover:bg-white/10')
      ->add('data-[active]:bg-zinc-900 data-[active]:text-white dark:data-[active]:bg-white dark:data-[active]:text-zinc-900')
      ->add('disabled:opacity-50 disabled:pointer-events-none')
      ->add('transition-colors');
@endphp

@if($tooltip)
<flux:tooltip :content="$tooltip" class="contents">
    <button type="button" {{ $attributes->class($classes) }} data-flux-editor-button :disabled="$disabled" @if($active) data-active @endif>
        @if($icon)
            <flux:icon :icon="$icon" :variant="$iconVariant" class="size-4" />
        @endif
        {{ $slot }}
    </button>
</flux:tooltip>
@else
<button type="button" {{ $attributes->class($classes) }} data-flux-editor-button :disabled="$disabled" @if($active) data-active @endif>
    @if($icon)
        <flux:icon :icon="$icon" :variant="$iconVariant" class="size-4" />
    @endif
    {{ $slot }}
</button>
@endif
