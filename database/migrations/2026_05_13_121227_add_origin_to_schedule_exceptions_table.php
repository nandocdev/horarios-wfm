<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_exceptions', function (Blueprint $blueprint) {
            $blueprint->nullableMorphs('origin'); // origin_type, origin_id
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_exceptions', function (Blueprint $blueprint) {
            $blueprint->dropMorphs('origin');
        });
    }
};
