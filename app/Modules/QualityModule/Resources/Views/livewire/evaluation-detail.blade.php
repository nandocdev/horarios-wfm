<div class="space-y-6">
    <x-wfm.page-header :title="'Detalle de Evaluación — ' . ($evaluation->queue?->code ?? '')" :description="$evaluation->dteval?->format('d/m/Y') ?? ''" tour="quality.evaluation-detail" data-tour="quality-detail-header">
        <x-slot:actions>
            <flux:button href="{{ route('quality.evaluations.index') }}" variant="ghost" icon="arrow-left" wire:navigate>Volver</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <x-wfm.section title="Puntajes por Criterio" data-tour="quality-detail-scores">
                <div class="space-y-2">
                    @foreach($evaluation->scores as $score)
                        <div class="flex items-center justify-between p-2 bg-wfm-surface rounded">
                            <span class="text-xs text-wfm-navy-800 dark:text-white">{{ $score->criteriaVersion?->criterio_text ?? '—' }}</span>
                            <x-wfm.adherence-badge :value="$score->puntaje_obtenido" :target="$score->criteriaVersion?->puntaje ?? 1" size="xs" />
                        </div>
                    @endforeach
                </div>
                <div class="flex justify-between items-center pt-3 border-t border-wfm-surface-border mt-3">
                    <span class="text-sm font-semibold text-wfm-navy-800 dark:text-white">Score Total</span>
                    <x-wfm.adherence-badge :value="$evaluation->score ?? 0" target="60" />
                </div>
            </x-wfm.section>

            @if($evaluation->callobs)
                <x-wfm.section title="Observaciones">
                    <p class="text-xs text-wfm-navy-800 dark:text-white">{{ $evaluation->callobs }}</p>
                </x-wfm.section>
            @endif

            @if($evaluation->feedbacks->isNotEmpty())
                <x-wfm.section title="Feedback">
                    @foreach($evaluation->feedbacks as $feedback)
                        <div class="p-3 bg-wfm-surface rounded mb-2 last:mb-0">
                            <p class="text-xs">{{ $feedback->obsfeed }}</p>
                            <p class="text-[10px] text-wfm-surface-muted mt-1">{{ $feedback->creator?->name ?? '—' }} · {{ $feedback->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </x-wfm.section>
            @endif
        </div>

        <div class="space-y-4">
            <x-wfm.section title="Información">
                <div class="space-y-2">
                    <div>
                        <p class="kpi-label">Cola</p>
                        <p class="text-xs font-medium">{{ $evaluation->queue?->code }} — {{ $evaluation->queue?->name }}</p>
                    </div>
                    <div>
                        <p class="kpi-label">Empleado ID</p>
                        <p class="text-xs">{{ $evaluation->employee_id }}</p>
                    </div>
                    <div>
                        <p class="kpi-label">Evaluador ID</p>
                        <p class="text-xs">{{ $evaluation->evaluator_id }}</p>
                    </div>
                    <div>
                        <p class="kpi-label">Estado</p>
                        <x-wfm.agent-status :status="$evaluation->status === 'activa' ? 'available' : 'offline'" :label="$evaluation->status" size="xs" />
                    </div>
                    @if($evaluation->clip_id)
                        <div>
                            <p class="kpi-label">Clip ID</p>
                            <p class="text-xs">{{ $evaluation->clip_id }}</p>
                        </div>
                    @endif
                </div>
            </x-wfm.section>

            @if($evaluation->calibrations->isNotEmpty())
                <x-wfm.section title="Calibraciones">
                    @foreach($evaluation->calibrations as $cal)
                        <div class="p-3 bg-wfm-surface rounded mb-2 last:mb-0 space-y-1">
                            <p class="text-xs">{{ $cal->score_anterior }} → <strong>{{ $cal->score_nuevo }}</strong></p>
                            @if($cal->obs)
                                <p class="text-[10px] text-wfm-surface-muted">{{ $cal->obs }}</p>
                            @endif
                            <p class="text-[10px] text-wfm-surface-muted">{{ $cal->creator?->name ?? '—' }} · {{ $cal->created_at?->format('d/m/Y H:i') }}</p>
                        </div>
                    @endforeach
                </x-wfm.section>
            @endif

            @if($evaluation->status === 'activa')
                <div data-tour="quality-detail-actions" class="space-y-2">
                    <flux:button href="{{ route('quality.feedback.create', $evaluation->id) }}" variant="primary" icon="chat-bubble-left-right" class="w-full" wire:navigate>Agregar Feedback</flux:button>
                    <flux:button href="{{ route('quality.calibrations.create', $evaluation->id) }}" variant="warning" icon="scale" class="w-full" wire:navigate>Calibrar</flux:button>
                </div>
            @endif
        </div>
    </div>
</div>
