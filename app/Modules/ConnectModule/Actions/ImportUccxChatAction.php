<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use App\Modules\ConnectModule\Models\ChatRecord;
use App\Modules\PersonnelModule\Models\Employee;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class ImportUccxChatAction
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
                    Log::warning("Fila chat {$rowCount} malformada en {$filePath}.");

                    continue;
                }

                $row = array_combine($header, $data);

                try {
                    $this->persistRecord($row);
                    $importedCount++;
                } catch (\Exception $e) {
                    Log::warning("Error procesando chat fila {$rowCount} en {$filePath}: ".$e->getMessage());

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
            Log::error("Error masivo importando chats {$filePath}: ".$e->getMessage());
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
        $conversationId = $row['chat_conversation'];
        $loginId = $row['ID de agente'];
        $employeeId = $this->employeeCache[$loginId] ?? null;

        ChatRecord::updateOrCreate(
            ['conversation_id' => $conversationId],
            [
                'agent_login_id' => $loginId,
                'employee_id' => $employeeId,
                'start_time' => CarbonImmutable::parse($row['hora_de_inicio_de_conversación']),
                'end_time' => CarbonImmutable::parse($row['hora_de fin_de_conversación']),
                'accepted_at' => $row['hora_de_aceptación'] ? CarbonImmutable::parse($row['hora_de_aceptación']) : null,
                'total_duration' => (int) ($row['duración_de_conversación'] ?: 0),
                'talk_time' => (int) ($row['tiempo_de_conversación'] ?: 0),
                'author_identifier' => $row['autor_de_conversación'] ?: null,
                'destination_identifier' => $row['destino_de_conversación'] ?: null,
                'chat_type' => $row['tipo_de_conversación'] ?: null,
                'chat_source' => $row['chat_source'] ?: null,
                'chat_rating' => $row['chat_rating'] ?: null,
                'raw_agent_name' => $row['Nombre del agente'] ?: null,
            ]
        );
    }
}
