<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\AgentCallPerformance;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ImportUccxPerformanceAction
{
    /** @var array<string, int> */
    private array $employeeCache = [];

    public function execute(string $filePath): int
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("El archivo no existe: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            throw new \RuntimeException("No se pudo abrir el archivo: {$filePath}");
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return 0;
        }

        $importedCount = 0;
        $rowCount = 0;
        $this->primeCaches();

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $rowCount++;

                if (count($header) !== count($data)) {
                    Log::warning("Fila performance {$rowCount} malformada en {$filePath}.");

                    continue;
                }

                $row = array_combine($header, $data);

                try {
                    $this->persistRecord($row);
                    $importedCount++;
                } catch (\Exception $e) {
                    Log::warning("Error procesando performance fila {$rowCount} en {$filePath}: ".$e->getMessage());

                    continue;
                }

                if ($importedCount % 500 === 0) {
                    DB::commit();
                    DB::beginTransaction();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error masivo importando performance {$filePath}: ".$e->getMessage());
            throw $e;
        } finally {
            fclose($handle);
        }

        return $importedCount;
    }

    private function primeCaches(): void
    {
        $this->employeeCache = Employee::whereNotNull('username')->pluck('id', 'username')->toArray();
    }

    private function persistRecord(array $row): void
    {
        $loginId = $row['ID_de_conexión_del_agente'];
        $startTime = CarbonImmutable::parse($row['hora_de_inicio_de_llamada']);
        $employeeId = $this->employeeCache[$loginId] ?? null;

        AgentCallPerformance::updateOrCreate(
            [
                'agent_login_id' => $loginId,
                'start_time' => $startTime,
            ],
            [
                'employee_id' => $employeeId,
                'agent_ext' => $row['agent_ext'] ?: null,
                'end_time' => CarbonImmutable::parse($row['hora_de_fin_de_llamada']),
                'total_duration' => (int) ($row['duración_de_llamada'] ?: 0),
                'talk_time' => (int) ($row['tiempo_de_conversación'] ?: 0),
                'hold_time' => (int) ($row['tiempo_en_espera'] ?: 0),
                'work_time' => (int) ($row['tiempo_de_cierre'] ?: 0),
                'phone_number' => $row['número_llamado'] ?: null,
                'ani' => $row['ani_de_llamada'] ?: null,
                'csq_name' => $row['llamada_dirigida_por_csq'] ?: null,
                'call_skill' => $row['call_skill'] ?: null,
                'call_type' => $row['call_type'] ?: null,
                'raw_agent_name' => $row['nombre_del_agente'] ?: null,
            ]
        );
    }
}
