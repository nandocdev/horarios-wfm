@blaze(fold: true)

@php
  $classes = \Flux\Flux::classes()
      ->add('prose prose-sm max-w-none dark:prose-invert')
      ->add('min-h-[200px] max-h-[500px] overflow-y-auto')
      ->add('px-3 py-3 text-base sm:text-sm text-zinc-700 dark:text-zinc-300')
      ->add('[&_.ProseMirror]:outline-none [&_.ProseMirror]:min-h-[180px]');
@endphp

<div {{ $attributes->class($classes) }} data-flux-editor-content data-slot="content">
    {{ $slot }}
</div>
