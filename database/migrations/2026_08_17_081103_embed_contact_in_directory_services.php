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
        // Cada puerta corresponde a una especialidad (servicio) con su contacto.
        // Columnas nullable: la obligatoriedad se aplica en el formulario.
        Schema::table('directory_services', function (Blueprint $table) {
            $table->string('contact_role')->nullable();
            $table->string('contact_extension')->nullable();
            $table->string('contact_email')->nullable();
        });

        Schema::dropIfExists('directory_contacts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directory_services', function (Blueprint $table) {
            $table->dropColumn(['contact_role', 'contact_extension', 'contact_email']);
        });

        Schema::create('directory_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('directory_units')->cascadeOnDelete();
            $table->string('role');
            $table->string('extension');
            $table->string('email')->nullable();
            $table->timestamps();

            $table->index(['unit_id', 'role']);
        });
    }
};
