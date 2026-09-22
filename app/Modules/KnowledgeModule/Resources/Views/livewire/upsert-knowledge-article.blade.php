<div class="space-y-6">
    <x-wfm.page-header :title="$article ? 'Editar Artículo' : 'Nuevo Artículo'" :description="$article ? 'ID: ' . $article->id . ' | Versión actual: v' . $article->version : 'Crea un nuevo artículo para la base de conocimiento.'">
        <x-slot:actions>
            <flux:button href="{{ route('knowledge.admin') }}" wire:navigate variant="ghost" icon="arrow-left">Volver</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Columna Principal: Editor de Contenido (2/3) --}}
            <div class="lg:col-span-2 space-y-6">
                <x-wfm.section title="Contenido Principal" description="Escribe la información detallada que guiará a los operadores.">
                    <div class="space-y-4">
                        <flux:input wire:model="form.title" label="Título *" placeholder="Ej. Proceso de Cancelación de Citas de Odontología" maxlength="255" />

                        <flux:textarea wire:model="form.summary" label="Resumen Corto" placeholder="Describe brevemente de qué trata este artículo para agilizar la lectura en los resultados de búsqueda..." maxlength="500" rows="2" />

                        <flux:editor
                            wire:model="form.content"
                            label="Contenido Detallado *"
                            description="Usa la barra de herramientas para dar formato. Se guarda como HTML enriquecido. Atajos: Ctrl+B negrita, Ctrl+I cursiva."
                            placeholder="Escribe las instrucciones paso a paso, guiones de atención o respuestas..."
                            toolbar="heading | bold italic underline strike | bullet ordered blockquote | link highlight code | align ~ undo redo"
                            class="**:data-[slot=content]:min-h-[360px]! **:data-[slot=content]:max-h-[600px]!"
                            :invalid="$errors->has('form.content')"
                        />
                        <flux:error name="form.content" />
                    </div>
                </x-wfm.section>

                {{-- Guía de uso del editor enriquecido --}}
                <div class="p-4 bg-wfm-surface/60 rounded-lg border border-wfm-surface-border text-xs text-wfm-surface-muted space-y-2">
                    <span class="font-bold text-wfm-navy-800 dark:text-white uppercase tracking-wider text-[11px] block">💡 Guía Rápida de Formato HTML — editor enriquecido:</span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-[11px] leading-relaxed">
                        <div><span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">Ctrl+B</span> Negrita · <span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">Ctrl+I</span> Cursiva · <span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">Ctrl+U</span> Subrayado</div>
                        <div><span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">**texto**</span> · <span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">*texto*</span> · <span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">#</span> título</div>
                        <div><span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">- lista</span> · <span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">1. lista</span> · <span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">&gt; cita</span></div>
                        <div><span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">Ctrl+Z</span> deshacer · pega sin formato con <span class="font-mono bg-white dark:bg-zinc-800 px-1 py-0.5 rounded border">Ctrl+Shift+V</span></div>
                    </div>
                </div>
            </div>

            {{-- Columna Derecha: Metadatos y Clasificación (1/3) --}}
            <div class="space-y-6">
                {{-- Tarjeta de Flujo Editorial --}}
                <x-wfm.section title="Flujo Editorial" description="Estado, clasificación y ciclo de vida">
                    <div class="space-y-4">
                        <flux:select wire:model="form.status" label="Estado de Publicación *">
                            <flux:select.option value="draft">📁 Borrador (Boceto / Privado)</flux:select.option>
                            <flux:select.option value="review">🔍 En Revisión (Aprobación Pendiente)</flux:select.option>
                            <flux:select.option value="published">🟢 Publicado (Vigente para Operadores)</flux:select.option>
                            <flux:select.option value="archived">📦 Archivado (Histórico / Retirado)</flux:select.option>
                        </flux:select>
                        <flux:error name="form.status" />

                        <flux:select wire:model="form.category_id" label="Categoría" placeholder="Seleccionar categoría...">
                            <flux:select.option value="">Sin categoría</flux:select.option>
                            @foreach($categories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="form.category_id" />

                        <flux:select wire:model="form.directory_unit_id" label="Ficha de Contacto (Unidad)" placeholder="Seleccionar unidad..." searchable>
                            <flux:select.option value="">Sin ficha de contacto</flux:select.option>
                            @foreach($units as $unit)
                                <flux:select.option value="{{ $unit->id }}">{{ $unit->display_name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="form.directory_unit_id" />

                        <div class="pt-3 border-t border-wfm-surface-border space-y-3">
                            <flux:input type="datetime-local" wire:model="form.published_at" label="Vigente Desde" />
                            <flux:error name="form.published_at" />

                            <flux:input type="datetime-local" wire:model="form.expires_at" label="Vigente Hasta" />
                            <flux:error name="form.expires_at" />

                            <p class="text-[11px] text-wfm-surface-muted leading-snug">
                                Si se dejan vacías, la vigencia iniciará de inmediato y no tendrá expiración.
                            </p>
                        </div>
                    </div>
                </x-wfm.section>

                {{-- Tarjeta de Distribución Operativa --}}
                <x-wfm.section title="Distribución Operativa" description="Visibilidad en colas y búsqueda">
                    <div class="space-y-4">
                        <flux:field label="Colas de Atención Vinculadas *" hint="Artículo visible para operadores de estas colas">
                            <div class="max-h-[220px] overflow-y-auto p-2.5 border border-wfm-surface-border rounded-md space-y-1.5 bg-wfm-surface/40">
                                @forelse($queues as $queue)
                                    <label class="flex items-center justify-between gap-2 text-xs cursor-pointer hover:bg-wfm-surface-hover p-1.5 rounded transition-colors">
                                        <div class="flex items-center gap-2">
                                            <flux:checkbox wire:model="form.queues" value="{{ $queue->id }}" />
                                            <span class="font-medium text-wfm-navy-800 dark:text-white">
                                                <flux:icon.queue-list class="w-3.5 h-3.5 inline mr-1 text-wfm-navy-600 dark:text-wfm-navy-300" />
                                                {{ $queue->name }}
                                            </span>
                                        </div>
                                        <span class="text-[10px] bg-wfm-surface text-wfm-surface-muted px-1.5 py-0.5 rounded font-mono border border-wfm-surface-border">Prioridad: {{ $queue->priority }}</span>
                                    </label>
                                @empty
                                    <p class="text-xs text-wfm-surface-muted p-2">No hay colas de atención disponibles.</p>
                                @endforelse
                            </div>
                            <flux:error name="form.queues" />
                        </flux:field>

                        <flux:field label="Etiquetas (Búsqueda Rápida)" hint="Separa con comas. Ej: cita, laboratorio, cancelación">
                            <flux:input wire:model="form.tagsString" placeholder="cita, laboratorio, cancelación" />
                            <p class="text-[11px] text-wfm-surface-muted mt-1">
                                Facilita que los operadores encuentren el artículo mediante palabras claves cortas.
                            </p>
                            <flux:error name="form.tagsString" />
                        </flux:field>
                    </div>
                </x-wfm.section>

                {{-- Tarjeta de Acciones --}}
                <x-wfm.section>
                    <div class="flex flex-col gap-2.5">
                        <flux:button type="submit" variant="primary" icon="check" class="w-full justify-center">
                            {{ $article ? 'Guardar Cambios' : 'Crear Artículo' }}
                        </flux:button>
                        <flux:button href="{{ route('knowledge.admin') }}" variant="ghost" wire:navigate class="w-full justify-center">
                            Cancelar y Salir
                        </flux:button>
                    </div>
                </x-wfm.section>
            </div>
        </div>
    </form>
</div>
