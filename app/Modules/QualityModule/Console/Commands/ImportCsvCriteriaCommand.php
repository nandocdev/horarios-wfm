<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Console\Commands;

use App\Modules\QualityModule\Actions\CsvCriteriaImporter;
use Illuminate\Console\Command;

class ImportCsvCriteriaCommand extends Command
{
    protected $signature = 'quality:import-csv {--force : Re-importa aunque los registros ya existan}';

    protected $description = 'Importa criterios de evaluación desde CSVs del legacy system en database/data/QA/';

    public function handle(CsvCriteriaImporter $importer): int
    {
        $this->info('Importando criterios desde CSVs...');

        $stats = $importer->execute();

        $this->info(sprintf('✓ %d criterios importados para colas', $stats['criteria']));
        $this->info(sprintf('✓ %d red flags importadas', $stats['red_flags']));

        return self::SUCCESS;
    }
}
