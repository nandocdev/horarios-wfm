<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reglas de negocio / restricciones clínicas por unidad (fila del Excel)
        // Desacoplado de DirectoryService para no contaminar directorio físico
        Schema::create('directory_service_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('directory_units')->cascadeOnDelete();
            $table->string('service_name', 150);
            $table->boolean('allows_first_visit')->default(true);
            $table->boolean('requires_referral')->default(false);
            $table->text('referral_specialists')->nullable()->comment('Ortopeda, Fisiatra, M.F.R. Policlínica');
            $table->text('requirements')->nullable()->comment('Resumen clínico, cédula, orden médica');
            $table->text('exclusions')->nullable()->comment('No se hace centelleo tiroideo ni resonancia cardíaca');
            $table->string('external_referral', 200)->nullable()->comment('Derivar Eco Doppler a San Francisco');
            $table->text('operational_notes')->nullable()->comment('Jueves hasta 12md por docencia');
            $table->timestamps();

            $table->index(['unit_id', 'service_name']);
        });

        // Trámites / FAQs transversales (Runbooks Call Center)
        Schema::create('directory_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->nullable()->constrained('directory_units')->nullOnDelete();
            $table->string('subject', 150)->comment('Incapacidades, Morgue retiro cupos');
            $table->text('procedure_steps');
            $table->text('required_documents');
            $table->string('estimated_time', 100)->nullable()->comment('8 a 10 días hábiles');
            $table->string('schedule', 150)->nullable();
            $table->boolean('allows_third_party')->default(false);
            $table->timestamps();

            $table->index('subject');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_faqs');
        Schema::dropIfExists('directory_service_rules');
    }
};
