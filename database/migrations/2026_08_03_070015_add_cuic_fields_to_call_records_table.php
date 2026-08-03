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
        Schema::table('call_records', function (Blueprint $table): void {
            $table->string('dialed_number')->nullable()->after('destination_number');
            $table->string('application_name')->nullable()->after('dialed_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table): void {
            $table->dropColumn(['dialed_number', 'application_name']);
        });
    }
};
