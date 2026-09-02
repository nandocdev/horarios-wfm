<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Console;

use App\Modules\DirectoryModule\Models\Building;
use App\Modules\DirectoryModule\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDirectoryPdfCommand extends Command
{
    protected $signature = 'directory:import-pdf {file=docs/directorio_contacts.csv : CSV curado del PDF (Building,Level,Sector,Service,Door_id,Role,CISCO,Directo,Movil)} {--dry-run}';

    protected $description = 'Importa directorio telefónico desde CSV curado del PDF (460 filas)';

    public function handle(): int
    {
        $path = base_path($this->argument('file'));

        if (! file_exists($path)) {
            $this->error("CSV no encontrado: $path. Genera docs/directorio_contacts.csv desde el PDF OCR curado.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $rows = array_map('str_getcsv', file($path));
        $header = array_map('trim', array_shift($rows));
        $count = 0;
        $skipped = 0;

        foreach ($rows as $r) {
            if (count($r) < count($header)) {
                $skipped++;

                continue;
            }
            $data = array_combine($header, $r);
            $buildingName = trim($data['Building'] ?? $data['Edificio'] ?? '');
            $level = trim($data['Level'] ?? $data['PISO'] ?? $data['level'] ?? '');
            $sector = trim($data['Sector'] ?? $data['Departamento'] ?? '');
            $service = trim($data['Service'] ?? $data['Nombre'] ?? $data['NOMBRE'] ?? '');
            $role = trim($data['Role'] ?? $data['CARGO'] ?? '');
            $door = trim($data['Door_id'] ?? $data['door_id'] ?? '');
            $cisco = trim($data['CISCO'] ?? '');
            $directo = trim($data['Directo'] ?? $data['TELEFONO'] ?? '');
            $movil = trim($data['Movil'] ?? $data['MOVIL'] ?? '');

            if ($buildingName === '' || $service === '') {
                $skipped++;

                continue;
            }

            if ($dryRun) {
                $count++;

                continue;
            }

            DB::transaction(function () use ($buildingName, $level, $sector, $service, $role, $door, $cisco, $directo, $movil) {
                $building = Building::firstOrCreate(
                    ['name' => $buildingName],
                    ['director_name' => 'Por definir', 'administrator_name' => 'Por definir', 'is_active' => true]
                );

                $unit = Unit::firstOrCreate(
                    ['building_id' => $building->id, 'sector' => $sector ?: null, 'level' => $level ?: null],
                    ['is_active' => true]
                );

                // is_person heurística: si CARGO es CONSULTORIO/SALA/RECEPCION/ESTACION -> sala
                $isPerson = ! preg_match('/^(CONSULTORIO|RECEPCION|ESTACION|SALA|FARMACIA|ALMACEN)/i', $service);

                $svc = $unit->services()->firstOrCreate(
                    ['name' => $service],
                    [
                        'is_person' => $isPerson,
                        'door_id' => $door ?: null,
                        'attention_hours' => 'Lunes a Viernes 7:00 am - 3:00 pm',
                        'contact_role' => $role ?: null,
                        'contact_extension' => $cisco ?: null,
                    ]
                );

                $phones = [];
                foreach (['CISCO' => $cisco, 'DIRECTO' => $directo, 'MOVIL' => $movil] as $type => $num) {
                    $san = preg_replace('/[^0-9\-]/', '', $num);
                    if ($san !== '' && $san !== null) {
                        $phones[] = ['number' => $san, 'type' => $type, 'description' => null];
                    }
                }
                foreach ($phones as $ph) {
                    $svc->phones()->firstOrCreate(['number' => $ph['number']], $ph);
                }
            });

            $count++;
        }

        $this->info(($dryRun ? '[dry] ' : '')."Importados $count servicios, omitidos $skipped");

        return self::SUCCESS;
    }
}
