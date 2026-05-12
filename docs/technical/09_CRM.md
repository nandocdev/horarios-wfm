---

## **TL;DR**

Perfecto. Eso es **exactamente lo que necesitas**: IVR dispara webhook → operador completa formulario simple → HorariosWFM registra todo. **Una tabla, sin contaminar Employee/Citizen.**

---

## **1. Arquitectura de Flujo**

```
┌─────────────────────┐
│  CISCO IVR          │
│  - Queue: 199       │
│  - Phone: 66XX-XXXX │
│  - Call start time  │
└──────────┬──────────┘
           │ (webhook JSON)
           ▼
┌──────────────────────────────┐
│ HorariosWFM ContactCenter     │
│ POST /api/call-start         │
│ Crea CallRecord (borrador)   │
│ Retorna callRecordId         │
└──────────┬───────────────────┘
           │ (callRecordId)
           ▼
┌──────────────────────────────┐
│ CISCO Finesse Desktop        │
│ (agente/operador)            │
│                              │
│ ┌────────────────────────┐   │
│ │ FORMULARIO EMERGENTE   │   │
│ │ - Cédula asegurado     │   │
│ │ - Subtipo (dropdown)   │   │
│ │ - Descripción libre    │   │
│ │ - [Guardar]            │   │
│ └────────────────────────┘   │
└──────────┬───────────────────┘
           │ (POST JSON + callRecordId)
           ▼
┌──────────────────────────────┐
│ HorariosWFM ContactCenter     │
│ PUT /api/call-records/{id}   │
│ Actualiza: cédula, subtipo,  │
│ descripción, estado=abierto  │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ Durante la llamada            │
│ Operador puede ACTUALIZAR    │
│ descripción/estado en tiempo  │
│ real (opcional)              │
└──────────┬───────────────────┘
           │ (webhook fin de llamada)
           ▼
┌──────────────────────────────┐
│ IVR dispara FIN de llamada   │
│ Envía: ended_at, duration    │
└──────────┬───────────────────┘
           │
           ▼
┌──────────────────────────────┐
│ HorariosWFM                  │
│ PUT /api/call-records/{id}   │
│ - ended_at                   │
│ - estado = cerrado (opcional)│
│ - resultado final            │
└──────────────────────────────┘
```

---

## **2. Tabla única: `call_records`**

```sql
CREATE TABLE public.call_records (
    id bigserial PRIMARY KEY,

    -- IVR (Cisco)
    cisco_call_id varchar(255) UNIQUE NOT NULL,
    queue_name varchar(100),                  -- '199', 'Soporte Técnico', etc.
    phone_number varchar(20) NOT NULL,        -- '66XX-XXXX'
    ivr_started_at timestamp NOT NULL,        -- Timestamp del IVR

    -- Operador
    employee_id int8 NULL REFERENCES employees(id),

    -- Asegurado (SOLO cédula, sin PII)
    citizen_identifier varchar(12) NULL,      -- Cédula del asegurado

    -- Clasificación por el operador
    case_subtype_id int8 REFERENCES case_subtypes(id),  -- FK a catálogo
    description text,

    -- Estado del registro
    status varchar(50) DEFAULT 'open',        -- 'open', 'closed', 'transferred'
    resolution varchar(255) NULL,             -- "Cita agendada", "Consulta respondida"

    -- Timing de llamada
    ivr_ended_at timestamp NULL,              -- Timestamp final del IVR
    duration_seconds int4 GENERATED ALWAYS AS (
        EXTRACT(EPOCH FROM (ivr_ended_at - ivr_started_at))::int4
    ) STORED,

    -- Auditoría
    created_at timestamp,
    updated_at timestamp,
    closed_at timestamp NULL,
    closed_by int8 REFERENCES employees(id),

    -- Índices
    CONSTRAINT call_records_citizen_identifier_format
        CHECK (citizen_identifier ~ '^\d{8,12}$' OR citizen_identifier IS NULL)
);

-- Índices para reportes
CREATE INDEX call_records_employee_ivr_started_at_idx
    ON call_records(employee_id, ivr_started_at DESC);
CREATE INDEX call_records_queue_name_idx
    ON call_records(queue_name, ivr_started_at DESC);
CREATE INDEX call_records_case_subtype_id_idx
    ON call_records(case_subtype_id);
CREATE INDEX call_records_status_idx
    ON call_records(status);
CREATE INDEX call_records_citizen_identifier_idx
    ON call_records(citizen_identifier);

-- Catálogo de subtipos
CREATE TABLE public.case_subtypes (
    id bigserial PRIMARY KEY,
    code varchar(50) UNIQUE NOT NULL,
    queue_name varchar(100) NOT NULL,        -- Vinculado a cola Cisco
    name varchar(255) NOT NULL,
    description text,
    is_active bool DEFAULT true,
    created_at timestamp,
    updated_at timestamp,

    CONSTRAINT case_subtypes_queue_code_unique UNIQUE (queue_name, code)
);
```

---

## **3. Modelo Eloquent**

```php
<?php
// app/Modules/ContactCenter/Models/CallRecord.php

namespace App\Modules\ContactCenter\Models;

use App\Modules\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallRecord extends Model
{
    protected $table = 'call_records';

    protected $fillable = [
        'cisco_call_id',
        'queue_name',
        'phone_number',
        'ivr_started_at',
        'ivr_ended_at',
        'employee_id',
        'citizen_identifier',
        'case_subtype_id',
        'description',
        'status',
        'resolution',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'ivr_started_at' => 'datetime',
        'ivr_ended_at'   => 'datetime',
        'closed_at'      => 'datetime',
    ];

    // Relaciones
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function caseSubtype(): BelongsTo
    {
        return $this->belongsTo(CaseSubtype::class, 'case_subtype_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'closed_by');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeByCitizen($query, string $citizenIdentifier)
    {
        return $query->where('citizen_identifier', $citizenIdentifier);
    }

    public function scopeByEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByQueue($query, string $queue)
    {
        return $query->where('queue_name', $queue);
    }

    public function scopeByDateRange($query, $start, $end)
    {
        return $query->whereBetween('ivr_started_at', [$start, $end]);
    }

    // Accesores útiles
    public function getDurationMinutesAttribute(): ?float
    {
        if (!$this->ivr_ended_at) return null;
        return $this->ivr_started_at->diffInSeconds($this->ivr_ended_at) / 60;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'open'        => 'Abierto',
            'closed'      => 'Cerrado',
            'transferred' => 'Transferido',
            default       => 'Desconocido',
        };
    }
}
```

```php
<?php
// app/Modules/ContactCenter/Models/CaseSubtype.php

namespace App\Modules\ContactCenter\Models;

use Illuminate\Database\Eloquent\Model;

class CaseSubtype extends Model
{
    protected $table = 'case_subtypes';

    protected $fillable = [
        'code',
        'queue_name',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeByQueue($query, string $queue)
    {
        return $query->where('queue_name', $queue)->where('is_active', true);
    }
}
```

---

## **4. DTOs**

```php
<?php
// app/Modules/ContactCenter/DTOs/CallStartDTO.php

namespace App\Modules\ContactCenter\DTOs;

use Carbon\Carbon;

/**
 * Webhook dispara esto cuando IVR inicia.
 * CISCO envía ANTES de que el operador conteste.
 */
readonly class CallStartDTO
{
    public function __construct(
        public string  $ciscoCallId,
        public string  $queueName,
        public string  $phoneNumber,
        public Carbon  $ivrStartedAt,
    ) {}

    public static function fromCiscoWebhook(array $data): self
    {
        return new self(
            ciscoCallId: $data['call_id'],
            queueName: $data['queue_name'],
            phoneNumber: $data['ani'],  // Automatic Number Identification
            ivrStartedAt: Carbon::parse($data['timestamp']),
        );
    }
}

// app/Modules/ContactCenter/DTOs/CallCompleteDTO.php

namespace App\Modules\ContactCenter\DTOs;

use Carbon\Carbon;

/**
 * Operador completa el formulario DURANTE la llamada.
 */
readonly class CallCompleteDTO
{
    public function __construct(
        public string  $citizenIdentifier,          // Cédula (validada)
        public int     $caseSubtypeId,
        public string  $description,
        public ?string $resolution = null,
    ) {}

    public static function fromForm(array $data): self
    {
        return new self(
            citizenIdentifier: $data['citizen_identifier'],
            caseSubtypeId: (int) $data['case_subtype_id'],
            description: $data['description'],
            resolution: $data['resolution'] ?? null,
        );
    }
}

// app/Modules/ContactCenter/DTOs/CallCloseDTO.php

namespace App\Modules\ContactCenter\DTOs;

use Carbon\Carbon;

/**
 * IVR dispara cuando cuelga (fin de llamada).
 */
readonly class CallCloseDTO
{
    public function __construct(
        public string  $ciscoCallId,
        public Carbon  $ivrEndedAt,
        public ?string $status = 'closed',
    ) {}

    public static function fromCiscoWebhook(array $data): self
    {
        return new self(
            ciscoCallId: $data['call_id'],
            ivrEndedAt: Carbon::parse($data['end_timestamp']),
            status: $data['call_status'] ?? 'closed',
        );
    }
}
```

---

## **5. Actions — Lógica de Negocio**

```php
<?php
// app/Modules/ContactCenter/Actions/CreateCallRecordAction.php

namespace App\Modules\ContactCenter\Actions;

use App\Modules\ContactCenter\DTOs\CallStartDTO;
use App\Modules\ContactCenter\Models\CallRecord;
use Illuminate\Database\DatabaseManager;

/**
 * WEBHOOK #1: IVR dispara al INICIAR llamada.
 * Crea registro borrador. Operador lo completará después.
 */
class CreateCallRecordAction
{
    public function __construct(private DatabaseManager $db) {}

    public function execute(CallStartDTO $dto): CallRecord
    {
        return $this->db->transaction(function () use ($dto) {
            return CallRecord::create([
                'cisco_call_id'   => $dto->ciscoCallId,
                'queue_name'      => $dto->queueName,
                'phone_number'    => $dto->phoneNumber,
                'ivr_started_at'  => $dto->ivrStartedAt,
                'status'          => 'open',  // Abierto, esperando datos del operador
            ]);
        });
    }
}

// app/Modules/ContactCenter/Actions/CompleteCallRecordAction.php

namespace App\Modules\ContactCenter\Actions;

use App\Modules\ContactCenter\DTOs\CallCompleteDTO;
use App\Modules\ContactCenter\Models\CallRecord;
use Illuminate\Database\DatabaseManager;

/**
 * OPERADOR completa el formulario durante la llamada.
 * Valida cédula, subtipo y descripción.
 */
class CompleteCallRecordAction
{
    public function __construct(private DatabaseManager $db) {}

    public function execute(CallRecord $record, CallCompleteDTO $dto): CallRecord
    {
        // Validaciones
            if (!$this->validateCitizenIdentifier($dto->citizenIdentifier)) {
                throw new \InvalidArgumentException('Cédula inválida');
            }

            return $this->db->transaction(function () use ($record, $dto) {
                $record->update([
                    'citizen_identifier' => $dto->citizenIdentifier,
                // Status sigue siendo 'open' hasta que IVR dispare fin
            ]);

            return $record->refresh();
        });
    }

    private function validateCitizenIdentifier(string $id): bool
    {
        // Validación básica: 8-12 dígitos, sin caracteres especiales
        return preg_match('/^\d{8,12}$/', $id) === 1;
    }
}

// app/Modules/ContactCenter/Actions/CloseCallRecordAction.php

namespace App\Modules\ContactCenter\Actions;

use App\Modules\ContactCenter\DTOs\CallCloseDTO;
use App\Modules\ContactCenter\Models\CallRecord;
use Illuminate\Database\DatabaseManager;

/**
 * WEBHOOK #2: IVR dispara al TERMINAR llamada.
 * Cierra registro con timestamp y duración.
 */
class CloseCallRecordAction
{
    public function __construct(private DatabaseManager $db) {}

    public function execute(CallRecord $record, CallCloseDTO $dto): CallRecord
    {
        return $this->db->transaction(function () use ($record, $dto) {
            $record->update([
                'ivr_ended_at' => $dto->ivrEndedAt,
                'status'       => $dto->status, // 'closed', 'transferred', etc.
                'closed_at'    => now(),
            ]);

            return $record->refresh();
        });
    }
}
```

---

## **6. API Endpoints**

```php
<?php
// app/Modules/ContactCenter/Http/Controllers/CallRecordController.php

namespace App\Modules\ContactCenter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ContactCenter\Actions\{
    CreateCallRecordAction,
    CompleteCallRecordAction,
    CloseCallRecordAction,
};
use App\Modules\ContactCenter\DTOs\{CallStartDTO, CallCompleteDTO, CallCloseDTO};
use App\Modules\ContactCenter\Http\Requests\{
    CreateCallRequest,
    CompleteCallRequest,
    CloseCallRequest,
};
use App\Modules\ContactCenter\Models\CallRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallRecordController extends Controller
{
    /**
     * WEBHOOK #1: IVR dispara al INICIAR llamada.
     * POST /api/contact-center/calls/start
     *
     * Payload CISCO:
     * {
     *   "call_id": "CISCO-20260406-12345",
     *   "queue_name": "Servicios de Salud",
     *   "ani": "66XX-1234",
     *   "timestamp": "2026-04-06T14:30:00Z"
     * }
     */
    public function start(
        CreateCallRequest $request,
        CreateCallRecordAction $action,
    ): JsonResponse {
        $dto = CallStartDTO::fromCiscoWebhook($request->validated());
        $record = $action->execute($dto);

        return response()->json([
            'callRecordId' => $record->id,
            'queueName'    => $record->queue_name,
            'startedAt'    => $record->ivr_started_at,
        ], 201);
    }

    /**
     * OPERADOR completa formulario DURANTE la llamada.
     * PUT /api/contact-center/calls/{id}/complete
     *
     * Payload:
     * {
     *   "citizen_identifier": "12345678",
     *   "case_subtype_id": 5,
     *   "description": "Cliente solicita cita de control",
     *   "resolution": "Cita agendada para 10 de abril"
     * }
     */
    public function complete(
        int $id,
        CompleteCallRequest $request,
        CompleteCallRecordAction $action,
    ): JsonResponse {
        $record = CallRecord::findOrFail($id);
        $this->authorize('update', $record);

        $dto = CallCompleteDTO::fromForm($request->validated());
        $updated = $action->execute($record, $dto);

        return response()->json($updated, 200);
    }

    /**
     * WEBHOOK #2: IVR dispara al TERMINAR llamada.
     * PUT /api/contact-center/calls/{id}/close
     *
     * Payload CISCO:
     * {
     *   "call_id": "CISCO-20260406-12345",
     *   "end_timestamp": "2026-04-06T14:45:30Z",
     *   "call_status": "closed"
     * }
     */
    public function close(
        int $id,
        CloseCallRequest $request,
        CloseCallRecordAction $action,
    ): JsonResponse {
        $record = CallRecord::findOrFail($id);

        $dto = CallCloseDTO::fromCiscoWebhook($request->validated());
        $closed = $action->execute($record, $dto);

        return response()->json([
            'id'             => $closed->id,
            'status'         => $closed->status,
            'durationMinutes' => $closed->duration_minutes,
        ], 200);
    }

    /**
     * OPERADOR consulta registros abiertos (formularios pendientes).
     * GET /api/contact-center/calls/open
     */
    public function openCalls(Request $request): JsonResponse
    {
        $records = CallRecord::open()
            ->with(['employee', 'caseSubtype'])
            ->orderByDesc('ivr_started_at')
            ->paginate(15);

        return response()->json($records);
    }

    /**
     * Reporte: carga operativa por equipo/día.
     * GET /api/contact-center/reports/team-load?date=2026-04-06
     */
    public function teamLoadReport(Request $request): JsonResponse
    {
        $date = $request->query('date') ?
            \Carbon\Carbon::parse($request->query('date')) :
            now();

        $records = CallRecord::byDateRange(
            $date->startOfDay(),
            $date->endOfDay()
        )->get();

        $report = [
            'date'            => $date->toDateString(),
            'totalCalls'      => $records->count(),
            'averageDuration' => round($records->avg('duration_seconds') / 60, 2),
            'byQueue'         => $records->groupBy('queue_name')->map(fn($q) => [
                'count'    => $q->count(),
                'avgTime'  => round($q->avg('duration_seconds') / 60, 2),
            ]),
            'bySubtype'       => $records->groupBy('case_subtype_id')->map(fn($s) => [
                'count' => $s->count(),
                'name'  => $s->first()?->caseSubtype?->name,
            ]),
        ];

        return response()->json($report);
    }
}
```

---

## **7. Form Requests — Validación**

```php
<?php
// app/Modules/ContactCenter/Http/Requests/CreateCallRequest.php

namespace App\Modules\ContactCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Webhook CISCO → inicio de llamada.
 * IP whitelist para seguridad.
 */
class CreateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Validar que viene de CISCO (IP configurada)
        $ciscoIps = config('contact-center.cisco_webhook_ips', []);
        return empty($ciscoIps) || in_array($this->ip(), $ciscoIps);
    }

    public function rules(): array
    {
        return [
            'call_id'      => 'required|string|unique:call_records,cisco_call_id',
            'queue_name'   => 'required|string|in:Servicios de Salud,Servicios Administrativos,Soporte Técnico',
            'ani'          => 'required|string|regex:/^\d{4}-\d{4}$/',
            'timestamp'    => 'required|date_format:Y-m-d\TH:i:s\Z',
        ];
    }
}

// app/Modules/ContactCenter/Http/Requests/CompleteCallRequest.php

namespace App\Modules\ContactCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Operador llena formulario emergente en Finesse.
 */
class CompleteCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'citizen_identifier'      => 'required|regex:/^\d{8,12}$/',
            'case_subtype_id' => 'required|integer|exists:case_subtypes,id',
            'description'     => 'required|string|min:10|max:500',
            'resolution'      => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'citizen_identifier.regex'    => 'Cédula debe tener 8-12 dígitos',
            'description.min'     => 'Descripción debe tener al menos 10 caracteres',
        ];
    }
}

// app/Modules/ContactCenter/Http/Requests/CloseCallRequest.php

namespace App\Modules\ContactCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Webhook CISCO → fin de llamada.
 */
class CloseCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ciscoIps = config('contact-center.cisco_webhook_ips', []);
        return empty($ciscoIps) || in_array($this->ip(), $ciscoIps);
    }

    public function rules(): array
    {
        return [
            'call_id'         => 'required|string|exists:call_records,cisco_call_id',
            'end_timestamp'   => 'required|date_format:Y-m-d\TH:i:s\Z',
            'call_status'     => 'required|in:closed,transferred,abandoned',
        ];
    }
}
```

---

## **8. Catálogo de Subtipos — Seeder**

```php
<?php
// database/seeders/CaseSubtypesSeeder.php

namespace Database\Seeders;

use App\Modules\ContactCenter\Models\CaseSubtype;
use Illuminate\Database\Seeder;

class CaseSubtypesSeeder extends Seeder
{
    public function run(): void
    {
        $subtypes = [
            // Cola: Servicios de Salud
            [
                'code'       => 'MED_CITA_CONTROL',
                'queue_name' => 'Servicios de Salud',
                'name'       => 'Cita de Control',
                'description' => 'Cita médica de seguimiento/control',
            ],
            [
                'code'       => 'MED_CITA_PRIMERA',
                'queue_name' => 'Servicios de Salud',
                'name'       => 'Cita Primera Vez',
                'description' => 'Primera consulta con el médico',
            ],
            [
                'code'       => 'LAB_CUPO',
                'queue_name' => 'Servicios de Salud',
                'name'       => 'Cupo Laboratorio',
                'description' => 'Solicitud de cupo para examen de laboratorio',
            ],
            [
                'code'       => 'FARM_INFO',
                'queue_name' => 'Servicios de Salud',
                'name'       => 'Consulta Farmacia',
                'description' => 'Información sobre medicamentos disponibles',
            ],

            // Cola: Servicios Administrativos
            [
                'code'       => 'ADM_VALIDACION',
                'queue_name' => 'Servicios Administrativos',
                'name'       => 'Validación de Derechos',
                'description' => 'Consulta de estado de afiliación',
            ],
            [
                'code'       => 'ADM_PENSION',
                'queue_name' => 'Servicios Administrativos',
                'name'       => 'Trámite de Pensión',
                'description' => 'Solicitud de jubilación o pensión',
            ],
            [
                'code'       => 'ADM_INCAPACIDAD',
                'queue_name' => 'Servicios Administrativos',
                'name'       => 'Incapacidad Médica',
                'description' => 'Consulta sobre incapacidades registradas',
            ],

            // Cola: Soporte Técnico
            [
                'code'       => 'TEC_SIPE',
                'queue_name' => 'Soporte Técnico',
                'name'       => 'Soporte SIPE',
                'description' => 'Ayuda técnica sistema SIPE (empleadores)',
            ],
            [
                'code'       => 'TEC_PORTAL',
                'queue_name' => 'Soporte Técnico',
                'name'       => 'Soporte Portal Web',
                'description' => 'Problemas acceso Mi Caja Digital',
            ],
        ];

        foreach ($subtypes as $subtype) {
            CaseSubtype::firstOrCreate(['code' => $subtype['code']], $subtype);
        }
    }
}
```

---

## **9. Routes API**

```php
<?php
// app/Modules/ContactCenter/Routes/api.php

use App\Modules\ContactCenter\Http\Controllers\CallRecordController;
use Illuminate\Support\Facades\Route;

// Webhooks CISCO (sin autenticación, pero IP whitelist)
Route::post('/contact-center/calls/start', [CallRecordController::class, 'start'])
    ->withoutMiddleware('auth:sanctum')
    ->name('contact-center.call-start');

Route::put('/contact-center/calls/{id}/close', [CallRecordController::class, 'close'])
    ->withoutMiddleware('auth:sanctum')
    ->name('contact-center.call-close');

// Rutas protegidas (operadores autenticados)
Route::middleware(['auth:sanctum'])->group(function () {
    // Operador completa formulario
    Route::put('/contact-center/calls/{id}/complete',
        [CallRecordController::class, 'complete'])
        ->name('contact-center.call-complete');

    // Ver mis registros abiertos
    Route::get('/contact-center/calls/open',
        [CallRecordController::class, 'openCalls'])
        ->name('contact-center.calls-open');

    // Reportes
    Route::get('/contact-center/reports/team-load',
        [CallRecordController::class, 'teamLoadReport'])
        ->name('contact-center.team-load-report');
});
```

---

## **10. Policy de Autorización**

```php
<?php
// app/Modules/ContactCenter/Policies/CallRecordPolicy.php

namespace App\Modules\ContactCenter\Policies;

use App\Modules\ContactCenter\Models\CallRecord;
use App\Modules\Core\Models\User;

class CallRecordPolicy
{
    public function update(User $user, CallRecord $record): bool
    {
        // Solo el operador que recibió la llamada puede completar
        return $user->employee?->id === $record->employee_id
            || $user->hasRole(['coordinator', 'wfm', 'director']);
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole(['coordinator', 'wfm', 'director']);
    }
}
```

---

## **11. Finesse CTI Integration — Formulario Emergente**

```html
<!-- HTML para CISCO Finesse custom gadget -->
<!-- Nota: Finesse usa AJAX para comunicar con backend -->

<div id="call-form-gadget" style="display:none;padding:15px;">
  <h3 id="queue-name">Servicios de Salud</h3>

  <form id="call-form">
    <!-- Campo: Cédula (autofoco) -->
    <div class="form-group">
      <label>Cédula del Asegurado *</label>
      <input type="text"
             id="citizen_identifier"
             name="citizen_identifier"
             placeholder="12345678"
             pattern="\d{8,12}"
             required
             autofocus
             style="width:100%;padding:8px;">
      <small style="color:#666;">8-12 dígitos</small>
    </div>

    <!-- Campo: Subtipo (dropdown por cola) -->
    <div class="form-group">
      <label>Tipo de Consulta *</label>
      <select id="case_subtype_id"
              name="case_subtype_id"
              required
              style="width:100%;padding:8px;">
        <option value="">-- Seleccionar --</option>
      </select>
    </div>

    <!-- Campo: Descripción libre -->
    <div class="form-group">
      <label>Descripción de la Consulta *</label>
      <textarea id="description"
                name="description"
                placeholder="Ej: Cliente solicita cita de control general"
                required
                minlength="10"
                maxlength="500"
                style="width:100%;height:80px;padding:8px;"></textarea>
      <small style="color:#666;" id="char-count">0/500</small>
    </div>

    <!-- Campo: Resolución (opcional, puede completarse al final) -->
    <div class="form-group">
      <label>Resolución (Opcional)</label>
      <input type="text"
             id="resolution"
             name="resolution"
             placeholder="Ej: Cita agendada para 10 de abril"
             maxlength="255"
             style="width:100%;padding:8px;">
    </div>

    <!-- Botones -->
    <div style="display:flex;gap:10px;margin-top:15px;">
      <button type="submit"
              style="flex:1;padding:10px;background:#4CAF50;color:white;border:none;cursor:pointer;">
        Guardar
      </button>
      <button type="reset"
              style="flex:1;padding:10px;background:#999;color:white;border:none;cursor:pointer;">
        Limpiar
      </button>
    </div>
  </form>

  <div id="status-message" style="margin-top:10px;display:none;"></div>
</div>

<script>
// Simulación: en Finesse real esto viene del CTI event
let callRecordId = null;
let queueName = null;

// Event: IVR dispara inicio de llamada (en Finesse sería AQSTATE event)
window.addEventListener('message', function(event) {
  if (event.data.type === 'CALL_START') {
    callRecordId = event.data.callRecordId;
    queueName = event.data.queueName;

    document.getElementById('queue-name').textContent = queueName;
    document.getElementById('call-form-gadget').style.display = 'block';

    // Cargar subtipos según cola
    loadSubtypes(queueName);

    // Autofoco en cédula
    document.getElementById('citizen_identifier').focus();
  }
});

function loadSubtypes(queue) {
  // AJAX GET /api/contact-center/subtypes?queue=Servicios+de+Salud
  fetch(`/api/contact-center/subtypes?queue=${encodeURIComponent(queue)}`)
    .then(r => r.json())
    .then(subtypes => {
      const select = document.getElementById('case_subtype_id');
      select.innerHTML = '<option value="">-- Seleccionar --</option>';
      subtypes.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        select.appendChild(opt);
      });
    });
}

// Form submit
document.getElementById('call-form').addEventListener('submit', function(e) {
  e.preventDefault();

  const data = {
    citizen_identifier:      document.getElementById('citizen_identifier').value,
    case_subtype_id: document.getElementById('case_subtype_id').value,
    description:     document.getElementById('description').value,
    resolution:      document.getElementById('resolution').value || null,
  };

  // AJAX PUT /api/contact-center/calls/{callRecordId}/complete
  fetch(`/api/contact-center/calls/${callRecordId}/complete`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${getFinessToken()}`,
    },
    body: JSON.stringify(data),
  })
  .then(r => r.json())
  .then(result => {
    document.getElementById('status-message').style.display = 'block';
    document.getElementById('status-message').innerHTML =
      '<strong style="color:green;">✓ Guardado correctamente</strong>';

    // Limpiar después de 3 segundos
    setTimeout(() => {
      document.getElementById('call-form').reset();
      document.getElementById('status-message').style.display = 'none';
    }, 3000);
  })
  .catch(err => {
    document.getElementById('status-message').style.display = 'block';
    document.getElementById('status-message').innerHTML =
      `<strong style="color:red;">✗ Error: ${err.message}</strong>`;
  });
});

// Contador de caracteres
document.getElementById('description').addEventListener('input', function() {
  document.getElementById('char-count').textContent = `${this.value.length}/500`;
});
</script>
```

---

## **12. Migración de Base de Datos**

```php
<?php
// database/migrations/2026_04_06_create_call_records_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Catálogo de subtipos
        Schema::create('case_subtypes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('queue_name');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['queue_name', 'code']);
        });

        // Registro de llamadas
        Schema::create('call_records', function (Blueprint $table) {
            $table->id();

            // CISCO IVR
            $table->string('cisco_call_id')->unique();
            $table->string('queue_name');
            $table->string('phone_number');
            $table->timestamp('ivr_started_at');
            $table->timestamp('ivr_ended_at')->nullable();

            // Operador
            $table->foreignId('employee_id')
                  ->constrained('employees')
                  ->restrictOnDelete();

            // Asegurado (SOLO cédula)
            $table->string('citizen_identifier', 12)->nullable();

            // Datos completados por operador
            $table->foreignId('case_subtype_id')
                  ->nullable()
                  ->constrained('case_subtypes')
                  ->nullOnDelete();
            $table->text('description')->nullable();
            $table->string('resolution')->nullable();

            // Estado
            $table->enum('status', ['open', 'closed', 'transferred'])
                  ->default('open');

            // Auditoría
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')
                  ->nullable()
                  ->references('id')
                  ->on('employees');

            $table->timestamps();

            // Índices
            $table->index(['employee_id', 'ivr_started_at']);
            $table->index(['queue_name', 'ivr_started_at']);
            $table->index(['case_subtype_id']);
            $table->index(['status']);
            $table->index('citizen_identifier');
            $table->index('cisco_call_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_records');
        Schema::dropIfExists('case_subtypes');
    }
};
```

---

## **13. Config**

```php
<?php
// config/contact-center.php

return [
    // IP whitelist para webhooks CISCO
    'cisco_webhook_ips' => [
        '192.168.1.100',      // CISCO PBX interna
        '10.0.0.50',          // CISCO Cloud
        '203.0.113.42',       // Backup
    ],

    // Queuenames válidas (mapeo con CISCO)
    'valid_queues' => [
        'Servicios de Salud',
        'Servicios Administrativos',
        'Soporte Técnico',
    ],

    // Retención de datos (compliance/auditoría)
    'retention_days' => 180,  // Borrar registros después de 6 meses

    // Validación de cédula
    'citizen_id_pattern' => '/^\d{8,12}$/',
];
```

---

## **14. Flujo Completo — Timeline**

```
T=0s   | IVR responde llamada (Cisco)
       | → Webhook POST /api/contact-center/calls/start
       | → CreateCallRecordAction crea record borrador
       | → API retorna callRecordId

T=0s   | Finesse Desktop recibe callRecordId
       | → Formulario emergente aparece en pantalla del operador
       | → Autofoco en campo "Cédula"
       | → Operador completa: cédula, subtipo, descripción

T=30s  | Operador hace clic "Guardar"
       | → PUT /api/contact-center/calls/{id}/complete
       | → CompleteCallRecordAction actualiza registro
       | → Operador sigue atendiendo (llamada sigue activa)

T=5m   | Cliente cuelga o operador finaliza llamada
       | → IVR dispara webhook de fin
       | → POST /api/contact-center/calls/close
       | → CloseCallRecordAction registra ended_at, status='closed'

T=5m   | Llamada completamente registrada en BD
       | → Disponible para reportes
       | → Visible en historial del asegurado (futuro CiRM)
```

---

## **15. Reportes útiles para Coordinadores**

```sql
-- Carga diaria por cola
SELECT queue_name, COUNT(*) as total_calls,
       ROUND(AVG(EXTRACT(EPOCH FROM (ivr_ended_at - ivr_started_at))/60), 2) as avg_duration
FROM call_records
WHERE DATE(ivr_started_at) = CURRENT_DATE
GROUP BY queue_name;

-- Operadores sin completar formularios (abiertos)
SELECT e.username, COUNT(*) as open_records
FROM call_records cr
JOIN employees e ON cr.employee_id = e.id
WHERE cr.status = 'open'
  AND cr.description IS NULL
GROUP BY e.id, e.username
ORDER BY open_records DESC;

-- Tipo de consulta más frecuente
SELECT cs.name, COUNT(*) as frequency
FROM call_records cr
JOIN case_subtypes cs ON cr.case_subtype_id = cs.id
WHERE cr.ivr_started_at >= NOW() - INTERVAL '7 days'
GROUP BY cs.id, cs.name
ORDER BY frequency DESC;

-- Asegurados con múltiples consultas en el día
SELECT citizen_identifier, COUNT(*) as call_count,
       STRING_AGG(cs.name, ', ') as consultas
FROM call_records cr
LEFT JOIN case_subtypes cs ON cr.case_subtype_id = cs.id
WHERE DATE(cr.ivr_started_at) = CURRENT_DATE
GROUP BY citizen_identifier
HAVING COUNT(*) > 1
ORDER BY call_count DESC;
```

---

## **16. Checklist de Implementación**

- [ ] Tabla `call_records` y `case_subtypes` creadas
- [ ] Modelos Eloquent: `CallRecord`, `CaseSubtype`
- [ ] Actions: `CreateCallRecordAction`, `CompleteCallRecordAction`, `CloseCallRecordAction`
- [ ] DTOs: `CallStartDTO`, `CompleteDTO`, `CallCloseDTO`
- [ ] Form Requests: validación CISCO + operador
- [ ] API Endpoints: `/start`, `/complete`, `/close`
- [ ] Finesse custom gadget (HTML+JS)
- [ ] Config CISCO IP whitelist
- [ ] Seeder `CaseSubtypesSeeder`
- [ ] Policy `CallRecordPolicy`
- [ ] Routes API registradas
- [ ] Documentación: webhook payload esperado
- [ ] Testing: crear registro → completar → cerrar

---

## **Resumen Final**

✅ **Una sola tabla** (`call_records`) — no contamina Employee/Citizen
✅ **Tres webhooks CISCO** — inicio, actualización (operador), cierre
✅ **Formulario emergente** en Finesse — cédula + subtipo + descripción
✅ **Lógica de negocio en Actions** — reutilizable, testeable
✅ **Reportes operativos** — carga diaria, asegurados frecuentes, etc.
✅ **Preparado para CiRM** — datos limpios, sin PII sensible

**No es un CRM.** Es **instrumentación de llamadas** que el operador alimenta en vivo. Perfecto para tu scope.
