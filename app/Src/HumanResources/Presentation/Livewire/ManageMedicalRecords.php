<?php

declare(strict_types=1);

namespace App\Src\HumanResources\Presentation\Livewire;

use App\Modules\PersonnelModule\Models\Employee;
use App\Src\HumanResources\Infrastructure\Persistence\EloquentEmployeeDisease;
use App\Src\HumanResources\Infrastructure\Persistence\EloquentEmployeeDisability;
use App\Src\HumanResources\Infrastructure\Persistence\EloquentEmployeeDependent;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Legajo Médico')]
class ManageMedicalRecords extends Component
{
    public Employee $employee;
    public string $diseaseNotes = '';
    public int $diseaseTypeId;
    public string $dependentName = '';
    public string $dependentRelationship = '';
    public string $dependentBirthDate = '';

    public function mount(Employee $employee): void
    {
        $this->authorize('view', $employee);
        $this->employee = $employee->load(['diseases', 'disabilities', 'dependents']);
    }

    public function addDisease(): void
    {
        $this->validate([
            'diseaseTypeId' => ['required', 'integer', 'exists:disease_types,id'],
            'diseaseNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        EloquentEmployeeDisease::create([
            'employee_id' => $this->employee->id,
            'disease_type_id' => $this->diseaseTypeId,
            'notes' => $this->diseaseNotes,
        ]);

        $this->reset(['diseaseTypeId', 'diseaseNotes']);
        $this->employee->refresh();
        toast('Enfermedad registrada en el legajo médico.');
    }

    public function addDependent(): void
    {
        $this->validate([
            'dependentName' => ['required', 'string', 'max:255'],
            'dependentRelationship' => ['required', 'string', 'max:50'],
            'dependentBirthDate' => ['nullable', 'date'],
        ]);

        EloquentEmployeeDependent::create([
            'employee_id' => $this->employee->id,
            'name' => $this->dependentName,
            'relationship' => $this->dependentRelationship,
            'birth_date' => $this->dependentBirthDate ?: null,
        ]);

        $this->reset(['dependentName', 'dependentRelationship', 'dependentBirthDate']);
        $this->employee->refresh();
        toast('Dependiente registrado correctamente.');
    }

    public function render()
    {
        $diseaseTypes = \App\Modules\PersonnelModule\Models\DiseaseType::all();

        return view('hr::livewire.manage-medical-records', [
            'diseaseTypes' => $diseaseTypes,
        ]);
    }
}
