<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Livewire\MyDay;

use App\Modules\PersonnelModule\Models\Employee;
use App\Modules\WfmModule\Services\MyDayService;
use Livewire\Attributes\On;
use Livewire\Component;

class ScheduleComplianceWidget extends Component
{
    public int $employeeId;

    public string $selectedDate;

    #[On('my-day-date-changed')]
    public function updateDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function render(MyDayService $service)
    {
        $employee = Employee::find($this->employeeId);
        $data = $service->getEmployeeData($employee, $this->selectedDate);

        return view('wfm::livewire.my-day.schedule-compliance-widget', [
            'd' => $data,
        ]);
    }
}
