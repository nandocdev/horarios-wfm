<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\QualityModule\Database\Seeders\QualityModuleSeeder as ModuleQualityModuleSeeder;
use Illuminate\Database\Seeder;

class QualityModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModuleQualityModuleSeeder::class,
        ]);
    }
}
