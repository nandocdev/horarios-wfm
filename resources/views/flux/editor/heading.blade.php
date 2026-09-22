@blaze(fold: true)
<div class="relative" data-flux-editor-heading>
    <flux:dropdown position="bottom start" offset="6">
        <flux:editor.button icon="bars-3" tooltip="{{ __('Styles') }}" x-bind:data-active="$el.closest('[data-flux-editor]')?._fluxEditor?.isActive('heading') ? '' : null">
            <span class="text-xs font-medium hidden sm:inline">{{ __('Text') }}</span>
        </flux:editor.button>
        <flux:menu>
            <flux:menu.item x-on:click="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().setParagraph().run()">{{ __('Text') }}</flux:menu.item>
            <flux:menu.item x-on:click="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().toggleHeading({ level: 1 }).run()">{{ __('Heading 1') }}</flux:menu.item>
            <flux:menu.item x-on:click="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().toggleHeading({ level: 2 }).run()">{{ __('Heading 2') }}</flux:menu.item>
            <flux:menu.item x-on:click="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().toggleHeading({ level: 3 }).run()">{{ __('Heading 3') }}</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
