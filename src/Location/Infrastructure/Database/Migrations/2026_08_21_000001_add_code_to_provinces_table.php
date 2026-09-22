<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provinces')) {
            return;
        }

        if (! Schema::hasColumn('provinces', 'code')) {
            Schema::table('provinces', function (Blueprint $table): void {
                $table->string('code', 10)->nullable()->unique()->after('name');
            });
        }

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
                ->whereNull('code')
                ->update(['code' => $code]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('provinces') && Schema::hasColumn('provinces', 'code')) {
            Schema::table('provinces', function (Blueprint $table): void {
                $table->dropColumn('code');
            });
        }
    }
};
