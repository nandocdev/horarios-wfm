<flux:editor.button icon="arrow-uturn-left" tooltip="{{ __('Undo') }}" x-on:click="$root.editor?.chain().focus().undo().run()" x-bind:disabled="!$root.editor?.can().chain().focus().undo().run()" />
