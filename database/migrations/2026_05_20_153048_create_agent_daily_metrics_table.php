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
        Schema::create('agent_daily_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('metric_date')->index();

            // Tiempos (Segundos)
            $table->integer('login_seconds')->default(0);
            $table->integer('productive_seconds')->default(0);

            // Volumen
            $table->integer('calls_total')->default(0);
            $table->integer('talk_seconds')->default(0);

            // Cálculos Avanzados
            $table->decimal('weighted_aht', 10, 2)->default(0);
            $table->decimal('capacity_calls', 10, 2)->default(0);
            $table->decimal('capacity_gap', 10, 2)->default(0);
            $table->decimal('work_units', 10, 2)->default(0); // Minutos equivalentes

            // Porcentajes (0-100)
            $table->decimal('availability_pct', 5, 2)->default(0);
            $table->decimal('efficiency_pct', 5, 2)->default(0);
            $table->decimal('pwi_pct', 5, 2)->default(0);

            $table->jsonb('queue_distribution')->nullable(); // Metadata de distribución por cola
            
            $table->timestamps();

            $table->unique(['employee_id', 'metric_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_daily_metrics');
    }
};
