<div class="space-y-8">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Detalle de Evaluación</flux:heading>
            <flux:subheading>{{ $evaluation->queue?->code }} — {{ $evaluation->dteval?->format('d/m/Y') }}</flux:subheading>
        </div>
        <flux:button href="{{ route('quality.evaluations.index') }}" variant="subtle" icon="arrow-left">Volver</flux:button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <flux:card>
                <div class="space-y-4">
                    <flux:heading size="lg">Puntajes por Criterio</flux:heading>
                    <flux:separator />
                    <div class="space-y-2">
                        @foreach($evaluation->scores as $score)
                            <div class="flex items-center justify-between p-2 bg-slate-50 dark:bg-slate-900 rounded">
                                <flux:text size="sm">{{ $score->criteriaVersion?->criterio_text ?? '—' }}</flux:text>
                                <flux:badge size="sm" color="blue" inset="top">{{ $score->puntaje_obtenido }} / {{ $score->criteriaVersion?->puntaje ?? '?' }}</flux:badge>
                            </div>
                        @endforeach
                    </div>
                    <flux:separator />
                    <div class="flex justify-between items-center">
                        <flux:heading size="md">Score Total</flux:heading>
                        <flux:badge size="lg" color="{{ $evaluation->score >= 80 ? 'green' : ($evaluation->score >= 60 ? 'amber' : 'red') }}">
                            {{ $evaluation->score ?? '—' }}
                        </flux:badge>
                    </div>
                </div>
            </flux:card>

            @if($evaluation->callobs)
                <flux:card>
                    <div class="space-y-2">
                        <flux:heading size="lg">Observaciones</flux:heading>
                        <flux:separator />
                        <flux:text>{{ $evaluation->callobs }}</flux:text>
                    </div>
                </flux:card>
            @endif

            @if($evaluation->feedbacks->isNotEmpty())
                <flux:card>
                    <div class="space-y-4">
                        <flux:heading size="lg">Feedback</flux:heading>
                        <flux:separator />
                        @foreach($evaluation->feedbacks as $feedback)
                            <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded">
                                <flux:text size="sm">{{ $feedback->obsfeed }}</flux:text>
                                <flux:text size="xs" class="text-slate-400 block mt-1">{{ $feedback->creator?->name ?? '—' }} · {{ $feedback->created_at?->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif
        </div>

        <div class="space-y-6">
            <flux:card>
                <div class="space-y-2">
                    <flux:heading size="lg">Información</flux:heading>
                    <flux:separator />
                    <div class="space-y-1">
                        <flux:text size="xs" class="text-slate-500 uppercase font-bold">Cola</flux:text>
                        <flux:text size="sm">{{ $evaluation->queue?->code }} — {{ $evaluation->queue?->name }}</flux:text>
                    </div>
                    <div class="space-y-1">
                        <flux:text size="xs" class="text-slate-500 uppercase font-bold">Empleado ID</flux:text>
                        <flux:text size="sm">{{ $evaluation->employee_id }}</flux:text>
                    </div>
                    <div class="space-y-1">
                        <flux:text size="xs" class="text-slate-500 uppercase font-bold">Evaluador ID</flux:text>
                        <flux:text size="sm">{{ $evaluation->evaluator_id }}</flux:text>
                    </div>
                    <div class="space-y-1">
                        <flux:text size="xs" class="text-slate-500 uppercase font-bold">Estado</flux:text>
                        <flux:badge size="sm" color="{{ $evaluation->status === 'activa' ? 'green' : 'zinc' }}" inset="top">{{ $evaluation->status }}</flux:badge>
                    </div>
                    @if($evaluation->clip_id)
                        <div class="space-y-1">
                            <flux:text size="xs" class="text-slate-500 uppercase font-bold">Clip ID</flux:text>
                            <flux:text size="sm">{{ $evaluation->clip_id }}</flux:text>
                        </div>
                    @endif
                </div>
            </flux:card>

            @if($evaluation->calibrations->isNotEmpty())
                <flux:card>
                    <div class="space-y-2">
                        <flux:heading size="lg">Calibraciones</flux:heading>
                        <flux:separator />
                        @foreach($evaluation->calibrations as $cal)
                            <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded space-y-1">
                                <flux:text size="sm">{{ $cal->score_anterior }} → <strong>{{ $cal->score_nuevo }}</strong></flux:text>
                                @if($cal->obs)
                                    <flux:text size="xs" class="text-slate-500">{{ $cal->obs }}</flux:text>
                                @endif
                                <flux:text size="xs" class="text-slate-400 block">{{ $cal->creator?->name ?? '—' }} · {{ $cal->created_at?->format('d/m/Y H:i') }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                </flux:card>
            @endif

            @if($evaluation->status === 'activa')
                <div class="space-y-2">
                    <flux:button href="{{ route('quality.feedback.create', $evaluation->id) }}" variant="primary" icon="chat-bubble-left-right" class="w-full">Agregar Feedback</flux:button>
                    <flux:button href="{{ route('quality.calibrations.create', $evaluation->id) }}" variant="warning" icon="scale" class="w-full">Calibrar</flux:button>
                </div>
            @endif
        </div>
    </div>
</div>
