<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('capacity_intervals', function (Blueprint $table) {
            $table->decimal('coverage', 10, 2)->default(0)->comment('(available / required) * 100')->change();
        });

        Schema::table('capacity_results', function (Blueprint $table) {
            $table->decimal('avg_coverage', 10, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('capacity_intervals', function (Blueprint $table) {
            $table->decimal('coverage', 5, 2)->default(0)->comment('(available / required) * 100')->change();
        });

        Schema::table('capacity_results', function (Blueprint $table) {
            $table->decimal('avg_coverage', 5, 2)->default(0)->change();
        });
    }
};
