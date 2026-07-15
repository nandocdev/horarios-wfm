<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Database\Seeders;

use App\Modules\QualityModule\Actions\CsvCriteriaImporter;
use App\Modules\QualityModule\Enums\QueueCode;
use App\Modules\QualityModule\Models\Queue;
use Illuminate\Database\Seeder;

class QualityModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedQueues();
        $this->importCriteriaFromCsv();
    }

    private function seedQueues(): void
    {
        foreach (QueueCode::cases() as $code) {
            Queue::firstOrCreate(
                ['code' => $code->value],
                ['name' => $code->label(), 'is_active' => true]
            );
        }

        $this->command?->info(sprintf('✓ %d colas creadas', count(QueueCode::cases())));
    }

    private function importCriteriaFromCsv(): void
    {
        $stats = app(CsvCriteriaImporter::class)->execute();

        $this->command?->info(sprintf('✓ %d criterios importados desde CSVs', $stats['criteria']));
        $this->command?->info(sprintf('✓ %d red flags importadas desde CSVs', $stats['red_flags']));
    }
}
