<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\AgentStateTransition;
use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ImportUccxTransitionsAction
{
    public function __construct(
        private readonly EmployeeLookupRepositoryInterface $employeeLookup,
    ) {
        $this->employeeLookup->warmup();
    }

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

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $rowCount++;

                // Skip empty or malformed rows
                if (count($header) !== count($data)) {
                    Log::warning("Fila {$rowCount} malformada en {$filePath}. Columnas esperadas: ".count($header).', encontradas: '.count($data));

                    continue;
                }

                $row = array_combine($header, $data);

                try {
                    $this->persistRecord($row);
                    $importedCount++;
                } catch (\Exception $e) {
                    Log::warning("Error procesando transición fila {$rowCount} en {$filePath}: ".$e->getMessage());

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
            Log::error("Error masivo importando transiciones {$filePath}: ".$e->getMessage());
            throw $e;
        } finally {
            fclose($handle);
        }

        return $importedCount;
    }

    private function persistRecord(array $row): void
    {
        $loginId = $row['agent_login_id'];
        $transitionTime = CarbonImmutable::parse($row['transition_time']);
        $employeeId = $this->employeeLookup->resolve(loginId: $loginId);

        AgentStateTransition::updateOrCreate(
            [
                'agent_login_id' => $loginId,
                'transition_time' => $transitionTime,
                'agent_state' => $row['agent_state'],
            ],
            [
                'employee_id' => $employeeId,
                'reason_code' => $row['reason_code'] ?: null,
                'duration' => (int) ($row['duration'] ?: 0),
            ]
        );
    }
}
