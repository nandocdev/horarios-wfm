<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ActivityType;
use Illuminate\Database\Seeder;

class ScheduleCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tipos de Actividad
        $activityTypes = [
            ['name' => 'REUNIÓN', 'color' => '#8b5cf6', 'is_productive' => true, 'is_paid' => true],
            ['name' => 'CAPACITACION', 'color' => '#f59e0b', 'is_productive' => true, 'is_paid' => true],
            ['name' => 'DOCENCIA', 'color' => '#10b981', 'is_productive' => true, 'is_paid' => true],
            ['name' => 'RETROALIMENTACION', 'color' => '#06b6d4', 'is_productive' => true, 'is_paid' => true],
        ];

        foreach ($activityTypes as $type) {
            ActivityType::updateOrCreate(['name' => $type['name']], $type);
        }

        // 2. Motivos de Ausencia / Justificación
        $red = '#ef4444';
        $blue = '#3b82f6';

        $absenceReasons = [
            ['short_code' => 'A.I.', 'name' => 'AUSENCIA INJUSTIFICADA', 'color' => $red, 'is_excused' => false, 'requires_attachment' => false],
            ['short_code' => 'S', 'name' => 'SUSPENSIÓN', 'color' => $red, 'is_excused' => false, 'requires_attachment' => false],
            ['short_code' => 'T.I.', 'name' => 'TARDANZA INJUSTIFICADA', 'color' => $red, 'is_excused' => false, 'requires_attachment' => false],
            ['short_code' => 'T.J.', 'name' => 'TARDANZA JUSTIFICADA', 'color' => $red, 'is_excused' => true, 'requires_attachment' => true],
            ['short_code' => 'C.M.', 'name' => 'CERTIFICADO DE INCAPACIDAD', 'color' => $red, 'is_excused' => true, 'requires_attachment' => true],
            ['short_code' => 'S.C.', 'name' => 'ENFERMEDAD SIN CERTIF. M.', 'color' => $red, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'R', 'name' => 'REUNIÓN - PROGRAMAS - PRESENTACIÓN', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'R.P.', 'name' => 'RIESGOS PROFESIONALES', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => true],
            ['short_code' => 'L.', 'name' => 'LICENCIA', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => true],
            ['short_code' => 'P', 'name' => 'PERMISO X ARTÍCULO', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'D.', 'name' => 'DUELO', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => true],
            ['short_code' => 'V.', 'name' => 'VACACIONES', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'P.T.', 'name' => 'PAGANDO TIEMPO', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'T.C.', 'name' => 'TIEMPO COMPENSATORIO', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'T.E.', 'name' => 'TIEMPO EXTRA', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'S.D', 'name' => 'SEMINARIO-DOCENCIA', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'C.A', 'name' => 'CONSTANCIA DE ASISTENCIA', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => true],
            ['short_code' => 'T.A', 'name' => 'TIEMPO ADEUDADO', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => false],
            ['short_code' => 'C.A.8', 'name' => 'CONTANCIA ASISTENCIA TODO EL DÍA AUSENCIA', 'color' => $red, 'is_excused' => false, 'requires_attachment' => true],
            ['short_code' => 'C.A 7', 'name' => 'CONTANCIA DE 5 A 7 HORAS', 'color' => $blue, 'is_excused' => true, 'requires_attachment' => true],
        ];

        foreach ($absenceReasons as $reason) {
            AbsenceReasonCode::updateOrCreate(['short_code' => $reason['short_code']], $reason);
        }
    }
}
