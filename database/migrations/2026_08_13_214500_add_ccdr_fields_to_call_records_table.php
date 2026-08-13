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
            $table->unsignedSmallInteger('node_id')->nullable()->after('sequence_number');
            $table->unsignedSmallInteger('contact_type')->nullable()->after('ivr_ended_at');
            $table->unsignedSmallInteger('originator_type')->nullable()->after('contact_disposition');
            $table->string('originator_id')->nullable()->after('originator_type');
            $table->unsignedSmallInteger('destination_type')->nullable()->after('originator_id');
            $table->string('destination_id')->nullable()->after('destination_type');
            $table->string('original_dialed_number')->nullable()->after('dialed_number');
            $table->unsignedInteger('hold_time')->default(0)->after('talk_time');

            $table->index('contact_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_records', function (Blueprint $table): void {
            $table->dropIndex(['contact_type']);
            $table->dropColumn([
                'node_id',
                'contact_type',
                'originator_type',
                'originator_id',
                'destination_type',
                'destination_id',
                'original_dialed_number',
                'hold_time',
            ]);
        });
    }
};
