<div class="space-y-6">
    <div>
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Editar Etiqueta' : ($mode === 'show' ? 'Ver Etiqueta' : 'Crear Etiqueta') }}</flux:heading>
        <flux:subheading>Administra las etiquetas de contenido.</flux:subheading>
    </div>

    <form method="POST" action="{{ $mode === 'edit' ? route('platform.communications.admin.tags.update', $tag) : route('platform.communications.admin.tags.store') }}" class="max-w-2xl">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <flux:card>
            <div class="space-y-4">
                <flux:input name="name" label="Nombre *" value="{{ old('name', $tag->name ?? '') }}" placeholder="Ej. WFM" :disabled="$mode === 'show'" required />

                <flux:input name="slug" label="Slug (URL amigable)" value="{{ old('slug', $tag->slug ?? '') }}" :disabled="$mode === 'show'" />

                <flux:input name="color" type="color" label="Color" value="{{ old('color', $tag->color ?? '#6B7280') }}" :disabled="$mode === 'show'" />

                <flux:checkbox name="is_active" label="Etiqueta activa" value="1" :checked="old('is_active', $tag->is_active ?? true)" :disabled="$mode === 'show'" />
            </div>
        </flux:card>

        @if($mode !== 'show')
            <div class="flex gap-3 mt-6">
                <flux:button variant="ghost" href="{{ route('platform.communications.admin.tags.index') }}" wire:navigate>Cancelar</flux:button>
                <flux:button variant="primary" type="submit">{{ $mode === 'edit' ? 'Actualizar' : 'Crear Etiqueta' }}</flux:button>
            </div>
        @else
            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" href="{{ route('platform.communications.admin.tags.edit', $tag) }}" wire:navigate>Editar</flux:button>
                <flux:button variant="ghost" href="{{ route('platform.communications.admin.tags.index') }}" wire:navigate>Volver</flux:button>
            </div>
        @endif
    </form>
</div>
