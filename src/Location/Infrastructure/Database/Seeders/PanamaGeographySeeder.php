<?php

declare(strict_types=1);

namespace Src\Location\Infrastructure\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Src\Location\Infrastructure\Persistence\Models\District;
use Src\Location\Infrastructure\Persistence\Models\Province;
use Src\Location\Infrastructure\Persistence\Models\Township;

class PanamaGeographySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedProvinces();
            $this->seedDistricts();
            $this->seedTownships();
        });
    }

    private function seedProvinces(): void
    {
        $csvPath = database_path('data/provincias.csv');

        if (! file_exists($csvPath)) {
            throw new \RuntimeException("Archivo CSV de provincias no encontrado: {$csvPath}");
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
            throw new \RuntimeException("Archivo CSV de distritos no encontrado: {$csvPath}");
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
            throw new \RuntimeException("Archivo CSV de corregimientos no encontrado: {$csvPath}");
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
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $filePath): array
    {
        $data = [];
        $header = null;

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            return $data;
        }

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if ($header === null) {
                $header = $row;
            } else {
                $data[] = array_combine($header, $row);
            }
        }

        fclose($handle);

        return $data;
    }
}
