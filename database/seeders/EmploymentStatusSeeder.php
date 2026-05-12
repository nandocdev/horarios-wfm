<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmploymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = database_path('data/employment_statuses.csv');

        if (! file_exists($csvPath)) {
            $this->command->warn("Archivo CSV de estados laborales no encontrado: {$csvPath}");

            return;
        }

        $statuses = $this->readCsv($csvPath);

        foreach ($statuses as $status) {
            DB::table('employment_statuses')->updateOrInsert(
                ['id' => $status['id']],
                ['name' => $status['name']]
            );
        }

        $this->command->info('Estados laborales sembrados exitosamente.');
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
