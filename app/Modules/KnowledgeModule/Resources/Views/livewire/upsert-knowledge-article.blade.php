<div class="max-w-4xl mx-auto px-4 sm:px-4 lg:px-8 py-8 space-y-8">
    {{-- Barra superior de navegación --}}
    <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-4">
        <div>
            <div class="flex items-center gap-2">
                <flux:badge size="sm" color="zinc">{{ $article ? 'EDICIÓN' : 'NUEVO' }}</flux:badge>
                @if($article)
                    <flux:text size="xs" class="text-slate-400 font-mono">ID: {{ $article->id }} | Versión actual: v{{ $article->version }}</flux:text>
                @endif
            </div>
            <flux:heading size="xl" class="font-bold text-slate-900 dark:text-white">{{ $article ? 'Editar Artículo' : 'Nuevo Artículo' }}</flux:heading>
        </div>
        <flux:button href="{{ route('knowledge.admin') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm">
            Volver al Panel
        </flux:button>
    </div>

    {{-- Formulario Principal --}}
    <form wire:submit.prevent="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            {{-- Columna Izquierda: Editor de Contenido (2/3) --}}
            <div class="lg:col-span-2 space-y-4">
                <flux:card class="p-4 bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 space-y-5">
                    <flux:heading size="md" class="font-bold text-slate-800 dark:text-slate-200 pb-2 border-b border-slate-50 dark:border-slate-700">Contenido Principal</flux:heading>

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label class="font-semibold">Título del Artículo</flux:label>
                            <flux:input wire:model="form.title" placeholder="Ej. Proceso de Cancelación de Citas de Odontología" class="w-full" />
                            <flux:text size="xs" class="text-slate-400 mt-1">El slug se generará de manera automática basado en el título.</flux:text>
                            <flux:error name="form.title" />
                        </flux:field>

                        <flux:field>
                            <flux:label class="font-semibold">Resumen Corto</flux:label>
                            <flux:textarea wire:model="form.summary" rows="2" placeholder="Describe brevemente de qué trata este artículo para agilizar la lectura en los resultados de búsqueda..." />
                            <flux:error name="form.summary" />
                        </flux:field>

                        <flux:field>
                            <div class="flex items-center justify-between mb-1">
                                <flux:label class="font-semibold">Contenido Detallado</flux:label>
                                <span class="text-[10px] text-slate-400 font-mono">HTML Habilitado</span>
                            </div>
                            <flux:textarea wire:model="form.content" rows="18" placeholder="Escribe las instrucciones paso a paso, guiones de atención o respuestas..." class="font-mono text-sm leading-relaxed" />
                            <flux:error name="form.content" />
                        </flux:field>
                    </div>
                </flux:card>

                {{-- Tarjeta de Guía Rápida de Formato --}}
                <flux:card class="p-4 bg-slate-50/50 dark:bg-slate-900/20 border border-slate-200/60 dark:border-slate-700/50">
                    <flux:text size="xs" class="text-slate-500 dark:text-slate-400 block font-bold mb-2 uppercase">💡 Guía Rápida de Formato HTML:</flux:text>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-[11px] text-slate-500">
                        <div><strong class="font-mono text-slate-700 dark:text-slate-300">&lt;b&gt;Texto&lt;/b&gt;</strong>: Negrita</div>
                        <div><strong class="font-mono text-slate-700 dark:text-slate-300">&lt;br&gt;</strong>: Salto de línea</div>
                        <div><strong class="font-mono text-slate-700 dark:text-slate-300">&lt;ul&gt; &lt;li&gt;</strong>: Viñetas</div>
                        <div><strong class="font-mono text-slate-700 dark:text-slate-300">&lt;ol&gt; &lt;li&gt;</strong>: Lista numerada</div>
                    </div>
                </flux:card>
            </div>

            {{-- Columna Derecha: Parámetros y Clasificación (1/3) --}}
            <div class="lg:col-span-1 space-y-4">
                
                {{-- Tarjeta de Estado y Publicación --}}
                <flux:card class="p-5 bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 space-y-4">
                    <flux:heading size="md" class="font-bold text-slate-800 dark:text-slate-200 pb-2 border-b border-slate-50 dark:border-slate-700">Flujo Editorial</flux:heading>

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label class="font-semibold">Estado de Publicación</flux:label>
                            <select wire:model="form.status" class="w-full text-xs rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white p-2">
                                <option value="draft">📁 Borrador (Boceto / Privado)</option>
                                <option value="review">🔍 En Revisión (Aprobación Pendiente)</option>
                                <option value="published">🟢 Publicado (Vigente para Operadores)</option>
                                <option value="archived">📦 Archivado (Histórico / Retirado)</option>
                            </select>
                            <flux:text size="xs" class="text-slate-400 mt-1 block">Los operadores solo pueden buscar y ver artículos en estado Publicado.</flux:text>
                            <flux:error name="form.status" />
                        </flux:field>

                        <flux:field>
                            <flux:label class="font-semibold">Categoría</flux:label>
                            <select wire:model="form.category_id" class="w-full text-xs rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white p-2">
                                <option value="">Selecciona una categoría...</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <flux:error name="form.category_id" />
                        </flux:field>

                        <div class="space-y-3 pt-2 border-t border-slate-50 dark:border-slate-700/50">
                            <flux:input type="datetime-local" wire:model="form.published_at" label="Vigente Desde" class="text-xs" />
                            <flux:input type="datetime-local" wire:model="form.expires_at" label="Vigente Hasta" class="text-xs" />
                            <flux:text size="xs" class="text-slate-400">Si se dejan vacías, la vigencia iniciará de inmediato y no tendrá expiración.</flux:text>
                        </div>
                    </div>
                </flux:card>

                {{-- Tarjeta de Destinos (Colas y Tags) --}}
                <flux:card class="p-5 bg-white dark:bg-slate-800 shadow-sm border border-slate-200 dark:border-slate-700 space-y-4">
                    <flux:heading size="md" class="font-bold text-slate-800 dark:text-slate-200 pb-2 border-b border-slate-50 dark:border-slate-700">Distribución Operativa</flux:heading>

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label class="font-semibold">Colas de Atención Vinculadas (Al menos una)</flux:label>
                            <div class="mt-1.5 space-y-2 max-h-[220px] overflow-y-auto p-3 border border-slate-200 dark:border-slate-700 rounded-md bg-slate-50/50 dark:bg-slate-900/50">
                                @foreach($queues as $q)
                                    <label class="flex items-start gap-2.5 cursor-pointer py-0.5 hover:bg-slate-100/55 dark:hover:bg-slate-700/30 rounded px-1 transition-opacity">
                                        <input type="checkbox" wire:model="form.queues" value="{{ $q->id }}" class="mt-0.5 rounded border-slate-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="text-xs text-slate-700 dark:text-slate-300 leading-tight">
                                            📞 {{ $q->name }} 
                                            <span class="block text-[10px] text-slate-400 font-mono">Prioridad: {{ $q->priority }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <flux:error name="form.queues" />
                        </flux:field>

                        <flux:field>
                            <flux:label class="font-semibold">Etiquetas (Búsqueda Rápida)</flux:label>
                            <flux:input wire:model="form.tagsString" placeholder="Separadas por comas (ej. citas, cancelacion, laboratorio)" />
                            <flux:text size="xs" class="text-slate-400 mt-1">Facilita que los operadores encuentren el artículo mediante palabras claves cortas.</flux:text>
                            <flux:error name="form.tagsString" />
                        </flux:field>
                    </div>
                </flux:card>

                {{-- Acciones finales --}}
                <flux:card class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-700 flex flex-col gap-2">
                    <flux:button type="submit" variant="primary" class="w-full">
                        Guardar Cambios
                    </flux:button>
                    <flux:button href="{{ route('knowledge.admin') }}" wire:navigate variant="ghost" class="w-full">
                        Cancelar y Salir
                    </flux:button>
                </flux:card>

            </div>
        </div>
    </form>
</div>
