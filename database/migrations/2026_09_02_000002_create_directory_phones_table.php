<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1:N teléfonos por servicio (reemplaza contact_extension único)
        // Soporta CISCO (5 dígitos), DIRECTO (513-XXXX), MOVIL, WHATSAPP_CHAT
        Schema::create('directory_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('directory_services')->cascadeOnDelete();
            $table->string('number', 30);
            $table->string('type', 20)->default('CISCO')->comment('CISCO, DIRECTO, MOVIL, WHATSAPP_CHAT');
            $table->string('description', 100)->nullable()->comment('Línea de consultas, Citas pediátricas');
            $table->timestamps();

            $table->unique(['service_id', 'number']);
            $table->index('number');
            $table->index('type');
        });

        // Soporte polimórfico opcional a nivel unidad (líneas generales 513-9400 sin servicio)
        Schema::create('directory_unit_phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('directory_units')->cascadeOnDelete();
            $table->string('number', 30);
            $table->string('type', 20)->default('DIRECTO');
            $table->string('description', 100)->nullable();
            $table->timestamps();

            $table->unique(['unit_id', 'number']);
        });

        // Backfill: migrar contact_extension existente a directory_phones
        if (Schema::hasColumn('directory_services', 'contact_extension')) {
            $services = DB::table('directory_services')->whereNotNull('contact_extension')->where('contact_extension', '!=', '')->get(['id', 'contact_extension']);
            foreach ($services as $svc) {
                $raw = trim((string) $svc->contact_extension);
                // sanitizar: solo dígitos y guión
                $sanitized = preg_replace('/[^0-9\-]/', '', $raw);
                if ($sanitized === '' || $sanitized === null) {
                    continue;
                }
                $type = match (true) {
                    preg_match('/^\d{5}$/', $sanitized) === 1 => 'CISCO',
                    str_starts_with($sanitized, '513-') => 'DIRECTO',
                    preg_match('/^6\d{3}-\d{4}$/', $sanitized) === 1 => 'MOVIL',
                    default => 'DIRECTO',
                };
                DB::table('directory_phones')->insert([
                    'service_id' => $svc->id,
                    'number' => $sanitized,
                    'type' => $type,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_unit_phones');
        Schema::dropIfExists('directory_phones');
    }
};
