<div class="space-y-6">
    <div>
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Editar Categoría' : ($mode === 'show' ? 'Ver Categoría' : 'Crear Categoría') }}</flux:heading>
        <flux:subheading>Administra las categorías de contenido.</flux:subheading>
    </div>

    <form method="POST" action="{{ $mode === 'edit' ? route('platform.communications.admin.categories.update', $category) : route('platform.communications.admin.categories.store') }}" class="max-w-2xl">
        @csrf
        @if($mode === 'edit')
            @method('PUT')
        @endif

        <flux:card>
            <div class="space-y-4">
                <flux:input name="name" label="Nombre *" value="{{ old('name', $category->name ?? '') }}" placeholder="Ej. Operaciones" :disabled="$mode === 'show'" required />

                <flux:input name="slug" label="Slug (URL amigable)" value="{{ old('slug', $category->slug ?? '') }}" :disabled="$mode === 'show'" />

                <flux:textarea name="description" label="Descripción" rows="3" placeholder="Descripción de la categoría..." :disabled="$mode === 'show'">{{ old('description', $category->description ?? '') }}</flux:textarea>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="color" type="color" label="Color" value="{{ old('color', $category->color ?? '#3B82F6') }}" :disabled="$mode === 'show'" />

                    <flux:input name="sort_order" type="number" label="Orden" value="{{ old('sort_order', $category->sort_order ?? 0) }}" :disabled="$mode === 'show'" />
                </div>

                <flux:checkbox name="is_active" label="Categoría activa" value="1" :checked="old('is_active', $category->is_active ?? true)" :disabled="$mode === 'show'" />
            </div>
        </flux:card>

        @if($mode !== 'show')
            <div class="flex gap-3 mt-6">
                <flux:button variant="ghost" href="{{ route('platform.communications.admin.categories.index') }}" wire:navigate>Cancelar</flux:button>
                <flux:button variant="primary" type="submit">{{ $mode === 'edit' ? 'Actualizar' : 'Crear Categoría' }}</flux:button>
            </div>
        @else
            <div class="flex gap-3 mt-6">
                <flux:button variant="primary" href="{{ route('platform.communications.admin.categories.edit', $category) }}" wire:navigate>Editar</flux:button>
                <flux:button variant="ghost" href="{{ route('platform.communications.admin.categories.index') }}" wire:navigate>Volver</flux:button>
            </div>
        @endif
    </form>
</div>
