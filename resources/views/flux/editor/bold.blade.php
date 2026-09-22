<flux:editor.button icon="bold" tooltip="{{ __('Bold') }}" x-on:click="$root.editor?.chain().focus().toggleBold().run()" x-bind:data-active="$root.editor?.isActive('bold') ? '' : null" />
