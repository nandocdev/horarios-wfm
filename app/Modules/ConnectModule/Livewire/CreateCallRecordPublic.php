<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Livewire;

use App\Modules\ConnectModule\Actions\CreateManualCallRecordAction;
use App\Modules\ConnectModule\DTOs\ManualCallRecordDTO;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\Channel;
use App\Modules\PersonnelModule\Models\Employee;
use Flux;
use Livewire\Attributes\Url;
use Livewire\Component;

class CreateCallRecordPublic extends Component
{
    #[Url]
    public string $cola = '';

    #[Url]
    public string $telefono = '';

    #[Url]
    public string $username = '';

    public int $queueId = 0;

    public string $channelId = '';

    public bool $saved = false;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        if ($this->cola) {
            $queue = CallQueue::where('name', $this->cola)
                ->orWhere('id', is_numeric($this->cola) ? $this->cola : 0)
                ->first();

            if ($queue) {
                $this->queueId = $queue->id;
                $this->channelId = $queue->channel_id;
            }
        }

        if (empty($this->channelId)) {
            $firstChannel = Channel::active()->orderBy('name')->first();
            if ($firstChannel) {
                $this->channelId = $firstChannel->id;
                if (empty($this->queueId)) {
                    $this->queueId = CallQueue::where('channel_id', $firstChannel->id)->active()->orderBy('name')->value('id') ?? 0;
                }
            }
        }
    }

    public function save(CreateManualCallRecordAction $action): void
    {
        $this->errorMessage = null;

        if (empty($this->telefono)) {
            $this->errorMessage = 'El parámetro teléfono es requerido.';

            return;
        }

        if (empty($this->queueId)) {
            $this->errorMessage = 'El parámetro cola es requerido o la cola especificada no existe.';

            return;
        }

        $employeeId = auth()->user()?->employee?->id;

        if (! $employeeId && $this->username) {
            $employee = Employee::where('username', $this->username)
                ->orWhere('cisco_username', $this->username)
                ->first();

            if ($employee) {
                $employeeId = $employee->id;
            }
        }

        $dto = ManualCallRecordDTO::fromForm([
            'queue_id' => $this->queueId,
            'phone_number' => $this->telefono,
            'citizen_identifier' => '0-000-000',
            'case_subtype_id' => 0,
            'description' => 'Registro automático desde endpoint público. Cola: '.$this->cola.', Usuario: '.($this->username ?: 'N/A'),
            'status' => 'open',
            'employee_id' => $employeeId,
        ]);

        $action->execute($dto);

        $this->saved = true;

        if (auth()->check()) {
            Flux::toast('Llamada registrada correctamente.', variant: 'success');
        }
    }

    public function render()
    {
        $isAuthenticated = auth()->check();

        $data = [
            'isAuthenticated' => $isAuthenticated,
            'queueName' => $this->queueId ? CallQueue::find($this->queueId)?->name : null,
        ];

        return view('connect::livewire.create-call-record-public', $data)
            ->layout($isAuthenticated ? 'layouts.app' : null, $isAuthenticated ? ['title' => 'Nuevo Registro de Llamada'] : []);
    }
}
