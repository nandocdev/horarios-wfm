<div class="space-y-6">
    <div>
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Editar Noticia' : 'Crear Nueva Noticia' }}</flux:heading>
        <flux:subheading>Completa los campos para publicar contenido en la página de inicio.</flux:subheading>
    </div>

    <form wire:submit="{{ $mode === 'edit' ? 'update' : 'save' }}" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8 space-y-6">
            <flux:card>
                <div class="space-y-4">
                    <flux:input wire:model.live.debounce.300ms="form.title" label="Título de la Noticia" placeholder="Ej. Actualización del Sistema WFM" />

                    <flux:input wire:model="form.slug" label="Slug (URL amigable)" placeholder="ej-actualizacion-sistema" readonly />

                    <flux:textarea wire:model="form.excerpt" label="Resumen o Extracto (Opcional)" rows="2" placeholder="Breve descripción para el listado..." />

                    <flux:editor wire:model="form.content" label="Contenido Completo" description="Editor Markdown" rows="10" placeholder="Escribe aquí el cuerpo de la noticia..." />

                    <div class="flex flex-col gap-4">
                        <flux:select wire:model="form.category_ids" label="Categorías" multiple>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>

                        <flux:select wire:model="form.tag_ids" label="Etiquetas" multiple>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-4 space-y-6">
            <flux:card>
                <div class="space-y-4">
                    <flux:input type="datetime-local" wire:model="form.published_at" label="Fecha de Publicación" />
                    <flux:input type="datetime-local" wire:model="form.scheduled_at" label="Publicación Programada" />
                    <flux:input type="datetime-local" wire:model="form.archive_at" label="Archivado Automático" />

                    <flux:checkbox wire:model="form.is_active" label="Noticia activa y visible" />

                    <flux:separator />

                    <flux:input type="file" wire:model="featured_image" label="Imagen Destacada" accept="image/*" />
                    <flux:error name="featured_image" />

                    @if ($featured_image)
                        <div class="mt-2 text-xs text-zinc-500">Imagen seleccionada: {{ $featured_image->getClientOriginalName() }}</div>
                    @elseif ($mode === 'edit' && isset($news) && $news->hasMedia('featured_image'))
                        <div class="mt-2">
                            <img src="{{ $news->getFirstMediaUrl('featured_image') }}" class="h-24 w-full object-cover rounded shadow" />
                        </div>
                    @endif

                    <flux:input type="file" wire:model="attachments" label="Archivos Adjuntos (PDF, Videos, Imágenes)" multiple />
                    <flux:error name="attachments" />
                    <flux:error name="attachments.*" />
                    <flux:subheading class="mt-1 text-xs">Múltiples archivos permitidos.</flux:subheading>

                    @if ($mode === 'edit' && isset($news) && $news->hasMedia('attachments'))
                        <div class="mt-4 space-y-2">
                            <flux:heading size="sm">Archivos actuales:</flux:heading>
                            @foreach($news->getMedia('attachments') as $media)
                                <div class="flex items-center justify-between p-2 bg-zinc-50 rounded border text-xs">
                                    <span class="truncate max-w-[150px]">{{ $media->file_name }}</span>
                                    <flux:button variant="ghost" icon="trash" size="xs" color="red" wire:click="deleteMedia({{ $media->id }})" wire:confirm="¿Eliminar archivo?" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </flux:card>

            <div class="flex gap-3">
                <flux:button variant="ghost" class="flex-1" href="{{ route('platform.communications.news.index') }}" wire:navigate>Cancelar</flux:button>
                @if($mode === 'create')
                    <flux:button variant="primary" type="submit" class="flex-1">Guardar</flux:button>
                @else
                    <flux:button variant="primary" type="submit" class="flex-1">Actualizar</flux:button>
                @endif
            </div>
        </div>
    </form>
</div>
