<div class="space-y-6">
    <x-wfm.page-header :title="$article ? 'Editar Artículo' : 'Nuevo Artículo'" :description="$article ? 'ID: ' . $article->id . ' | Versión actual: v' . $article->version : 'Crea un nuevo artículo para la base de conocimiento.'">
        <x-slot:actions>
            <flux:button href="{{ route('knowledge.admin') }}" wire:navigate variant="ghost" icon="arrow-left">Volver</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <x-wfm.section>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:input wire:model="form.title" label="Título *" placeholder="Título del artículo" maxlength="255" />
                <flux:select wire:model="form.category_id" label="Categoría" placeholder="Seleccionar categoría">
                    @foreach($categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:textarea wire:model="form.summary" label="Resumen" placeholder="Breve descripción del artículo..." maxlength="500" rows="2" />

            <flux:field label="Contenido *" hint="HTML / Markdown soportado">
                <div class="border border-wfm-surface-border rounded-md overflow-hidden">
                    <div class="bg-wfm-surface px-3 py-2 border-b border-wfm-surface-border text-xs text-wfm-surface-muted">
                        <flux:button type="button" variant="ghost" size="xs" wire:click="$set('activeTab', 'write')" class="{{ ($activeTab ?? 'write') === 'write' ? 'font-semibold' : '' }}">Escribir</flux:button>
                        <flux:button type="button" variant="ghost" size="xs" wire:click="$set('activeTab', 'preview')" class="{{ ($activeTab ?? 'write') === 'preview' ? 'font-semibold' : '' }}">Vista Previa</flux:button>
                    </div>
                    @if(($activeTab ?? 'write') === 'preview' && $form->content)
                        <div class="p-4 prose dark:prose-invert max-w-none text-sm">
                            {!! $form->content !!}
                        </div>
                    @else
                        <textarea wire:model="form.content" rows="16" class="w-full p-4 text-sm font-mono border-0 focus:ring-0 resize-y bg-transparent" placeholder="Escribe el contenido del artículo..."></textarea>
                    @endif
                </div>
                <flux:error name="form.content" />
            </flux:field>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <flux:select wire:model="form.status" label="Estado">
                    <flux:select.option value="draft">Borrador</flux:select.option>
                    <flux:select.option value="review">En Revisión</flux:select.option>
                    <flux:select.option value="published">Publicado</flux:select.option>
                    <flux:select.option value="archived">Archivado</flux:select.option>
                </flux:select>
                <flux:input wire:model="form.priority" label="Prioridad" type="number" min="1" max="999" step="1" />
            </div>

            <flux:field label="Colas Relacionadas" hint="Artículo visible para operadores de estas colas">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto p-3 border border-wfm-surface-border rounded-md">
                    @foreach($queues as $queue)
                        <label class="flex items-center gap-2 text-xs cursor-pointer hover:bg-wfm-surface-hover p-1 rounded">
                            <flux:checkbox wire:model="form.queue_ids" value="{{ $queue->id }}" />
                            {{ $queue->name }}
                        </label>
                    @endforeach
                </div>
                <flux:error name="form.queue_ids" />
            </flux:field>

            <flux:field label="Etiquetas" hint="Separa con comas. Ej: cita, laboratorio, procedimiento">
                <flux:input wire:model="form.tags" placeholder="cita, laboratorio, procedimiento" />
                <flux:error name="form.tags" />
            </flux:field>

            @if($article)
                <flux:field label="Motivo del Cambio" hint="Describe brevemente qué se modificó (se registra en el historial)">
                    <flux:textarea wire:model="form.change_reason" placeholder="Ej: Se actualizó el procedimiento para incluir el nuevo formulario..." rows="2" />
                </flux:field>
            @endif

            <div class="flex justify-end gap-2 pt-4 border-t border-wfm-surface-border">
                <flux:button href="{{ route('knowledge.admin') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                <flux:button type="submit" variant="primary" icon="check">
                    {{ $article ? 'Guardar Cambios' : 'Crear Artículo' }}
                </flux:button>
            </div>
        </form>
    </x-wfm.section>
</div>
