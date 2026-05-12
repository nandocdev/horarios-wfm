<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = database_path('data/employees.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("Archivo CSV de empleados no encontrado: {$csvPath}");

            return;
        }

        $employees = $this->readCsv($csvPath);

        DB::transaction(function () use ($employees) {
            // Desactivar FK checks para permitir carga fuera de orden jerárquico
            DB::statement("SET session_replication_role = 'replica';");

            foreach ($employees as $row) {
                // $this->command->info("Seeding employee ID: " . $row['id']);
                DB::table('employees')->updateOrInsert(
                    ['id' => $row['id']],
                    [
                        'employee_number' => $row['employee_number'],
                        'username' => $row['username'],
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'email' => strtolower($row['email']),
                        'birth_date' => $this->nullOrValue($row['birth_date']),
                        'gender' => $this->nullOrValue($row['gender']),
                        'blood_type' => $this->nullOrValue($row['blood_type']),
                        'phone' => $this->nullOrValue($row['phone']),
                        'mobile_phone' => $this->nullOrValue($row['mobile_phone']),
                        'address' => $this->nullOrValue($row['address']),
                        'township_id' => $this->nullOrValue($row['township_id']),
                        'department_id' => $this->nullOrValue($row['department_id']),
                        'position_id' => $this->nullOrValue($row['position_id']),
                        'employment_status_id' => $this->nullOrValue($row['employment_status_id']),
                        'parent_id' => $this->nullOrValue($row['parent_id']),
                        'user_id' => DB::table('users')->where('email', strtolower($row['email']))->value('id'),
                        'hire_date' => $this->nullOrValue($row['hire_date']),
                        'salary' => $this->nullOrValue($row['salary']) ?: 0,
                        'is_active' => $row['is_active'] === 'true' || $row['is_active'] === '1',
                        'is_manager' => $row['is_manager'] === 'true' || $row['is_manager'] === '1',
                        'metadata' => $row['metadata'] ? $row['metadata'] : null, // Ya es un string JSON del CSV
                        'created_at' => $row['created_at'] ?: now(),
                        'updated_at' => $row['updated_at'] ?: now(),
                    ]
                );
            }
        });

        $this->command->info('Empleados sembrados exitosamente.');
    }

    private function nullOrValue($value)
    {
        return $value === '' ? null : $value;
    }

    private function readCsv(string $filePath): array
    {
        $data = [];
        $header = null;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if (! $header) {
                    $header = $row;
                } else {
                    if (count($header) === count($row)) {
                        $data[] = array_combine($header, $row);
                    }
                }
            }
            fclose($handle);
        }

        return $data;
    }
}
