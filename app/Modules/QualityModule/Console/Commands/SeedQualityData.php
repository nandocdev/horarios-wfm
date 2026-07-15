<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Console\Commands;

use App\Modules\QualityModule\Database\Seeders\QualityModuleSeeder;
use Illuminate\Console\Command;

class SeedQualityData extends Command
{
    protected $signature = 'quality:seed';

    protected $description = 'Seed datos iniciales del QualityModule: colas, criterios desde CSVs';

    public function handle(QualityModuleSeeder $seeder): int
    {
        $this->info('Iniciando seed de QualityModule...');

        $seeder->run();

        $this->info('✓ Seed de QualityModule completado.');

        return self::SUCCESS;
    }
}
