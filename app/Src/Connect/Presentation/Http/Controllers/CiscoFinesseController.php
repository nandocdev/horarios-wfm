<?php

declare(strict_types=1);

namespace App\Src\Connect\Presentation\Http\Controllers;

use App\Src\Connect\Application\DTOs\AgentSnapshotFilterDTO;
use App\Src\Connect\Application\Handlers\FetchCiscoAgentSnapshotHandler;
use App\Src\Connect\Infrastructure\Persistence\EloquentCallRecord;
use App\Src\Connect\Presentation\Http\Requests\FetchCiscoAgentSnapshotRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Throwable;

class CiscoFinesseController extends Controller
{
    public function agentSnapshot(
        FetchCiscoAgentSnapshotRequest $request,
        FetchCiscoAgentSnapshotHandler $handler,
    ): JsonResponse {
        try {
            $dto = new AgentSnapshotFilterDTO(
                employeeIds: $request->input('employee_ids', []),
                date: $request->input('date'),
            );

            $results = $handler->handle($dto);

            return response()->json($results);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo consultar Cisco Finesse.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ], 502);
        }
    }

    public function myDashboard(): JsonResponse
    {
        $user = auth()->user();
        $employee = $user?->employee;

        if (! $employee) {
            return response()->json(['message' => 'No employee linked to user.'], 404);
        }

        $this->authorize('viewAny', EloquentCallRecord::class);

        $stats = EloquentCallRecord::where('employee_id', $employee->id)
            ->selectRaw('COUNT(*) as total_calls')
            ->selectRaw('AVG(talk_time) as avg_talk_time')
            ->first();

        return response()->json([
            'employee_id' => $employee->id,
            'total_calls' => (int) ($stats->total_calls ?? 0),
            'avg_talk_time' => round((float) ($stats->avg_talk_time ?? 0), 0),
        ]);
    }
}
