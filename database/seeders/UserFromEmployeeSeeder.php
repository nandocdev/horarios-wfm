<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserFromEmployeeSeeder extends Seeder
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
        $password = Hash::make('password');

        DB::transaction(function () use ($employees, $password) {
            foreach ($employees as $row) {
                // Crear o actualizar el usuario basado en el email del empleado
                DB::table('users')->updateOrInsert(
                    ['email' => strtolower($row['email'])],
                    [
                        'name' => $row['first_name'].' '.$row['last_name'],
                        'password' => $password,
                        'is_active' => $row['is_active'] === 'true' || $row['is_active'] === '1',
                        'force_password_change' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        $this->command->info('Usuarios (basados en empleados) creados exitosamente.');
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
