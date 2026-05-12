<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ConnectModule\Actions\FetchCiscoAgentSnapshotAction;
use App\Modules\ConnectModule\Http\Requests\FetchCiscoAgentSnapshotRequest;
use Illuminate\Http\JsonResponse;
use Throwable;

class CiscoFinesseController extends Controller
{
    public function agentSnapshot(
        FetchCiscoAgentSnapshotRequest $request,
        FetchCiscoAgentSnapshotAction $action,
    ): JsonResponse {
        try {
            $payload = $action->execute($request->validated()['username'] ?? null);

            return response()->json($payload);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'No se pudo consultar Cisco Finesse.',
                'error' => config('app.debug') ? $exception->getMessage() : null,
            ], 502);
        }
    }
}
