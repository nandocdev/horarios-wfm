<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\GeoModule\Models\District;
use App\Modules\GeoModule\Models\Province;
use App\Modules\GeoModule\Models\Township;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PanamaGeographySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedProvinces();
            $this->seedDistricts();
            $this->seedTownships();
        });
    }

    private function seedProvinces(): void
    {
        $csvPath = database_path('data/provincias.csv');

        if (! file_exists($csvPath)) {
            throw new \Exception("Archivo CSV de provincias no encontrado: {$csvPath}");
        }

        $provinces = $this->readCsv($csvPath);

        foreach ($provinces as $province) {
            Province::firstOrCreate([
                'name' => $province['name'],
            ]);
        }
    }

    private function seedDistricts(): void
    {
        $csvPath = database_path('data/distritos.csv');

        if (! file_exists($csvPath)) {
            throw new \Exception("Archivo CSV de distritos no encontrado: {$csvPath}");
        }

        $districts = $this->readCsv($csvPath);

        foreach ($districts as $district) {
            District::firstOrCreate([
                'province_id' => $district['province_id'],
                'name' => $district['name'],
            ]);
        }
    }

    private function seedTownships(): void
    {
        $csvPath = database_path('data/corregimientos.csv');

        if (! file_exists($csvPath)) {
            throw new \Exception("Archivo CSV de corregimientos no encontrado: {$csvPath}");
        }

        $townships = $this->readCsv($csvPath);

        foreach ($townships as $township) {
            Township::firstOrCreate([
                'district_id' => $township['district_id'],
                'name' => trim($township['name']),
            ]);
        }
    }

    /**
     * Lee un archivo CSV y devuelve un array de filas.
     */
    private function readCsv(string $filePath): array
    {
        $data = [];
        $header = null;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                if (! $header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }

        return $data;
    }
}
