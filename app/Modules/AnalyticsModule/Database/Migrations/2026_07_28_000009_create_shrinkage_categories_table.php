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
        Schema::create('shrinkage_categories', function (Blueprint $table) {
            $table->bigSerial('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_planned')->default(true);
            $table->string('color', 10)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('shrinkage_categories')->insert([
            [
                'id' => 1,
                'code' => 'vacation',
                'name' => 'Vacaciones',
                'description' => 'Período de vacaciones programadas',
                'is_paid' => true,
                'is_planned' => true,
                'color' => '#3B82F6',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'code' => 'training',
                'name' => 'Capacitación',
                'description' => 'Entrenamiento o capacitación programada',
                'is_paid' => true,
                'is_planned' => true,
                'color' => '#8B5CF6',
                'is_active' => true,
            ],
            [
                'id' => 3,
                'code' => 'meeting',
                'name' => 'Reunión',
                'description' => 'Reuniones operativas o de equipo',
                'is_paid' => true,
                'is_planned' => true,
                'color' => '#F59E0B',
                'is_active' => true,
            ],
            [
                'id' => 4,
                'code' => 'leave',
                'name' => 'Permiso',
                'description' => 'Permisos médicos, personales o administrativos',
                'is_paid' => true,
                'is_planned' => false,
                'color' => '#EF4444',
                'is_active' => true,
            ],
            [
                'id' => 5,
                'code' => 'lunch',
                'name' => 'Almuerzo',
                'description' => 'Período de almuerzo',
                'is_paid' => false,
                'is_planned' => true,
                'color' => '#10B981',
                'is_active' => true,
            ],
            [
                'id' => 6,
                'code' => 'break',
                'name' => 'Descanso',
                'description' => 'Descansos programados durante la jornada',
                'is_paid' => true,
                'is_planned' => true,
                'color' => '#6366F1',
                'is_active' => true,
            ],
            [
                'id' => 7,
                'code' => 'coaching',
                'name' => 'Coaching',
                'description' => 'Sesiones de coaching o retroalimentación',
                'is_paid' => true,
                'is_planned' => true,
                'color' => '#EC4899',
                'is_active' => true,
            ],
            [
                'id' => 8,
                'code' => 'absence',
                'name' => 'Ausencia',
                'description' => 'Ausencia no justificada',
                'is_paid' => false,
                'is_planned' => false,
                'color' => '#DC2626',
                'is_active' => true,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shrinkage_categories');
    }
};
