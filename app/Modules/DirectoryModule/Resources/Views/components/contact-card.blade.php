@props(['unit' => null])

@if($unit)
    <div class="space-y-4">
        <div class="flex items-center gap-2">
            <flux:icon name="building-office" class="w-4 h-4 text-wfm-navy-700" />
            <h3 class="text-sm font-semibold text-wfm-navy-800 dark:text-white">Ficha de Contacto</h3>
        </div>

        @forelse($unit->services as $service)
            <div class="card-wfm overflow-hidden">
                <div class="px-3 py-2.5 bg-wfm-navy-800 dark:bg-wfm-navy-900 text-white flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold tracking-wide">SERVICIO: {{ $service->name }}</p>
                    @if($service->door_id)
                        <flux:badge size="sm" color="light" class="shrink-0">{{ $service->door_id }}</flux:badge>
                    @endif
                </div>

                <div class="p-3 space-y-3">
                    <div>
                        <p class="kpi-label text-[10px] uppercase tracking-wider border-b border-wfm-surface-border pb-1 mb-1.5">Ubicación y Localización</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs">
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Edificio / Torre</span>
                                <span class="font-medium text-wfm-navy-800 text-right">{{ $unit->building->name }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Nodo / Sector</span>
                                <span class="font-medium text-wfm-navy-800 text-right">{{ $unit->sector ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Nivel / Piso</span>
                                <span class="font-medium text-wfm-navy-800 text-right">{{ $unit->level ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Puerta / Consultorio</span>
                                <span class="font-medium text-wfm-navy-800 text-right">{{ $service->door_id ?: '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="kpi-label text-[10px] uppercase tracking-wider border-b border-wfm-surface-border pb-1 mb-1.5">Operación</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs">
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Horario de Atención</span>
                                <span class="font-medium text-wfm-navy-800 font-mono text-right">{{ $service->attention_hours }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Entrega Resultados</span>
                                <span class="font-medium text-wfm-navy-800 font-mono text-right">{{ $service->results_hours ?: 'N/A' }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="kpi-label text-[10px] uppercase tracking-wider border-b border-wfm-surface-border pb-1 mb-1.5">Contacto Principal del Servicio</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-1 text-xs">
                            <div class="flex justify-between gap-2 sm:block">
                                <span class="text-wfm-surface-muted">Rol</span>
                                <span class="font-medium text-wfm-navy-800 text-right sm:text-left">{{ $service->contact_role }}</span>
                            </div>
                            <div class="flex justify-between gap-2 sm:block">
                                <span class="text-wfm-surface-muted">Ext</span>
                                <span class="font-medium text-wfm-navy-800 font-mono text-right sm:text-left">{{ $service->contact_extension }}</span>
                            </div>
                            <div class="flex justify-between gap-2 sm:col-span-1 sm:block">
                                <span class="text-wfm-surface-muted">Correo</span>
                                @if($service->contact_email)
                                    <a href="mailto:{{ $service->contact_email }}" class="font-medium text-wfm-info hover:underline text-right sm:text-left break-all">{{ $service->contact_email }}</a>
                                @else
                                    <span class="text-wfm-surface-muted">N/A</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="kpi-label text-[10px] uppercase tracking-wider border-b border-wfm-surface-border pb-1 mb-1.5">Administración de Infraestructura (Metadatos del Edificio)</p>
                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Director Médico</span>
                                <span class="font-medium text-wfm-navy-800 text-right">{{ $unit->building->director_name ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Sub-Director Médico</span>
                                <span class="font-medium text-wfm-navy-800 text-right">{{ $unit->building->subdirector_name ?: '—' }}</span>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-wfm-surface-muted">Administrador / Encargado</span>
                                <span class="font-medium text-wfm-navy-800 text-right">{{ $unit->building->administrator_name ?: '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card-wfm p-3 text-xs text-wfm-surface-muted">Sin servicios registrados en esta unidad.</div>
        @endforelse
    </div>
@endif