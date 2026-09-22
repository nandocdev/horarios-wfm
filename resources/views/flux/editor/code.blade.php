<flux:editor.button icon="code-bracket" tooltip="{{ __('Code') }}" x-on:click="$root.editor?.chain().focus().toggleCode().run()" x-bind:data-active="$root.editor?.isActive('code') ? '' : null" />
