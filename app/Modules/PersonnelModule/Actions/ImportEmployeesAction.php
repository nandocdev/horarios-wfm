<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Actions;

use App\Modules\PersonnelModule\DTOs\ImportEmployeesDTO;
use App\Modules\PersonnelModule\Jobs\ProcessEmployeeImportChunkJob;
use App\Modules\PersonnelModule\Models\EmployeeImportBatch;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\LazyCollection;
use Throwable;

class ImportEmployeesAction
{
    public function execute(ImportEmployeesDTO $dto): EmployeeImportBatch
    {
        $importBatch = EmployeeImportBatch::query()->create([
            'original_filename' => $dto->originalFilename,
            'stored_path' => $dto->storedPath,
            'status' => 'processing',
            'chunk_size' => $dto->chunkSize,
            'created_by' => $dto->createdBy,
            'started_at' => now(),
            'errors' => [],
        ]);

        $jobs = [];
        $offset = 2;
        $totalRows = 0;

        foreach ($this->rowsFromCsv($dto->storedPath)->chunk($dto->chunkSize) as $chunk) {
            $chunkRows = array_values($chunk->all());

            if ($chunkRows === []) {
                continue;
            }

            $jobs[] = new ProcessEmployeeImportChunkJob($importBatch->id, $chunkRows, $offset);
            $chunkCount = count($chunkRows);
            $totalRows += $chunkCount;
            $offset += $chunkCount;
        }

        $importBatch->update(['total_rows' => $totalRows]);

        if ($jobs === []) {
            $importBatch->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            return $importBatch->fresh();
        }

        $batchId = $importBatch->id;

        $batch = Bus::batch($jobs)
            ->name('employees-import-'.$batchId)
            ->onQueue('default')
            ->then(function () use ($batchId): void {
                EmployeeImportBatch::query()
                    ->whereKey($batchId)
                    ->update([
                        'status' => 'completed',
                        'finished_at' => now(),
                    ]);
            })
            ->catch(function (Batch $batch, Throwable $exception) use ($batchId): void {
                $model = EmployeeImportBatch::query()->find($batchId);

                if (! $model) {
                    return;
                }

                $errors = $model->errors ?? [];
                $errors[] = [
                    'row' => null,
                    'message' => $exception->getMessage(),
                ];

                $model->update([
                    'status' => 'failed',
                    'errors' => array_slice($errors, -1000),
                    'finished_at' => now(),
                ]);
            })
            ->dispatch();

        $importBatch->update(['batch_id' => $batch->id]);

        return $importBatch->fresh();
    }

    /**
     * @return LazyCollection<int, array<string, string|null>>
     */
    private function rowsFromCsv(string $storedPath): LazyCollection
    {
        $absolutePath = Storage::disk('local')->path($storedPath);

        return LazyCollection::make(function () use ($absolutePath): \Generator {
            $handle = fopen($absolutePath, 'rb');

            if ($handle === false) {
                return;
            }

            try {
                $headers = fgetcsv($handle);

                if ($headers === false) {
                    return;
                }

                $headers = array_map(
                    static fn (string $header): string => strtolower(trim($header)),
                    $headers
                );

                while (($row = fgetcsv($handle)) !== false) {
                    if ($row === [null] || $row === []) {
                        continue;
                    }

                    $assoc = [];

                    foreach ($headers as $index => $header) {
                        $assoc[$header] = isset($row[$index]) ? trim((string) $row[$index]) : null;
                    }

                    yield $assoc;
                }
            } finally {
                fclose($handle);
            }
        });
    }
}
