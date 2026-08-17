<div class="space-y-6">
    <x-wfm.page-header :title="$unit ? 'Editar Unidad' : 'Nueva Unidad'" :description="$unit ? 'ID: ' . $unit->id . ' | ' . $unit->display_name : 'Registra una unidad operativa o administrativa de la CSS.'" tour="directory.upsert" data-tour="directory-upsert-header">
        <x-slot:actions>
            <flux:button href="{{ route('directory.index') }}" wire:navigate variant="ghost" icon="arrow-left">Volver</flux:button>
        </x-slot:actions>
    </x-wfm.page-header>

    <form wire:submit="save" class="space-y-4">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            {{-- Columna izquierda: secciones 1 y 2 --}}
            <div class="lg:col-span-2 space-y-4">
                {{-- 1 · Ubicación Física: edificio, sector y jerarquía administrativa --}}
                <x-wfm.section title="1 · Ubicación Física (Infraestructura)" description="Edificio, sector y responsables administrativos (una sola vez por edificio)." data-tour="directory-upsert-section1">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:select label="Edificio / Torre" wire:model="form.building_id" wire:change="onBuildingChange"
                        placeholder="Seleccione un edificio o agregue uno nuevo...">
                        <flux:select.option value="0">+ Agregar nuevo edificio</flux:select.option>
                        @foreach($buildings as $building)
                            <flux:select.option value="{{ $building->id }}">{{ $building->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.building_id" />
                </div>

                @if($form->usesExistingBuilding())
                    <flux:input label="Nuevo Edificio / Torre" value="" placeholder="Edificio existente seleccionado" disabled />
                @else
                    <flux:input wire:model="form.new_building" label="Nuevo Edificio / Torre" list="building-suggestions"
                        placeholder="Ej. Hospital Pediátrico" maxlength="255" />
                    <datalist id="building-suggestions">
                        @foreach($buildings as $building)
                            <option value="{{ $building->name }}"></option>
                        @endforeach
                    </datalist>
                    <flux:error name="form.new_building" />
                @endif

                <div>
                    <flux:input wire:model="form.sector" label="Nodo / Sector (Nube)" list="sector-list" placeholder="Ej. Nodo Norte" maxlength="255" />
                    <datalist id="sector-list">
                        @foreach($sectorSuggestions as $value)
                            <option value="{{ $value }}"></option>
                        @endforeach
                    </datalist>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                    <flux:input wire:model="form.director_name" label="Director Médico" :disabled="$form->usesExistingBuilding()"
                        placeholder="Nombre completo" maxlength="255" />
                    <flux:error name="form.director_name" />
                </div>
                <div>
                    <flux:input wire:model="form.subdirector_name" label="Sub-Director Médico" :disabled="$form->usesExistingBuilding()"
                        placeholder="Nombre completo" maxlength="255" />
                    <flux:error name="form.subdirector_name" />
                </div>
                <div>
                    <flux:input wire:model="form.administrator_name" label="Administrador / Encargado" :disabled="$form->usesExistingBuilding()"
                        placeholder="Nombre completo" maxlength="255" />
                    <flux:error name="form.administrator_name" />
                </div>
            </div>
            @if($form->usesExistingBuilding())
                <flux:text size="sm" class="mt-2 text-wfm-success">Los responsables pertenecen al edificio seleccionado y se gestionan al crearlo.</flux:text>
            @endif
        </x-wfm.section>

        {{-- 2 · Nivel / Piso: seleccionar existente o crear uno nuevo --}}
        <x-wfm.section title="2 · Nivel / Piso" description="Los servicios y puntos de contacto dependen del piso seleccionado." data-tour="directory-upsert-section2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <flux:select label="Nivel / Piso" wire:model="form.level" wire:change="onLevelChange"
                        placeholder="Seleccione un piso o agregue uno nuevo...">
                        <flux:select.option value="0">+ Agregar nuevo piso</flux:select.option>
                        @foreach($levelSuggestions as $value)
                            <flux:select.option value="{{ $value }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.level" />
                </div>

                @if($form->usesExistingLevel())
                    <flux:input label="Nuevo Nivel / Piso" value="" placeholder="Piso existente seleccionado" disabled />
                @else
                    <flux:input wire:model="form.new_level" label="Nuevo Nivel / Piso" list="level-list" placeholder="Ej. Piso 3" maxlength="255" />
                    <datalist id="level-list">
                        @foreach($levelSuggestions as $value)
                            <option value="{{ $value }}"></option>
                        @endforeach
                    </datalist>
                    <flux:error name="form.new_level" />
                @endif
            </div>
        </x-wfm.section>
            </div>

            {{-- Columna derecha: sección 3 --}}
            <div class="lg:col-span-3 space-y-4">
                {{-- 3 · Servicios del Piso: puerta/consultorio, horarios y contacto --}}
                <x-wfm.section title="3 · Servicios del Piso" description="Cada puerta/consultorio corresponde a una especialidad con su contacto. Los campos se repiten por cada servicio." data-tour="directory-upsert-section3">
            <div class="space-y-3">
                @foreach($form->services as $index => $service)
                    <div class="card-wfm p-3 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="kpi-label text-[10px] uppercase tracking-wider">Servicio #{{ $index + 1 }}</span>
                            <flux:button type="button" variant="ghost" icon="trash" size="xs" wire:click="removeService({{ $index }})">Quitar</flux:button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <flux:input wire:model="form.services.{{ $index }}.door_id" label="Puerta / Consultorio" list="door-list" placeholder="Ej. C-201" maxlength="255" />
                            <flux:input wire:model="form.services.{{ $index }}.name" label="Nombre del Servicio" list="service-list" placeholder="Ej. Cardiología" maxlength="255" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <flux:input wire:model="form.services.{{ $index }}.attention_hours" label="Horario de Atención" placeholder="07:00 - 15:00" />
                            <flux:input wire:model="form.services.{{ $index }}.results_hours" label="Entrega de Resultados" placeholder="Opcional" />
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <flux:input wire:model="form.services.{{ $index }}.contact_role" label="Rol del Contacto" list="role-list" placeholder="Ej. Citas" maxlength="255" />
                            <flux:input wire:model="form.services.{{ $index }}.contact_extension" label="Extensión Telefónica" placeholder="Ej. 4210" maxlength="10" />
                            <flux:input wire:model="form.services.{{ $index }}.contact_email" label="Correo del Departamento" type="email" placeholder="citas-pediatria@css.gob.pa" maxlength="255" />
                        </div>
                        <flux:text size="sm">Prioriza buzones compartidos (ej. citas-pediatria@css.gob.pa), no correos personales.</flux:text>
                    </div>
                @endforeach

                <datalist id="door-list">
                    @foreach($doorSuggestions as $value)
                        <option value="{{ $value }}"></option>
                    @endforeach
                </datalist>
                <datalist id="service-list">
                    @foreach($serviceSuggestions as $value)
                        <option value="{{ $value }}"></option>
                    @endforeach
                </datalist>
                <datalist id="role-list">
                    @foreach($roleSuggestions as $value)
                        <option value="{{ $value }}"></option>
                    @endforeach
                </datalist>

                <flux:button type="button" variant="subtle" icon="plus" size="sm" wire:click="addService">Agregar Servicio</flux:button>
                <flux:error name="form.services" />
            </div>
        </x-wfm.section>
            </div>
        </div>

        <x-wfm.section data-tour="directory-upsert-save">
            <div class="flex items-center justify-between gap-4">
                <flux:switch wire:model="form.is_active" label="Unidad activa" />
                <div class="flex gap-2">
                    <flux:button href="{{ route('directory.index') }}" variant="ghost" wire:navigate>Cancelar</flux:button>
                    <flux:button type="submit" variant="primary" icon="check">
                        {{ $unit ? 'Guardar Cambios' : 'Crear Unidad' }}
                    </flux:button>
                </div>
            </div>
        </x-wfm.section>
    </form>
</div>