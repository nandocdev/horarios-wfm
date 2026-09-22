<flux:editor.button icon="italic" tooltip="{{ __('Italic') }}" x-on:click="$root.editor?.chain().focus().toggleItalic().run()" x-bind:data-active="$root.editor?.isActive('italic') ? '' : null" />
