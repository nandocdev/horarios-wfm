<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->unique()->after('name');
        });

        // Backfill codes for existing provinces (2-letter abbreviations used in Panama)
        $codes = [
            'Bocas del Toro' => 'BT',
            'Coclé' => 'CC',
            'Colón' => 'CO',
            'Chiriquí' => 'CH',
            'Darién' => 'DA',
            'Herrera' => 'HE',
            'Los Santos' => 'LS',
            'Panamá' => 'PA',
            'Veraguas' => 'VG',
            'Comarca Kuna Yala' => 'KY',
            'Comarca Emberá' => 'EM',
            'Comarca Ngäbe Buglé' => 'NB',
            'Panamá Oeste' => 'PM',
        ];

        foreach ($codes as $name => $code) {
            DB::table('provinces')
                ->where('name', $name)
                ->update(['code' => $code]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provinces', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
