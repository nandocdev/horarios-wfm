<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PersonnelModule\Actions\ExportEmployeesAction;
use App\Modules\PersonnelModule\DTOs\EmployeeExportDTO;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EmployeeExportController extends Controller
{
    public function __invoke(Request $request, ExportEmployeesAction $action): Response
    {
        $this->authorize('export', Employee::class);

        $dto = EmployeeExportDTO::fromArray($request->all());

        return $action->execute($dto);
    }
}
