<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Http\Controllers;

use App\Src\Connect\Application\DTOs\CallCloseDTO;
use App\Src\Connect\Application\DTOs\CallCompleteDTO;
use App\Src\Connect\Application\DTOs\CallStartDTO;
use App\Src\Connect\Application\Handlers\CloseCallRecordHandler;
use App\Src\Connect\Application\Handlers\CompleteCallRecordHandler;
use App\Src\Connect\Application\Handlers\CreateCallRecordHandler;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecord;
use App\Src\Connect\Presentation\Http\Requests\CloseCallRequest;
use App\Src\Connect\Presentation\Http\Requests\CompleteCallRequest;
use App\Src\Connect\Presentation\Http\Requests\CreateCallRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class CallRecordController extends Controller
{
    public function store(CreateCallRequest $request, CreateCallRecordHandler $handler): JsonResponse
    {
        $dto = new CallStartDTO(
            queueId: (int) $request->input('queue_id'),
            employeeId: (int) $request->input('employee_id'),
            phoneNumber: $request->input('phone_number'),
            citizenIdentifier: $request->input('citizen_identifier'),
            ivrStartedAt: $request->input('ivr_started_at'),
        );

        $record = $handler->handle($dto);

        return response()->json([
            'id' => $record->id(),
            'status' => $record->status(),
        ], 201);
    }

    public function complete(int $record, CompleteCallRequest $request, CompleteCallRecordHandler $handler): JsonResponse
    {
        $eloquent = EloquentCallRecord::findOrFail($record);
        $this->authorize('update', $eloquent);

        $dto = new CallCompleteDTO(
            callRecordId: (int) $eloquent->id,
            talkTime: (int) $request->input('talk_time'),
            handleTime: (int) $request->input('handle_time'),
            contactDisposition: (int) $request->input('contact_disposition'),
        );

        $updated = $handler->handle($dto);

        return response()->json([
            'id' => $updated->id(),
            'status' => $updated->status(),
        ]);
    }

    public function close(int $record, CloseCallRequest $request, CloseCallRecordHandler $handler): JsonResponse
    {
        $eloquent = EloquentCallRecord::findOrFail($record);
        $this->authorize('update', $eloquent);

        $dto = new CallCloseDTO(
            callRecordId: (int) $eloquent->id,
        );

        $closed = $handler->handle($dto);

        return response()->json([
            'id' => $closed->id(),
            'status' => $closed->status(),
        ]);
    }

    public function show(int $record): JsonResponse
    {
        $eloquent = EloquentCallRecord::with(['queue', 'caseSubtype', 'employee'])->findOrFail($record);
        $this->authorize('view', $eloquent);

        return response()->json($eloquent);
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', EloquentCallRecord::class);

        $records = EloquentCallRecord::with(['queue', 'caseSubtype', 'employee'])
            ->orderByDesc('ivr_started_at')
            ->paginate(15);

        return response()->json($records);
    }
}
