<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_import_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('batch_id')->nullable()->index();
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('status', 32)->default('pending')->index();

            $table->unsignedInteger('chunk_size')->default(1000);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('rejected_rows')->default(0);

            $table->jsonb('errors')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_import_batches');
    }
};
