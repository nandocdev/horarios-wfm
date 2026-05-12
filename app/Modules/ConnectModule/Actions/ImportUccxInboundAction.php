<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\DTOs\UccxCallDataDTO;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Infrastructure\Cisco\CiscoFinesseClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ImportUccxInboundAction {
    /** @var array<string, int> */
    private array $queueCache = [];

    /** @var array<string, int> */
    private array $employeeCache = [];

    public function execute(string $filePath): int {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("El archivo no existe: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException("No se pudo abrir el archivo: {$filePath}");
        }

        // Leer cabecera
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);

            return 0;
        }

        $importedCount = 0;
        $rowCount = 0;

        // Cargar caches iniciales
        $this->primeCaches();

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($handle)) !== false) {
                $rowCount++;

                if (count($header) !== count($data)) {
                    Log::warning("Fila inbound {$rowCount} malformada en {$filePath}.");

                    continue;
                }

                $row = array_combine($header, $data);

                try {
                    $dto = UccxCallDataDTO::fromCsvRow($row);
                    $this->persistRecord($dto);
                    $importedCount++;
                } catch (\Exception $e) {
                    Log::warning("Error procesando fila {$rowCount} en {$filePath}: " . $e->getMessage());

                    continue;
                }

                // Chunking transactions for large files
                if ($importedCount % 500 === 0) {
                    DB::commit();
                    DB::beginTransaction();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error masivo importando UCCX {$filePath}: " . $e->getMessage());
            throw $e;
        } finally {
            fclose($handle);
        }

        return $importedCount;
    }

    private function primeCaches(): void {
        $this->queueCache = CallQueue::pluck('id', 'name')->toArray();

        try {
            $finesseClient = app(CiscoFinesseClient::class);
            $usersResponse = $finesseClient->getAllUsers();
            $ciscoUsers = $usersResponse['User'] ?? [];

            // Normalizar si la respuesta de Cisco es un solo objeto
            if (isset($ciscoUsers['loginId'])) {
                $ciscoUsers = [$ciscoUsers];
            }

            // Obtener todos los empleados con su username y nombres
            $employees = Employee::whereNotNull('username')->get(['id', 'username', 'first_name', 'last_name']);

            // Mapas locales para cruce rápido
            $usernameToId = $employees->mapWithKeys(fn($e) => [strtolower($e->username) => $e->id])->toArray();
            $nameToId = $employees->mapWithKeys(function ($e) {
                $fullName = strtolower(trim($e->first_name . ' ' . $e->last_name));
                return [$fullName => $e->id];
            })->filter(fn($id, $name) => !empty($name))->toArray();

            foreach ($ciscoUsers as $u) {
                $loginId = strtolower((string) ($u['loginId'] ?? $u['loginName'] ?? ''));
                $firstName = is_array($u['firstName'] ?? '') ? '' : ($u['firstName'] ?? '');
                $lastName = is_array($u['lastName'] ?? '') ? '' : ($u['lastName'] ?? '');
                $ciscoName = strtolower(trim($firstName . ' ' . $lastName));

                $empId = $usernameToId[$loginId] ?? $nameToId[$ciscoName] ?? null;

                if ($empId) {
                    $this->employeeCache[$loginId] = $empId;
                    if ($ciscoName) {
                        $this->employeeCache[$ciscoName] = $empId;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('ImportUccxInboundAction: Error al obtener usuarios de Cisco para mapeo: ' . $e->getMessage());
            // Fallback: usar nombres y usernames locales
            $employees = Employee::whereNotNull('username')->get(['id', 'username', 'first_name', 'last_name']);
            foreach ($employees as $employee) {
                $this->employeeCache[strtolower($employee->username)] = $employee->id;
                $fullName = strtolower(trim($employee->first_name . ' ' . $employee->last_name));
                if ($fullName) {
                    $this->employeeCache[$fullName] = $employee->id;
                }
            }
        }
    }

    private function persistRecord(UccxCallDataDTO $dto): void {
        $record = CallRecord::where('cisco_call_id', $dto->ciscoCallId)
            ->where('sequence_number', $dto->sequenceNumber)
            ->first();

        $queueId = null;
        if ($dto->queueName) {
            $cleanQueueName = rtrim($dto->queueName, '*');
            $queueId = $this->queueCache[$cleanQueueName] ?? null;
        }

        $employeeId = null;
        if ($dto->agentName) {
            $cleanAgentName = strtolower(trim($dto->agentName));
            $employeeId = $this->employeeCache[$cleanAgentName] ?? null;
        }

        $status = $this->mapStatus($dto->contactDisposition);

        if ($record) {
            // Merge existing record with new data (Max values for times usually represent the final state)
            $record->update([
                'queue_id' => $record->queue_id ?? $queueId,
                'employee_id' => $record->employee_id ?? $employeeId,
                'talk_time' => max((int) $record->talk_time, $dto->talkTime),
                'ring_time' => max((int) $record->ring_time, $dto->ringTime),
                'work_time' => max((int) $record->work_time, $dto->workTime),
                'queue_time' => max((int) $record->queue_time, $dto->queueTime),
                'status' => $status !== 'pending_operator' ? $status : $record->status,
                'contact_disposition' => $dto->contactDisposition,
            ]);
        } else {
            CallRecord::create([
                'cisco_call_id' => $dto->ciscoCallId,
                'sequence_number' => $dto->sequenceNumber,
                'queue_id' => $queueId,
                'phone_number' => $dto->originatingNumber,
                'destination_number' => $dto->destinationNumber,
                'ivr_started_at' => $dto->startedAt,
                'ivr_ended_at' => $dto->endedAt,
                'talk_time' => $dto->talkTime,
                'ring_time' => $dto->ringTime,
                'work_time' => $dto->workTime,
                'queue_time' => $dto->queueTime,
                'contact_disposition' => $dto->contactDisposition,
                'employee_id' => $employeeId,
                'raw_agent_name' => $dto->agentName,
                'status' => $status,
            ]);
        }
    }

    private function mapStatus(int $disposition): string {
        /**
         * Cisco Disposition:
         * 1: abandoned
         * 2: handled (closed)
         * 4: aborted
         * 5-98: rejected
         * 99: cleansed
         */
        return match (true) {
            $disposition === 1 => 'abandoned',
            $disposition === 2 => 'closed',
            $disposition === 4 => 'aborted',
            $disposition >= 5 && $disposition <= 98 => 'rejected',
            $disposition === 99 => 'cleansed',
            default => 'pending_operator',
        };
    }
}
