<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VacacionesSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $csvFile = base_path('database/data/vacaciones.csv');

        if (!file_exists($csvFile)) {
            $this->command->error("El archivo CSV no existe: {$csvFile}");
            return;
        }

        $handle = fopen($csvFile, 'r');
        $header = fgetcsv($handle); // Skip header

        $count = 0;
        $vacacionesReasonId = 12; // ID para VACACIONES

        while (($data = fgetcsv($handle)) !== FALSE) {
            // CSV columns: nombre,fullname,id,inicio,final
            $employeeId = $data[2];
            $startDate = $data[3];
            $endDate = $data[4];

            if (empty($employeeId) || empty($startDate) || empty($endDate)) {
                continue;
            }
            // Insertar en schedule_exceptions
            DB::table('schedule_exceptions')->insert([
                'employee_id' => $employeeId,
                'absence_reason_code_id' => $vacacionesReasonId,
                'start_at' => Carbon::parse($startDate)->startOfDay(),
                'end_at' => Carbon::parse($endDate)->endOfDay(), // Usualmente las excepciones terminan al final del día
                'is_full_day' => true,
                'remarks' => 'Importación masiva de vacaciones (CSV)',
                'created_by' => 1, // Admin o Sistema
                'metadata' => json_encode([
                    'source' => 'vacaciones.csv',
                    'original_name' => $data[1] ?? $data[0]
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $count++;
        }

        fclose($handle);
        $this->command->info("Se han importado {$count} registros de vacaciones.");
    }
}