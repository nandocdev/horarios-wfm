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
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->foreignId('directory_unit_id')
                ->nullable()
                ->after('category_id')
                ->constrained('directory_units')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('knowledge_articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('directory_unit_id');
        });
    }
};
