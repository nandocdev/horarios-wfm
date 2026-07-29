<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_queues', function (Blueprint $table) {
            $table->unsignedInteger('finesse_queue_id')->nullable()->after('id');
            $table->unique('finesse_queue_id');
        });
    }

    public function down(): void
    {
        Schema::table('call_queues', function (Blueprint $table) {
            $table->dropUnique(['finesse_queue_id']);
            $table->dropColumn('finesse_queue_id');
        });
    }
};
