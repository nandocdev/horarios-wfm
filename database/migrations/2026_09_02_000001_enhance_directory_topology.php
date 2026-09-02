<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_buildings', function (Blueprint $table) {
            $table->string('color_identifier', 50)->nullable()->after('name')->comment('Verde, Morado, Celeste, 4F - referencia operativa');
            $table->text('description')->nullable()->after('color_identifier');
        });

        Schema::table('directory_units', function (Blueprint $table) {
            $table->string('door_range', 50)->nullable()->after('level')->comment('Rango operativo: 00-10, 181-190, Puerta 220');
            $table->string('wing_sector', 100)->nullable()->after('door_range')->comment('Ala/sector: Entrando al final del atrio...');
            $table->string('attention_schedule', 150)->nullable()->after('wing_sector')->comment('Horario propio del piso si difiere del servicio');
        });

        Schema::table('directory_services', function (Blueprint $table) {
            $table->boolean('is_person')->default(true)->after('name')->comment('FALSE = sala/estación, TRUE = persona');
            $table->string('office_number', 50)->nullable()->after('is_person')->comment('OFICINA 166, PUERTA 128');
        });
    }

    public function down(): void
    {
        Schema::table('directory_services', function (Blueprint $table) {
            $table->dropColumn(['is_person', 'office_number']);
        });
        Schema::table('directory_units', function (Blueprint $table) {
            $table->dropColumn(['door_range', 'wing_sector', 'attention_schedule']);
        });
        Schema::table('directory_buildings', function (Blueprint $table) {
            $table->dropColumn(['color_identifier', 'description']);
        });
    }
};
