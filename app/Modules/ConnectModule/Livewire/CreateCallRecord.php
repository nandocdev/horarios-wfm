<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\CreateManualCallRecordAction;
use App\Modules\ConnectModule\DTOs\ManualCallRecordDTO;
use App\Modules\ConnectModule\Livewire\Forms\CreateCallRecordForm;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\CaseSubtype;
use App\Modules\ConnectModule\Models\Channel;
use App\Modules\ConnectModule\Services\CitizenValidationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class CreateCallRecord extends Component
{
    public CreateCallRecordForm $form;

    #[Url(as: 'telefono')]
    public string $telefono = '';

    #[Url(as: 'cola')]
    public string $cola = '';

    public ?CallRecord $selectedRecord = null;

    public ?array $citizenData = null;

    public bool $isValidating = false;

    public function mount(): void
    {
        Gate::authorize('create', CallRecord::class);

        $this->form = new CreateCallRecordForm($this, 'form');

        // Cargar parámetros de URL si existen
        if ($this->telefono) {
            $this->form->phone_number = $this->telefono;
        }

        if ($this->cola) {
            // Intentar buscar la cola por nombre o ID
            $queue = CallQueue::where('name', $this->cola)
                ->orWhere('id', is_numeric($this->cola) ? $this->cola : 0)
                ->first();

            if ($queue) {
                $this->form->queue_id = $queue->id;
                $this->form->channel_id = $queue->channel_id;
            }
        }

        // Si no se asignó canal, tomar el primero activo
        if (empty($this->form->channel_id)) {
            $firstChannel = Channel::active()->orderBy('name')->first();
            if ($firstChannel) {
                $this->form->channel_id = $firstChannel->id;
                if (empty($this->form->queue_id)) {
                    $this->form->queue_id = CallQueue::where('channel_id', $firstChannel->id)->active()->orderBy('name')->value('id') ?? 0;
                }
            }
        }

        $this->form->status = 'open';
    }

    public function updatedFormChannelId($value): void
    {
        if (empty($value)) {
            $this->form->queue_id = CallQueue::active()->orderBy('name')->value('id') ?? 0;
        } else {
            $firstQueueId = CallQueue::where('channel_id', $value)->active()->orderBy('name')->value('id');
            $this->form->queue_id = $firstQueueId ?? CallQueue::active()->orderBy('name')->value('id') ?? 0;
        }

        $this->form->case_subtype_id = 0;
    }

    public function updatedFormQueueId($value): void
    {
        $this->form->case_subtype_id = 0;
    }

    public function save(CreateManualCallRecordAction $action): void
    {
        Gate::authorize('create', CallRecord::class);
        $this->form->validate();

        $validQueue = CallQueue::where('id', $this->form->queue_id)
            ->when($this->form->channel_id, fn ($q) => $q->where('channel_id', $this->form->channel_id))
            ->where('is_active', true)
            ->exists();

        if (! $validQueue) {
            $this->addError('form.queue_id', 'La cola seleccionada no es válida para el canal seleccionado.');

            return;
        }

        $validSubtype = CaseSubtype::where('id', $this->form->case_subtype_id)
            ->where('queue_id', $this->form->queue_id)
            ->exists();

        if (! $validSubtype) {
            $this->addError('form.case_subtype_id', 'El subtipo de caso seleccionado no pertenece a la cola seleccionada.');

            return;
        }

        $formData = $this->form->toArray();
        if (empty($formData['citizen_identifier'])) {
            $formData['citizen_identifier'] = '0-000-000';
        }

        $dto = ManualCallRecordDTO::fromForm(array_merge($formData, [
            'employee_id' => auth()->user()?->employee?->id,
        ]));

        $action->execute($dto);

        \Flux::toast('Nuevo registro de llamada creado correctamente.', variant: 'success');

        $this->form = new CreateCallRecordForm($this, 'form');
        // Mantener el canal si es posible
        $firstChannel = Channel::active()->orderBy('name')->first();
        if ($firstChannel) {
            $this->form->channel_id = $firstChannel->id;
            $this->form->queue_id = CallQueue::where('channel_id', $firstChannel->id)->active()->orderBy('name')->value('id') ?? 0;
        }
        $this->form->status = 'open';
    }

    public function showRecordDetails(CallRecord $record): void
    {
        $this->selectedRecord = $record->load(['queue.channel', 'caseSubtype', 'employee']);
        $this->dispatch('modal-show', name: 'record-details');
    }

    public function updatedFormCitizenIdentifier($value): void
    {
        if (empty($value) || strlen($value) < 5) {
            $this->citizenData = null;

            return;
        }

        $this->isValidating = true;

        try {
            $service = app(CitizenValidationService::class);
            $this->citizenData = $service->validate($value);
        } catch (\Exception $e) {
            $this->citizenData = null;
        } finally {
            $this->isValidating = false;
        }
    }

    public function getHistoryProperty()
    {
        if (empty($this->form->phone_number) || strlen($this->form->phone_number) < 4) {
            return collect();
        }

        return CallRecord::with(['queue.channel', 'caseSubtype'])
            ->where('phone_number', 'like', '%'.$this->form->phone_number.'%')
            ->orderBy('ivr_started_at', 'desc')
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function qualityCapsules(): array
    {
        $queueId = (int) $this->form->queue_id;
        
        $capsules = [
            'general' => [
                ['title' => 'Empatía en la Atención', 'content' => 'Recuerda sonreír y usar un tono de voz empático en cada interacción.', 'icon' => 'heart'],
                ['title' => 'Tiempos de Resolución', 'content' => 'Intenta mantener la llamada por debajo de 3 minutos sin sacrificar calidad.', 'icon' => 'clock'],
                ['title' => 'Validación de Identidad', 'content' => 'Siempre confirma los datos del asegurado/beneficiario antes de brindar información.', 'icon' => 'shield-check'],
            ],
            1 => [
                ['title' => 'Citas Médicas', 'content' => 'Verifica la disponibilidad en el sistema antes de cancelar una cita previa.', 'icon' => 'calendar'],
                ['title' => 'Recordatorio', 'content' => 'Ofrece al paciente enviar el comprobante de cita por correo electrónico.', 'icon' => 'envelope'],
            ],
            2 => [
                ['title' => 'Soporte Técnico', 'content' => 'Asegúrate de registrar el código de error exacto mencionado por el usuario.', 'icon' => 'wrench'],
                ['title' => 'Escalamiento', 'content' => 'Si no resuelves en 5 minutos, escala el caso a nivel 2 usando el ticket.', 'icon' => 'arrow-up-circle'],
            ]
        ];

        return $capsules[$queueId] ?? $capsules['general'];
    }

    #[Computed]
    public function callScript(): array
    {
        $queueId = (int) $this->form->queue_id;

        $scripts = [
            'general' => [
                'Saludo inicial: "Gracias por llamar, le atiende [Tu Nombre], ¿con quién tengo el gusto?"',
                'Escuchar activamente el motivo de la llamada.',
                'Validar número de cédula o identificación en el sistema.',
                'Brindar información o realizar la gestión solicitada.',
                'Preguntar: "¿Hay algo más en lo que pueda ayudarle hoy?"',
                'Despedida estándar e invitarlos a usar los canales digitales.',
            ],
            1 => [
                'Saludo inicial: "Centro de Contacto, le atiende [Tu Nombre], ¿en qué le puedo asistir?"',
                'Solicitar número de cédula para validar asegurado/beneficiario.',
                'Consultar especialidad y centro de atención deseado.',
                'Buscar disponibilidad y ofrecer fechas.',
                'Confirmar la cita y generar número de control.',
                'Despedida recordando el tiempo de antelación para la cita.',
            ],
            2 => [
                'Saludo técnico: "Soporte Técnico, soy [Tu Nombre], ¿cuál es su incidencia?"',
                'Solicitar datos de la terminal y ubicación.',
                'Realizar diagnóstico de primer nivel (ping, reinicio).',
                'Solucionar o documentar los pasos realizados en el ticket.',
                'Brindar número de ticket al usuario.',
            ]
        ];

        return $scripts[$queueId] ?? $scripts['general'];
    }

    public function render()
    {
        $subtypes = CaseSubtype::when($this->form->queue_id, fn ($query) => $query->byQueue($this->form->queue_id))
            ->orderBy('name')
            ->get();

        $queues = ! empty($this->form->channel_id)
            ? CallQueue::where('channel_id', $this->form->channel_id)->active()->orderBy('name')->get()
            : CallQueue::active()->orderBy('name')->get();

        return view('connect::livewire.create-call-record', [
            'subtypes' => $subtypes,
            'queues' => $queues,
            'channels' => Channel::active()->orderBy('name')->get(),
            'history' => $this->history,
            'qualityCapsules' => $this->qualityCapsules(),
            'callScript' => $this->callScript(),
        ])->layout('layouts.app');
    }
}
