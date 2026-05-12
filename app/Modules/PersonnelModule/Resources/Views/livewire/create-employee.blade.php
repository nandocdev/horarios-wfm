<form wire:submit="create" class="space-y-6">
    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Información Personal</flux:heading>
        </div>

        <div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:input label="Número de empleado *" wire:model="employee_number" placeholder="EMP001" />
                <flux:input label="Nombre de usuario *" wire:model="username" placeholder="j.perez" />

                <flux:select label="Usuario *" wire:model="user_id" placeholder="Seleccionar usuario">
                    @foreach($selectOptions['users'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input label="Nombre *" wire:model="first_name" placeholder="Juan" />
                <flux:input label="Apellido *" wire:model="last_name" placeholder="Pérez" />

                <flux:input label="Email *" wire:model="email" type="email" placeholder="juan.perez@empresa.com" />

                <flux:input label="Teléfono" wire:model="phone" placeholder="+56912345678" />
                <flux:input label="Teléfono móvil" wire:model="mobile_phone" placeholder="+56987654321" />

                <flux:input label="Fecha de nacimiento" wire:model="birth_date" type="date" />

                <flux:select label="Género" wire:model="gender" placeholder="Seleccionar género">
                    <flux:select.option value="M">Masculino</flux:select.option>
                    <flux:select.option value="F">Femenino</flux:select.option>
                    <flux:select.option value="O">Otro</flux:select.option>
                </flux:select>

                <flux:select label="Tipo de sangre" wire:model="blood_type" placeholder="Seleccionar tipo">
                    <flux:select.option value="A+">A+</flux:select.option>
                    <flux:select.option value="A-">A-</flux:select.option>
                    <flux:select.option value="B+">B+</flux:select.option>
                    <flux:select.option value="B-">B-</flux:select.option>
                    <flux:select.option value="AB+">AB+</flux:select.option>
                    <flux:select.option value="AB-">AB-</flux:select.option>
                    <flux:select.option value="O+">O+</flux:select.option>
                    <flux:select.option value="O-">O-</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Dirección</flux:heading>
        </div>

        <div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:select label="Provincia" wire:model.live="province_id" placeholder="Seleccionar provincia">
                    <flux:select.option value="">Seleccionar provincia</flux:select.option>
                    @foreach($selectOptions['provinces'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select label="Distrito" wire:model.live="district_id" placeholder="Seleccionar distrito">
                    <flux:select.option value="">Seleccionar distrito</flux:select.option>
                    @foreach($selectOptions['districts'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select label="Comuna *" wire:model="township_id" placeholder="Seleccionar comuna">
                    @foreach($selectOptions['townships'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:textarea label="Dirección" wire:model="address" rows="3" placeholder="Dirección completa" />
            </div>
        </div>
    </flux:card>

    <flux:card class="space-y-4">
        <div>
            <flux:heading size="md">Información Laboral</flux:heading>
        </div>

        <div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:select label="Departamento *" wire:model="department_id" placeholder="Seleccionar departamento">
                    @foreach($selectOptions['departments'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select label="Cargo *" wire:model="position_id" placeholder="Seleccionar cargo">
                    @foreach($selectOptions['positions'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select label="Estado laboral *" wire:model="employment_status_id" placeholder="Seleccionar estado">
                    @foreach($selectOptions['employment_statuses'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select label="Reporta a" wire:model="parent_id" placeholder="Seleccionar supervisor">
                    <flux:select.option value="">Sin supervisor</flux:select.option>
                    @foreach($selectOptions['employees'] as $id => $name)
                        <flux:select.option value="{{ $id }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input label="Fecha de contratación *" wire:model="hire_date" type="date" />
                <flux:input label="Salario" wire:model="salary" type="number" step="0.01" placeholder="50000.00" />

                <flux:checkbox label="Es gerente" wire:model="is_manager" />
                <flux:checkbox label="Activo" wire:model="is_active" />
            </div>
        </div>
    </flux:card>

    <div class="flex justify-end space-x-4">
        <flux:button href="{{ route('employees.index') }}" variant="outline" wire:navigate>
            Cancelar
        </flux:button>

        <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
            <span wire:loading.remove>Crear Empleado</span>
            <span wire:loading>Creando...</span>
        </flux:button>
    </div>
</form>
