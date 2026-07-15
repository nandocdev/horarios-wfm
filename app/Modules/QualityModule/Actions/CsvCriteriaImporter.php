<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use App\Modules\QualityModule\Models\Queue;
use App\Modules\QualityModule\Models\QueueCriteria;
use App\Modules\QualityModule\Models\RedFlagCriteria;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class CsvCriteriaImporter
{
    private const QUEUE_MAP = [
        'au' => 'AU',
        'cm_tr' => 'CM-Tr',
        'cm_canc' => 'CM-Canc',
        'cm_conf' => 'CM-Conf',
        'farm' => 'Farm',
        'sipe' => 'SIPE',
        'web' => 'WEB',
    ];

    public function execute(): array
    {
        $csvDir = base_path('database/data/QA');

        if (! is_dir($csvDir)) {
            throw new RuntimeException('CSV directory not found: '.$csvDir);
        }

        $files = glob($csvDir.'/*.csv');
        $stats = ['criteria' => 0, 'red_flags' => 0];

        DB::transaction(function () use ($files, &$stats) {
            foreach ($files as $file) {
                $basename = basename($file);

                if (str_starts_with($basename, '_red_flag')) {
                    $stats['red_flags'] += $this->importRedFlags($file);

                    continue;
                }

                if (str_starts_with($basename, '_tbl_lab')) {
                    continue;
                }

                $stats['criteria'] += $this->importCriteriaFile($file);
            }
        });

        return $stats;
    }

    private function importCriteriaFile(string $path): int
    {
        $basename = basename($path);
        $prefix = $this->parsePrefix($basename);

        if (! isset(self::QUEUE_MAP[$prefix])) {
            return 0;
        }

        $queueCode = self::QUEUE_MAP[$prefix];
        $fiscalYearStart = $this->parseFiscalYear($basename);

        $queue = Queue::firstWhere('code', $queueCode);
        if (! $queue) {
            return 0;
        }

        $rows = $this->parseCsv($path);
        $count = 0;

        foreach ($rows as $row) {
            $criteriaCode = strtoupper($queueCode.'_'.$row['id']);
            $criteriaText = trim($row['criterio'] ?? '');
            $puntaje = (int) ($row['puntaje'] ?? 0);

            if ($criteriaText === '' || $puntaje <= 0) {
                continue;
            }

            $criteria = Criteria::firstOrCreate(
                ['code' => $criteriaCode],
            );

            $version = CriteriaVersion::firstOrCreate(
                [
                    'criteria_id' => $criteria->id,
                    'version' => 1,
                ],
                [
                    'criteria_id' => $criteria->id,
                    'version' => 1,
                    'criterio_text' => Str::limit($criteriaText, 250),
                    'puntaje' => $puntaje,
                    'descripcion' => $row['descripción'] ?? null,
                    'valid_from' => $fiscalYearStart->toDateString(),
                    'valid_to' => null,
                ]
            );

            QueueCriteria::firstOrCreate(
                [
                    'queue_id' => $queue->id,
                    'criteria_version_id' => $version->id,
                ],
                [
                    'queue_id' => $queue->id,
                    'criteria_version_id' => $version->id,
                    'orden' => (int) $row['id'],
                    'is_active' => true,
                ]
            );

            $count++;
        }

        return $count;
    }

    private function importRedFlags(string $path): int
    {
        $rows = $this->parseCsv($path);
        $count = 0;

        foreach ($rows as $row) {
            $criterioText = trim($row['criterio'] ?? '');
            $perdida = (int) ($row['perdida'] ?? 0);

            if ($criterioText === '' || $perdida <= 0) {
                continue;
            }

            RedFlagCriteria::firstOrCreate(
                ['criterio_text' => $criterioText],
                [
                    'criterio_text' => $criterioText,
                    'perdida' => $perdida,
                    'is_active' => true,
                ]
            );

            $count++;
        }

        return $count;
    }

    private function parsePrefix(string $basename): string
    {
        $parts = explode('_eval', $basename, 2);
        $prefix = ltrim($parts[0], '_');

        return strtolower($prefix);
    }

    private function parseFiscalYear(string $basename): CarbonImmutable
    {
        if (preg_match('/_eval(\d{2})_(\d{2})/', $basename, $m)) {
            $year = (int) ('20'.$m[1]);

            return CarbonImmutable::create($year, 7, 1, 0, 0, 0);
        }

        return CarbonImmutable::now()->startOfYear();
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return [];
        }

        $headers = fgetcsv($handle, 0, ',', '"', '');
        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn (string $h) => trim(mb_strtolower($h)), $headers);
        $rows = [];

        while (($line = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if (count($line) !== count($headers)) {
                continue;
            }

            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $line[$i] ?? '';
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
