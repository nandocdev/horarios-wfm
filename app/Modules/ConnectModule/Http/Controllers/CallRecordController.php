<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ConnectModule\Actions\CloseCallRecordAction;
use App\Modules\ConnectModule\Actions\CompleteCallRecordAction;
use App\Modules\ConnectModule\Actions\CreateCallRecordAction;
use App\Modules\ConnectModule\DTOs\CallCloseDTO;
use App\Modules\ConnectModule\DTOs\CallCompleteDTO;
use App\Modules\ConnectModule\DTOs\CallStartDTO;
use App\Modules\ConnectModule\Http\Requests\CloseCallRequest;
use App\Modules\ConnectModule\Http\Requests\CompleteCallRequest;
use App\Modules\ConnectModule\Http\Requests\CreateCallRequest;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Models\CaseSubtype;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallRecordController extends Controller
{
    public function start(CreateCallRequest $request, CreateCallRecordAction $action): JsonResponse
    {
        $dto = CallStartDTO::fromCiscoWebhook($request->validated());
        $record = $action->execute($dto);

        return response()->json([
            'callRecordId' => $record->id,
            'queueName' => $record->queue?->name ?? '',
            'startedAt' => $record->ivr_started_at,
        ], 201);
    }

    public function complete(int $id, CompleteCallRequest $request, CompleteCallRecordAction $action): JsonResponse
    {
        $record = CallRecord::findOrFail($id);
        $this->authorize('update', $record);

        $data = array_merge($request->validated(), [
            'employee_id' => auth()->user()?->employee?->id,
        ]);

        $dto = CallCompleteDTO::fromForm($data);
        $updated = $action->execute($record, $dto);

        return response()->json($updated, 200);
    }

    public function close(int $id, CloseCallRequest $request, CloseCallRecordAction $action): JsonResponse
    {
        $record = CallRecord::findOrFail($id);

        $validated = $request->validated();
        if ($record->cisco_call_id !== $validated['call_id']) {
            abort(422, 'Payload call_id does not match record');
        }

        $dto = CallCloseDTO::fromCiscoWebhook($validated);
        $closed = $action->execute($record, $dto);

        return response()->json([
            'id' => $closed->id,
            'status' => $closed->status,
            'durationMinutes' => $closed->duration_minutes,
        ], 200);
    }

    public function openCalls(Request $request): JsonResponse
    {
        $records = CallRecord::open()
            ->with(['employee', 'caseSubtype'])
            ->orderByDesc('ivr_started_at')
            ->paginate(15);

        return response()->json($records);
    }

    public function subtypes(Request $request): JsonResponse
    {
        $queue = $request->query('queue');
        $subtypes = CaseSubtype::query()
            ->when($queue, fn ($query) => $query->byQueue($queue))
            ->orderBy('name')
            ->get(['id', 'name', 'queue_id']);

        return response()->json($subtypes);
    }
}
