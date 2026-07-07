<div class="space-y-8 max-w-[960px] mx-auto">
    <div>
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Editar Reconocimiento' : 'Crear Nuevo Reconocimiento' }}</flux:heading>
        <flux:subheading>Reconoce el excelente trabajo de un colaborador ante toda la organización.</flux:subheading>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-8 space-y-4">
            <flux:card>
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Colaborador a Reconocer</flux:label>
                        <flux:select wire:model="form.employee_id" placeholder="Selecciona un empleado...">
                            <option value="">-- Selecciona un empleado --</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}">
                                    {{ $employee->full_name }} ({{ $employee->position?->name ?? 'Sin Cargo' }})
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="form.employee_id" />
                    </flux:field>

                    <flux:textarea 
                        wire:model="form.message" 
                        label="Mensaje de Reconocimiento"
                        placeholder="Ej. ¡Excelente trabajo en el cierre de mes! Tu dedicación fue clave..." 
                        rows="6"
                        maxlength="200"
                        description="Máximo 200 caracteres."
                    />

                    <div class="pt-4">
                        <flux:label>Imagen del Banner (Opcional)</flux:label>
                        <flux:text size="sm" class="mb-4">Esta imagen se mostrará como fondo en el carrusel de reconocimientos.</flux:text>
                        
                        <div class="flex items-start gap-4">
                            @if($form->image)
                                <div class="relative w-32 h-20 rounded-md overflow-hidden border border-slate-200">
                                    <img src="{{ $form->image->temporaryUrl() }}" class="w-full h-full object-cover">
                                </div>
                            @elseif($mode === 'edit' && $shoutout->hasMedia('banner'))
                                <div class="relative w-32 h-20 rounded-md overflow-hidden border border-slate-200">
                                    <img src="{{ $shoutout->getFirstMediaUrl('banner', 'thumb') }}" class="w-full h-full object-cover">
                                </div>
                            @endif

                            <div class="flex-1">
                                <flux:input type="file" wire:model="form.image" accept="image/*" />
                                <flux:error name="form.image" />
                                <div wire:loading wire:target="form.image" class="mt-2 text-xs text-slate-600 font-medium italic">
                                    Subiendo imagen...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-4 space-y-4">
            <flux:card>
                <div class="space-y-4">
                    <flux:input type="datetime-local" wire:model="form.scheduled_at" label="Fecha de Publicación" />
                    
                    <flux:input type="datetime-local" wire:model="form.archive_at" label="Fecha de Archivado" />

                    <flux:field>
                        <flux:select wire:model="form.workflow_action" label="Flujo de moderación">
                            <option value="save_draft">Guardar como borrador</option>
                            <option value="submit_review">Enviar a revisión</option>
                        </flux:select>
                        <flux:error name="form.workflow_action" />
                    </flux:field>

                    <flux:checkbox wire:model="form.is_active" label="Reconocimiento activo" />
                </div>
            </flux:card>

            <div class="flex gap-4">
                <flux:button variant="ghost" class="flex-1" href="{{ route('communications.shoutouts.index') }}" wire:navigate>
                    Cancelar
                </flux:button>
                <flux:button variant="primary" type="submit" class="flex-1" :loading="true">
                    {{ $mode === 'edit' ? 'Actualizar' : 'Guardar' }}
                </flux:button>
            </div>
        </div>
    </form>
</div>
