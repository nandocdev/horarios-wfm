@blaze(fold: true, safe: ['toolbar'])

@props([
    'label' => null,
    'description' => null,
    'badge' => null,
    'placeholder' => null,
    'toolbar' => null, // string | bool
    'disabled' => false,
    'invalid' => null,
    'value' => null,
    'rows' => null, // compat: ignored, height via class **:data-[slot=content]:...
])

@php
    // Resolve wire:model name for error bag & Livewire entangle
    $wireModel = $attributes->whereStartsWith('wire:model')->first();
    $wireDirective = $attributes->whereStartsWith('wire:model')->first() ? array_key_first(array_filter($attributes->getAttributes(), fn($v,$k) => str_starts_with($k, 'wire:model'), ARRAY_FILTER_USE_BOTH)) : null;
    // Get actual wire:model attribute name (e.g. wire:model, wire:model.live)
    $wireAttr = collect($attributes->getAttributes())->keys()->first(fn($k) => str_starts_with($k, 'wire:model'));
    $wireModelValue = $wireAttr ? $attributes->get($wireAttr) : null;
    $name = $wireModelValue ?? $attributes->get('name');

    // Resolve value: explicit prop overrides slot
    $initialValue = $value ?? $slot->toHtml();

    // Toolbar normalization
    // null => default, false => hidden, string => parse, true => default
    $defaultToolbar = 'heading | bold italic underline strike | bullet ordered blockquote | link | align ~ undo redo';
    if ($toolbar === null || $toolbar === true) {
        $toolbarString = $defaultToolbar;
    } elseif ($toolbar === false) {
        $toolbarString = null;
    } else {
        $toolbarString = (string) $toolbar;
    }

    $hasCustomSlot = !$slot->isEmpty() && str_contains($slot->toHtml(), 'data-slot="toolbar"') || str_contains($slot->toHtml(), 'data-slot="content"') || str_contains($slot->toHtml(), 'data-flux-editor-toolbar') || str_contains($slot->toHtml(), 'data-flux-editor-content');
    // Fallback: if slot is non-empty and not containing our markers, treat as custom composed content (Blaze slot)
    $isComposed = !$slot->isEmpty() && $hasCustomSlot;

    $invalidProp = $invalid !== null ? (bool) $invalid : false;

    $outerClasses = \Flux\Flux::classes()
        ->add('group/editor block w-full')
        ->add($attributes->pluck('class'));

    $editorWrapClasses = \Flux\Flux::classes()
        ->add('rounded-lg border bg-white dark:bg-white/10 overflow-hidden')
        ->add('border-zinc-200 border-b-zinc-300/80 dark:border-white/10')
        ->add('has-[[data-invalid]]:border-red-500 dark:has-[[data-invalid]]:border-red-500')
        ->add($disabled ? 'opacity-60 pointer-events-none' : '');

    $contentClasses = \Flux\Flux::classes()
        ->add('prose prose-sm max-w-none dark:prose-invert')
        ->add('min-h-[200px] max-h-[500px] overflow-y-auto')
        ->add('px-3 py-3 text-base sm:text-sm text-zinc-700 dark:text-zinc-300')
        ->add('[&_.ProseMirror]:outline-none [&_.ProseMirror]:min-h-[180px]')
        ->add('[&_.ProseMirror_p.is-editor-empty:first-child::before]:content-[attr(data-placeholder)] [&_.ProseMirror_p.is-editor-empty:first-child::before]:float-left [&_.ProseMirror_p.is-editor-empty:first-child::before]:text-zinc-400 [&_.ProseMirror_p.is-editor-empty:first-child::before]:pointer-events-none [&_.ProseMirror_p.is-editor-empty:first-child::before]:h-0')
        ->add('**:data-[slot=content]:min-h-[200px]');

    $toolbarTokens = $toolbarString ? preg_split('/\s+/', trim($toolbarString)) : [];
@endphp

<flux:with-field :$attributes>
    @if($label)
        <flux:label :$badge>{{ $label }}</flux:label>
    @endif

    @if($description && ! $attributes->has('description:trailing'))
        <flux:description>{{ $description }}</flux:description>
    @endif

    @php $outerClasses->add('relative'); @endphp
    <div
        x-data="fluxEditor({
            placeholder: @js($placeholder ?? __('Rich text editor')),
            disabled: @js((bool) $disabled),
            content: @js($initialValue)
        })"
        {{ $attributes->except(['class', 'wire:model', 'wire:model.live', 'wire:model.defer', 'wire:model.blur', 'wire:model.fill', 'wire:model.lazy', 'wire:model.live.debounce.300ms', 'wire:model.live.debounce.500ms', 'rows', 'name', 'value', 'label', 'description', 'badge', 'placeholder', 'toolbar', 'disabled', 'invalid'])->class($outerClasses) }}
        data-flux-editor
        @if($wireModelValue) data-wire-model="{{ $wireModelValue }}" @endif
        data-flux-control
        @unblaze(scope: ['name' => $name, 'invalid' => $invalidProp])
        <?php if ($scope['invalid'] || ($scope['name'] && $errors->has($scope['name']))) { ?>
        data-invalid aria-invalid="true"
        <?php } ?>
        @endunblaze
    >
        {{-- Hidden textarea for Livewire entangle --}}
        <textarea
            x-ref="input"
            data-flux-editor-input
            class="hidden"
            @if($name) name="{{ $name }}" @endif
            @if($wireAttr) {{ $wireAttr }}="{{ $wireModelValue }}" @endif
            aria-hidden="true"
            tabindex="-1"
        >{!! $initialValue !!}</textarea>

        <div wire:ignore class="{{ $editorWrapClasses }}">
            @if($isComposed)
                {{-- Custom composed mode: render user slot that should contain toolbar/content subcomponents --}}
                {{ $slot }}
            @else
                @if($toolbarString !== null)
                    <div data-flux-editor-toolbar data-slot="toolbar" class="flex flex-wrap items-center gap-0.5 p-1.5 bg-zinc-50 dark:bg-zinc-900/40 border-b border-zinc-200 dark:border-white/10">
                        @foreach($toolbarTokens as $token)
                            @if($token === '|' || $token === 'separator')
                                <flux:editor.separator />
                            @elseif($token === '~' || $token === 'spacer')
                                <flux:editor.spacer />
                            @elseif($token === 'heading')
                                <flux:editor.heading />
                            @elseif($token === 'bold')
                                <flux:editor.bold />
                            @elseif($token === 'italic')
                                <flux:editor.italic />
                            @elseif($token === 'strike')
                                <flux:editor.strike />
                            @elseif($token === 'underline')
                                <flux:editor.underline />
                            @elseif($token === 'bullet')
                                <flux:editor.bullet />
                            @elseif($token === 'ordered')
                                <flux:editor.ordered />
                            @elseif($token === 'blockquote')
                                <flux:editor.blockquote />
                            @elseif($token === 'subscript')
                                <flux:editor.subscript />
                            @elseif($token === 'superscript')
                                <flux:editor.superscript />
                            @elseif($token === 'highlight')
                                <flux:editor.highlight />
                            @elseif($token === 'link')
                                <flux:editor.link />
                            @elseif($token === 'code')
                                <flux:editor.code />
                            @elseif($token === 'align')
                                <flux:editor.align />
                            @elseif($token === 'undo')
                                <flux:editor.undo />
                            @elseif($token === 'redo')
                                <flux:editor.redo />
                            @elseif($token !== '')
                                {{-- Custom item: try to render view flux/editor/{token} if exists --}}
                                @includeIf('flux.editor.'.$token)
                            @endif
                        @endforeach
                    </div>
                @endif

                <div
                    wire:ignore
                    data-flux-editor-content
                    data-slot="content"
                    class="{{ $contentClasses }}"
                    @if($disabled) data-disabled @endif
                >
                    {{-- Tiptap mounts here; keep slot html as fallback for noscript --}}
                    {!! $initialValue !!}
                </div>
            @endif
        </div>

        @error($name)
            <flux:error>{{ $message }}</flux:error>
        @enderror
    </div>

    @if($description && $attributes->has('description:trailing'))
        <flux:description>{{ $description }}</flux:description>
    @endif
</flux:with-field>

@once
@push('styles')
<style>
[data-flux-editor] .ProseMirror { outline: none; }
[data-flux-editor] .ProseMirror p.is-editor-empty:first-child::before { content: attr(data-placeholder); float: left; color: #a1a1aa; pointer-events: none; height: 0; }
</style>
@endpush
@endonce
