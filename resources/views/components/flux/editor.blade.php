@blaze(fold: true)

@props([
    'name' => $attributes->whereStartsWith('wire:model')->first(),
    'rows' => 10,
    'invalid' => null,
    'toolbar' => true,
])

@php
$classes = \Flux\Flux::classes()
    ->add('block p-3 w-full')
    ->add('shadow-xs border rounded-b-lg')
    ->add('bg-white dark:bg-white/10')
    ->add('text-base sm:text-sm text-zinc-700 placeholder-zinc-400 dark:text-zinc-300 dark:placeholder-zinc-400')
    ->add('border-zinc-200 border-t-0 border-b-zinc-300/80 dark:border-white/10')
    ->add('focus:outline-none focus:ring-2 focus:ring-blue-500/30')
    ->add('data-invalid:shadow-none data-invalid:border-red-500 dark:data-invalid:border-red-500')
    ;
@endphp

<flux:with-field :$attributes>
    <div
        x-data="{
            wrap(open, close) {
                const el = this.$refs.editor;
                const start = el.selectionStart;
                const end = el.selectionEnd;
                const selected = el.value.substring(start, end);
                const before = el.value.substring(0, start);
                const after = el.value.substring(end);

                el.value = `${before}${open}${selected}${close}${after}`;
                el.focus();
                el.selectionStart = start + open.length;
                el.selectionEnd = end + open.length;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            },
            prefixLine(prefix) {
                const el = this.$refs.editor;
                const start = el.selectionStart;
                const end = el.selectionEnd;
                const selected = el.value.substring(start, end);
                const before = el.value.substring(0, start);
                const after = el.value.substring(end);

                const lines = (selected || 'Texto').split('\n').map(line => `${prefix}${line}`);
                const result = lines.join('\n');

                el.value = `${before}${result}${after}`;
                el.focus();
                el.selectionStart = start;
                el.selectionEnd = start + result.length;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            },
            insertLink() {
                const url = prompt('URL del enlace:', 'https://');
                if (!url) return;

                const el = this.$refs.editor;
                const start = el.selectionStart;
                const end = el.selectionEnd;
                const selected = el.value.substring(start, end) || 'texto';
                const markdown = `[${selected}](${url})`;

                const before = el.value.substring(0, start);
                const after = el.value.substring(end);
                el.value = `${before}${markdown}${after}`;
                el.focus();
                el.selectionStart = start;
                el.selectionEnd = start + markdown.length;
                el.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }"
        class="rounded-lg border border-zinc-200 dark:border-white/10 overflow-hidden"
    >
        @if($toolbar)
            <div class="flex flex-wrap items-center gap-1 p-2 bg-zinc-50 dark:bg-zinc-900/40 border-b border-zinc-200 dark:border-white/10">
                <flux:button type="button" variant="ghost" size="xs" icon="bold" x-on:click="wrap('**', '**')" title="Negrita" />
                <flux:button type="button" variant="ghost" size="xs" icon="italic" x-on:click="wrap('*', '*')" title="Cursiva" />
                <flux:button type="button" variant="ghost" size="xs" icon="code-bracket" x-on:click="wrap('`', '`')" title="Código" />
                <flux:separator vertical class="mx-1" />
                <flux:button type="button" variant="ghost" size="xs" icon="list-bullet" x-on:click="prefixLine('- ')" title="Lista" />
                <flux:button type="button" variant="ghost" size="xs" icon="link" x-on:click="insertLink()" title="Enlace" />
            </div>
        @endif

        <textarea
            x-ref="editor"
            {{ $attributes->class($classes) }}
            rows="{{ $rows }}"
            @isset ($name) name="{{ $name }}" @endisset
            @unblaze(scope: ['name' => $name ?? null, 'invalid' => $invalid ?? false])
            <?php if ($scope['invalid'] || ($scope['name'] && $errors->has($scope['name']))): ?>
            aria-invalid="true" data-invalid
            <?php endif; ?>
            @endunblaze
            data-flux-control
            data-flux-editor
        >{{ $slot }}</textarea>
    </div>
</flux:with-field>
