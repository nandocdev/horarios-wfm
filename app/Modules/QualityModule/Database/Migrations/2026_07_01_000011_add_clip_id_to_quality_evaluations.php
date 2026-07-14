<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('processed_clips') && DB::getDriverName() === 'pgsql') {
            DB::statement('
                ALTER TABLE quality_evaluations
                ADD CONSTRAINT quality_evaluations_clip_id_foreign
                FOREIGN KEY (clip_id) REFERENCES processed_clips(id)
                ON DELETE SET NULL
            ');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('processed_clips') && DB::getDriverName() === 'pgsql') {
            Schema::table('quality_evaluations', function ($table) {
                $table->dropForeign('quality_evaluations_clip_id_foreign');
            });
        }
    }
};
