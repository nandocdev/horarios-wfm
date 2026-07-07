<div class="space-y-8">
    <div>
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Editar Noticia' : 'Crear Nueva Noticia' }}</flux:heading>
        <flux:subheading>Completa los campos para publicar contenido en la página de inicio.</flux:subheading>
    </div>

    <form wire:submit="{{ $mode === 'edit' ? 'update' : 'save' }}" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-8 space-y-4">
            <flux:card>
                <div class="space-y-4">
                    <flux:input wire:model.live.debounce.300ms="form.title" label="Título de la Noticia"
                        placeholder="Ej. Actualización del Sistema WFM" />

                    <flux:input wire:model="form.slug" label="Slug (URL amigable)"
                        placeholder="ej-actualizacion-sistema" readonly />

                    <flux:textarea wire:model="form.excerpt" label="Resumen o Extracto (Opcional)" rows="2"
                        placeholder="Breve descripción para el listado..." />

                    <flux:editor wire:model="form.content" label="Contenido Completo"
                        description="Editor Markdown: usa la barra para formato rápido." rows="10"
                        placeholder="Escribe aquí el cuerpo de la noticia..." />

                    <div class="flex flex-col gap-4">
                        <flux:field>
                            <flux:select wire:model="form.category_ids" label="Categorías" multiple>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="form.category_ids" />
                            <flux:error name="form.category_ids.*" />
                        </flux:field>

                        <flux:field>
                            <flux:select wire:model="form.tag_ids" label="Etiquetas" multiple>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="form.tag_ids" />
                            <flux:error name="form.tag_ids.*" />
                        </flux:field>
                    </div>
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-4 space-y-4">
            <flux:card>
                <div class="space-y-4">
                    <flux:input type="datetime-local" wire:model="form.published_at" label="Fecha de Publicación" />

                    <flux:input type="datetime-local" wire:model="form.scheduled_at"
                        label="Publicación Programada (scheduled_at)" />

                    <flux:input type="datetime-local" wire:model="form.archive_at" label="Archivado Automático" />

                    @if($mode === 'create')
                        <flux:field>
                            <flux:select wire:model="form.workflow_action" label="Flujo de moderación"
                                placeholder="Selecciona una acción">
                                <option value="save_draft">Guardar como borrador</option>
                                <option value="submit_review">Enviar a revisión</option>
                            </flux:select>
                            <flux:error name="form.workflow_action" />
                        </flux:field>
                    @endif

                    <flux:checkbox wire:model="form.is_active" label="Noticia activa y visible" />

                    <flux:separator />

                    <!-- Imagen Destacada -->
                    <flux:input type="file" wire:model="form.featured_image" label="Imagen Destacada"
                        accept="image/*" />
                    <flux:error name="form.featured_image" />

                    @if ($form->featured_image)
                        <div class="mt-2 text-xs text-zinc-500">Imagen seleccionada:
                            {{ $form->featured_image->getClientOriginalName() }}
                        </div>
                    @elseif ($mode === 'edit' && $this->news->hasMedia('featured_image'))
                        <div class="mt-2">
                            <img src="{{ $this->news->getFirstMediaUrl('featured_image') }}"
                                class="h-24 w-full object-cover rounded shadow" />
                        </div>
                    @endif

                    <!-- Adjuntos (Vidéos, PDF, etc) -->
                    <flux:input type="file" wire:model="form.attachments"
                        label="Archivos Adjuntos (PDF, Videos, Imágenes)" multiple />
                    <flux:error name="form.attachments" />
                    <flux:error name="form.attachments.*" />
                    <flux:subheading class="mt-1 text-xs">Múltiples archivos permitidos.</flux:subheading>
                    <flux:subheading class="text-xs text-zinc-500">
                        Límite efectivo del servidor: {{ ini_get('upload_max_filesize') }} por archivo.
                    </flux:subheading>

                    @if ($mode === 'edit' && $this->news->hasMedia('attachments'))
                        <div class="mt-4 space-y-2">
                            <flux:heading size="sm">Archivos actuales:</flux:heading>
                            @foreach($this->news->getMedia('attachments') as $media)
                                <div class="flex items-center justify-between p-2 bg-zinc-50 rounded border text-xs">
                                    <span class="truncate max-w-[150px]">{{ $media->file_name }}</span>
                                    <flux:button variant="ghost" icon="trash" size="xs" color="red"
                                        wire:click="deleteMedia({{ $media->id }})" wire:confirm="¿Eliminar archivo?" />
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </flux:card>

            <div class="flex gap-3">
                <flux:button variant="ghost" class="flex-1" href="{{ route('communications.news.index') }}"
                    wire:navigate>Cancelar</flux:button>
                @if($mode === 'create')
                    <flux:button variant="outline" type="submit" class="flex-1"
                        wire:click="$set('form.workflow_action', 'save_draft')" :loading="true">
                        Guardar borrador
                    </flux:button>
                    <flux:button variant="primary" type="submit" class="flex-1"
                        wire:click="$set('form.workflow_action', 'submit_review')" :loading="true">
                        Enviar a revisión
                    </flux:button>
                @else
                    <flux:button variant="primary" type="submit" class="flex-1" :loading="true">
                        Actualizar
                    </flux:button>
                @endif
            </div>
        </div>
    </form>
</div>
