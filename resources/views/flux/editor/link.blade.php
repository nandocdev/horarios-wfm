@blaze(fold: true)
<div x-data="{ open: false, url: '' }" data-flux-editor-link class="relative">
    <flux:editor.button icon="link" tooltip="{{ __('Insert link') }}" x-on:click="const ed = $el.closest('[data-flux-editor]')._fluxEditor; if (ed?.isActive('link')) { ed.chain().focus().unsetLink().run() } else { url = ed?.getAttributes('link').href || ''; open = true; $nextTick(() => $refs.linkInput?.focus()) }" x-bind:data-active="$el.closest('[data-flux-editor]')._fluxEditor?.isActive('link') ? '' : null" />
    <div x-show="open" x-cloak class="absolute z-20 mt-1 p-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-white/10 rounded-lg shadow-lg flex gap-2" @click.outside="open = false">
        <input x-ref="linkInput" x-model="url" type="url" placeholder="https://" class="w-56 px-2 py-1 text-sm border rounded dark:bg-zinc-900 dark:border-white/10" @keydown.enter.prevent="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().setLink({ href: url }).run(); open=false" @keydown.escape.window="open=false" />
        <flux:button size="xs" variant="primary" x-on:click="$el.closest('[data-flux-editor]')._fluxEditor?.chain().focus().setLink({ href: url }).run(); open=false">{{ __('Insert link') }}</flux:button>
        <flux:button size="xs" variant="ghost" x-on:click="open=false">×</flux:button>
    </div>
</div>
