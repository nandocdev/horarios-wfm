@blaze(fold: true)
<div class="relative" data-flux-editor-align>
    <flux:dropdown position="bottom end" offset="6">
        <flux:editor.button icon="bars-3-bottom-left" tooltip="{{ __('Align') }}" />
        <flux:menu>
            <flux:menu.item x-on:click="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().setTextAlign('left').run()" icon="bars-3-bottom-left">{{ __('Left') }}</flux:menu.item>
            <flux:menu.item x-on:click="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().setTextAlign('center').run()" icon="bars-3-center-left">{{ __('Center') }}</flux:menu.item>
            <flux:menu.item x-on:click="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().setTextAlign('right').run()" icon="bars-3-bottom-right">{{ __('Right') }}</flux:menu.item>
        </flux:menu>
    </flux:dropdown>
</div>
