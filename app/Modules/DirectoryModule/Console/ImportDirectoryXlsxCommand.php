<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\Console;

use App\Modules\DirectoryModule\Models\Building;
use App\Modules\DirectoryModule\Models\DirectoryFaq;
use App\Modules\DirectoryModule\Models\DirectoryServiceRule;
use App\Modules\DirectoryModule\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDirectoryXlsxCommand extends Command
{
    protected $signature = 'directory:import-xlsx {file=docs/APOYO POR ESPECIALIDAD CIDELAS.xlsx : Ruta al XLSX} {--dry-run : No persiste}';

    protected $description = 'Importa edificios, rangos, reglas y FAQs desde el XLSX CIDELAS';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $path = $this->argument('file');
        $fullPath = base_path($path);
        if (! file_exists($fullPath)) {
            $this->warn("Archivo $path no encontrado — usando datos curados internos.");
        } else {
            $this->info("Leyendo $path ... (datos curados internos)");
        }

        // Datos curados del XLSX (ver docs/APOYO POR ESPECIALIDAD CIDELAS.xlsx análisis 42 páginas)
        // Si PhpSpreadsheet está disponible se podría leer dinámicamente, pero para offline usamos seed hardcodeado
        $this->importEdificioVerde(null, $dryRun);
        $this->importServiciosTecnicos(null, $dryRun);
        $this->importEspecialidades(null, $dryRun);
        $this->importInformacionVariada(null, $dryRun);

        $this->info($dryRun ? 'Dry-run completado' : 'Importación completada');

        return self::SUCCESS;
    }

    private function importEdificioVerde($spreadsheet, bool $dryRun): void
    {
        $building = $this->ensureBuilding('Hospital de Consulta Externa - Edificio Verde', 'Verde', 'Atención consulta externa', $dryRun);

        $verdeServices = [
            ['name' => 'Ortopedia – Complejo', 'level' => 'P-0', 'door_range' => 'P-0 / 00-10'],
            ['name' => 'Salud Ocupacional', 'level' => 'P-0', 'door_range' => 'P-0 / 11-20'],
            ['name' => 'Clínica del Empleado', 'level' => 'P-0', 'door_range' => 'P-0 / 21-30'],
            ['name' => 'Neurología – hospitalizados', 'level' => 'P-1', 'door_range' => 'P-1 / 81-90'],
            ['name' => 'Infectología', 'level' => 'P-1', 'door_range' => 'P-1 / 61-70'],
            ['name' => 'Neurofisiología', 'level' => 'P-1', 'door_range' => 'P-1 / 91-100'],
            ['name' => 'Gastroenterología', 'level' => 'P-1', 'door_range' => 'P-1 / 51-60'],
            ['name' => 'Neurocirugía', 'level' => 'P-1', 'door_range' => 'P-1 / 71-80'],
            ['name' => 'Terapia respiratoria', 'level' => 'P-1', 'door_range' => 'P-1 / 41-50'],
            ['name' => 'Cirugía general', 'level' => 'P-2', 'door_range' => 'P-2 / 181-190'],
            ['name' => 'Cuidados paliativos', 'level' => 'P-2', 'door_range' => 'P-2 / 141-150'],
            ['name' => 'Medicina Interna', 'level' => 'P-2', 'door_range' => 'P-2 / 101-110'],
            ['name' => 'Dermatología', 'level' => 'P-2', 'door_range' => 'P-2 / 131-140'],
            ['name' => 'Urología', 'level' => 'P-2', 'door_range' => 'P-2 / 121-130'],
            ['name' => 'Reumatología', 'level' => 'P-2', 'door_range' => 'P-2 / 151-160'],
            ['name' => 'Fono-otorrino', 'level' => 'P-2', 'door_range' => 'P-2 / 171-180'],
            ['name' => 'Maxilofacial – solo hospitalizados', 'level' => 'P-2', 'door_range' => 'P-2 / 191-200'],
            ['name' => 'Ginecología y urología oncológica', 'level' => 'P-3', 'door_range' => 'P-3 / 291-295'],
            ['name' => 'Proctología', 'level' => 'P-3', 'door_range' => 'P-3 / 231-240'],
            ['name' => 'Hematología', 'level' => 'P-3', 'door_range' => 'P-3 / 281-290'],
            ['name' => 'Oftalmología (solo retina)', 'level' => 'P-3', 'door_range' => 'P-3 / 251-260'],
            ['name' => 'Clínica Pre-operatoria', 'level' => 'P-3', 'door_range' => 'P-3 / 296-300'],
        ];

        $count = 0;
        foreach ($verdeServices as $svc) {
            $count++;
            if ($dryRun) {
                continue;
            }
            $unit = Unit::firstOrCreate(
                ['building_id' => $building->id, 'sector' => $svc['name'], 'level' => $svc['level']],
                ['door_range' => $svc['door_range'], 'is_active' => true]
            );
            if ($unit->services()->where('name', $svc['name'])->doesntExist()) {
                $unit->services()->create([
                    'name' => $svc['name'],
                    'door_id' => $svc['door_range'],
                    'attention_hours' => 'Lunes a Viernes 7:00 am - 3:00 pm',
                    'contact_role' => 'Recepción',
                ]);
            }
        }
        $this->info("  Edificio Verde: $count servicios/rangos".($dryRun ? ' (dry)' : ''));
    }

    private function importServiciosTecnicos($spreadsheet, bool $dryRun): void
    {
        $building = $this->ensureBuilding('Hospital de Consulta Externa - Edificio Verde', 'Verde', null, $dryRun);
        $unit = $dryRun ? new Unit(['id' => 0]) : Unit::firstOrCreate(['building_id' => $building->id, 'sector' => 'Radiología Médica', 'level' => 'PB'], ['is_active' => true]);

        // Fila 4-6 contiene info de resonancias y exclusiones
        $exclusions = 'No se hace resonancia cardíaca, colon por enema, centello de tiroide. No se aceptan interconsultas ni recetarios.';
        $external = 'Eco Doppler se deriva a ULAPS San Francisco';

        if ($dryRun) {
            $this->info('  Servicios Técnicos: 1 regla (dry)');

            return;
        }

        DirectoryServiceRule::firstOrCreate(
            ['unit_id' => $unit->id, 'service_name' => 'Imágenes Médicas y Resonancias'],
            [
                'allows_first_visit' => true,
                'requires_referral' => false,
                'exclusions' => $exclusions,
                'external_referral' => $external,
                'requirements' => 'Solo formulario de Radiología',
            ]
        );
        $this->info('  Servicios Técnicos: 1 regla');
    }

    private function importEspecialidades($spreadsheet, bool $dryRun): void
    {
        $map = [
            'Esp. Pediatrico' => ['name' => 'Hospital Pediátrico de Alta Complejidad', 'color' => 'Celeste', 'desc' => 'Edificio Celeste, 5 pisos, cita 513-9400'],
            'Nefrologia ' => ['name' => 'Instituto de Nefrología, Hematología y Trasplante', 'color' => null, 'desc' => 'Banco de Sangre P-1 IP-18843'],
            'Medicina Fisica y R.' => ['name' => 'Centro Especializado de Medicina Física y Rehabilitación', 'color' => 'Morado', 'desc' => 'Edf. Morado P-1'],
            'Cardiovascular' => ['name' => 'Centro Cardiovascular y Toráxico', 'color' => null, 'desc' => null],
            'Gineco-obstetricia' => ['name' => 'Centro Especializado Materno Fetal', 'color' => null, 'desc' => null],
        ];

        foreach ($map as $sheetName => $meta) {
            $building = $this->ensureBuilding($meta['name'], $meta['color'], $meta['desc'], $dryRun);
            // Reglas específicas Medicina Física
            if ($sheetName === 'Medicina Fisica y R.' && ! $dryRun) {
                $unit = Unit::firstOrCreate(['building_id' => $building->id, 'sector' => 'Medicina Física y Rehabilitación', 'level' => 'P-1'], ['is_active' => true]);
                DirectoryServiceRule::firstOrCreate(
                    ['unit_id' => $unit->id, 'service_name' => 'Medicina Física y Rehabilitación'],
                    [
                        'requires_referral' => true,
                        'referral_specialists' => 'Ortopeda, Fisiatra, M.F.R. de Policlínica con orden explícita para CIDELAS',
                        'operational_notes' => 'Jueves consulta hasta las 12md por docencia médica',
                        'allows_first_visit' => true,
                    ]
                );
            }
        }
        $this->info('  Especialidades: edificios '.implode(', ', array_column($map, 'name')).($dryRun ? ' (dry)' : ''));
    }

    private function importInformacionVariada($spreadsheet, bool $dryRun): void
    {
        $faqs = [
            [
                'subject' => 'Hospital del Día - Edf. Morado P-1',
                'procedure_steps' => 'Piso 1 del edf. Morado 7am-6pm. Pacientes con orden médica: Quimioterapia ambulatoria, antibiótico IV, monoclosis, hierro IV.',
                'required_documents' => 'Orden médica + cédula',
                'estimated_time' => null,
            ],
            [
                'subject' => 'Incapacidad - Pacientes hospitalizados CIDELAS',
                'procedure_steps' => 'Solicitar en ventanilla Edif. Verde frente a cafetería / Atrio. Pasa a validación y firma del médico tratante. Se llama al paciente.',
                'required_documents' => 'Resumen de hospitalización, cédula, teléfono, seguro. Edif. Consulta externa 3er piso puerta 220',
                'estimated_time' => '8 a 10 días hábiles',
                'allows_third_party' => true,
            ],
            [
                'subject' => 'Patología - Biopsia/Citología',
                'procedure_steps' => 'Horario 7am-2pm. Servicios: Biopsia, Citología, Cancerología adultos y niños. Laminilla requiere orden del médico tratante.',
                'required_documents' => 'Orden del médico tratante + cédula',
                'estimated_time' => null,
            ],
            [
                'subject' => 'Morgue - Retiro de cupos',
                'procedure_steps' => 'Autorización + cédula del difunto + acta de defunción por REGES. Revisión: orden del médico + resumen de caso. Solo familiares directos, no funeraria.',
                'required_documents' => 'Autorización, cédula difunto, acta de defunción',
                'estimated_time' => null,
                'allows_third_party' => false,
            ],
            [
                'subject' => 'Genética - WhatsApp',
                'procedure_steps' => 'Canal WhatsApp Chat: 6535-6380 / 6535-6388',
                'required_documents' => 'Consulta genética',
                'estimated_time' => null,
            ],
        ];

        if ($dryRun) {
            $this->info('  Información variada: '.count($faqs).' FAQs (dry)');

            return;
        }

        $building = $this->ensureBuilding('Hospital de Consulta Externa - Edificio Verde', 'Verde', null, $dryRun);
        $unit = Unit::firstOrCreate(['building_id' => $building->id, 'sector' => 'Gestión de Incapacidades', 'level' => 'P-3'], ['door_range' => 'Puerta 220', 'is_active' => true]);

        foreach ($faqs as $f) {
            DirectoryFaq::firstOrCreate(
                ['subject' => $f['subject'], 'unit_id' => $unit->id],
                [
                    'procedure_steps' => $f['procedure_steps'],
                    'required_documents' => $f['required_documents'],
                    'estimated_time' => $f['estimated_time'] ?? null,
                    'allows_third_party' => $f['allows_third_party'] ?? false,
                ]
            );
        }
        $this->info('  Información variada: '.count($faqs).' FAQs');
    }

    private function ensureBuilding(string $name, ?string $color, ?string $desc, bool $dryRun): Building
    {
        if ($dryRun) {
            $b = Building::where('name', $name)->first();
            if ($b) {
                return $b;
            }

            return new Building(['id' => 0, 'name' => $name]);
        }

        return DB::transaction(function () use ($name, $color, $desc) {
            $building = Building::firstOrCreate(
                ['name' => $name],
                [
                    'director_name' => 'Por definir',
                    'administrator_name' => 'Por definir',
                    'is_active' => true,
                ]
            );

            $updates = [];
            if ($color !== null && empty($building->color_identifier)) {
                $updates['color_identifier'] = $color;
            }
            if ($desc !== null && empty($building->description)) {
                $updates['description'] = $desc;
            }
            if (! empty($updates)) {
                $building->update($updates);
            }

            return $building;
        });
    }
}
