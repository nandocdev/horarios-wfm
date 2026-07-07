<div class="space-y-8">
    <div>
        <flux:heading size="xl">{{ $mode === 'edit' ? 'Editar Encuesta' : 'Crear Nueva Encuesta' }}</flux:heading>
        <flux:subheading>Define la pregunta y las opciones para recolectar feedback de los empleados.</flux:subheading>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="lg:col-span-8 space-y-4">
            <flux:card>
                <div class="space-y-4">
                    <flux:input 
                        wire:model="form.question" 
                        label="Pregunta de la Encuesta"
                        placeholder="Ej. ¿Qué te parece el nuevo horario de almuerzo?" 
                    />

                    <flux:separator text="Opciones de Respuesta" />

                    <div class="space-y-3">
                        @foreach($form->options as $index => $option)
                            <div class="flex items-end gap-3" wire:key="option-{{ $index }}">
                                <div class="flex-1">
                                    <flux:input 
                                        wire:model="form.options.{{ $index }}.text" 
                                        label="Opción {{ $index + 1 }}"
                                        placeholder="Escribe una respuesta..." 
                                    />
                                </div>
                                <div class="w-32">
                                    <flux:select wire:model="form.options.{{ $index }}.color" label="Color">
                                        <option value="blue">Azul</option>
                                        <option value="green">Verde</option>
                                        <option value="red">Rojo</option>
                                        <option value="yellow">Amarillo</option>
                                        <option value="indigo">Indigo</option>
                                        <option value="purple">Morado</option>
                                        <option value="pink">Rosa</option>
                                        <option value="gray">Gris</option>
                                    </flux:select>
                                </div>
                                @if(count($form->options) > 2)
                                    <flux:button 
                                        variant="ghost" 
                                        icon="trash" 
                                        size="sm" 
                                        color="red"
                                        wire:click="removeOption({{ $index }})" 
                                    />
                                @endif
                            </div>
                        @endforeach

                        @if(count($form->options) < 10)
                            <flux:button 
                                variant="outline" 
                                icon="plus" 
                                size="sm" 
                                class="w-full mt-2"
                                wire:click="addOption"
                            >
                                Agregar otra opción
                            </flux:button>
                        @endif
                    </div>
                </div>
            </flux:card>
        </div>

        <div class="lg:col-span-4 space-y-4">
            <flux:card>
                <div class="space-y-4">
                    <flux:input type="datetime-local" wire:model="form.scheduled_at" label="Fecha de Inicio" />
                    
                    <flux:input type="datetime-local" wire:model="form.expires_at" label="Fecha de Expiración" />

                    <flux:input type="datetime-local" wire:model="form.archive_at" label="Archivado Automático" />

                    <flux:field>
                        <flux:select wire:model="form.workflow_action" label="Flujo de moderación">
                            <option value="save_draft">Guardar como borrador</option>
                            <option value="submit_review">Enviar a revisión</option>
                        </flux:select>
                        <flux:error name="form.workflow_action" />
                    </flux:field>

                    <flux:checkbox wire:model="form.is_active" label="Encuesta activa y visible" />
                </div>
            </flux:card>

            <div class="flex gap-3">
                <flux:button variant="ghost" class="flex-1" href="{{ route('communications.polls.index') }}" wire:navigate>
                    Cancelar
                </flux:button>
                <flux:button variant="primary" type="submit" class="flex-1" :loading="true">
                    {{ $mode === 'edit' ? 'Actualizar' : 'Guardar Encuesta' }}
                </flux:button>
            </div>
        </div>
    </form>
</div>
