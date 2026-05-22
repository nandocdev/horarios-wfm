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
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->date('end_date')->nullable()->after('requested_date');
            $table->renameColumn('requested_date', 'start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->renameColumn('start_date', 'requested_date');
            $table->dropColumn('end_date');
        });
    }
};
