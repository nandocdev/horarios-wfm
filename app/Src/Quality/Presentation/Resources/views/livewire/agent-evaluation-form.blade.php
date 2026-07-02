<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Evaluación: {{ $agent->full_name }}</flux:heading>
            <flux:subheading>Complete la rúbrica de evaluación de calidad.</flux:subheading>
        </div>
    </div>

    <form wire:submit="submit" class="space-y-6">
        <flux:card>
            <flux:select wire:model="selectedFormId" :label="__('Formulario de Evaluación')" required>
                @foreach($forms as $form)
                    <flux:select.option value="{{ $form->id }}">{{ $form->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </flux:card>

        @if($selectedFormId)
            @php $selectedForm = $forms->firstWhere('id', $selectedFormId); @endphp
            @if($selectedForm)
                <flux:card class="space-y-6">
                    <flux:heading size="lg">{{ $selectedForm->name }}</flux:heading>
                    @if($selectedForm->description)
                        <flux:text class="text-zinc 500">{{ $selectedForm->description }}</flux:text>
                    @endif

                    @foreach($selectedForm->criteria as $criterion)
                        <div class="p-4 bg-zinc-50 dark:bg-white/5 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <flux:heading size="md">{{ $criterion->name }}</flux:heading>
                                    @if($criterion->description)
                                        <flux:text size="sm" class="text-zinc-500">{{ $criterion->description }}</flux:text>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($criterion->is_fatal_error)
                                        <flux:badge variant="danger" size="xs">Error Crítico</flux:badge>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-3">
                                <flux:input type="number" wire:model="scores.{{ $criterion->id }}"
                                    :label="__('Puntaje (0-:max)', ['max' => $criterion->max_score])"
                                    min="0" max="{{ $criterion->max_score }}" required />
                            </div>
                        </div>
                    @endforeach
                </flux:card>

                <flux:card class="space-y-4">
                    <flux:textarea wire:model="comments" :label="__('Comentarios')" rows="3" />
                </flux:card>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">Enviar Evaluación</flux:button>
                </div>
            @endif
        @endif
    </form>
</div>
