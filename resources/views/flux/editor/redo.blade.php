<flux:editor.button icon="arrow-uturn-right" tooltip="{{ __('Redo') }}" x-on:click="$root.editor?.chain().focus().redo().run()" x-bind:disabled="!$root.editor?.can().chain().focus().redo().run()" />
