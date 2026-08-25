<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Personnel;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class EmployeeRelationsTest extends TestCase
{
    public function test_employee_skills_relationship_is_defined(): void
    {
        $relation = (new Employee)->employeeSkills();

        $this->assertInstanceOf(HasMany::class, $relation);
    }
}
