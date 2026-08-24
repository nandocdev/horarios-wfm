<?php

declare(strict_types=1);

namespace App\Modules\WfmModule\Enums;

enum AbsenceReasonType: string
{
    case CommonIllness = 'comun';
    case OccupationalRisk = 'riesgos';
    case Bereavement = 'duelo';
    case ChildBirth = 'nacimiento';
    case MedicalAppointment = 'cita_medica';
    case Study = 'estudio';
    case Marriage = 'matrimonio';
    case Accident = 'accidente';
    case Other = 'otro';
}
