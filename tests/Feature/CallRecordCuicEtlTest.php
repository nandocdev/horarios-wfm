<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Actions\SyncCuicDataAction;
use App\Modules\ConnectModule\Models\CallQueue;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Services\CuicReportService;
use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

it('maps the documented CUIC call fields and only assigns a matching queue', function () {
    config()->set('contact-center.cuic.base_url', 'https://cuic.test');
    config()->set('contact-center.cuic.username', 'user');
    config()->set('contact-center.cuic.password', 'secret');
    config()->set('contact-center.cuic.reports.agent_csq_detail', [
        'id' => 'report-id',
        'locale' => 'es_ES',
        'params' => [
            'start_datetime' => 'start-param',
            'end_datetime' => 'end-param',
            'current_user' => 'user-param',
        ],
    ]);

    $queue = CallQueue::create([
        'finesse_queue_id' => 42,
        'name' => 'CSQ Ventas',
        'is_active' => true,
    ]);

    $rows = [
        [
            'session_id_seq' => '1-12345-0',
            'sequence_num' => 0,
            'start_time' => 1775178000000,
            'end_time' => 1775178123000,
            'contact_disposition' => 4,
            'originator_dn' => '60000001',
            'destination_dn' => '4001',
            'called_number' => '800-1234',
            'application_name' => 'IVR Principal',
            'csq_names' => 'CSQ Ventas',
            'queue_time' => 20,
            'ring_time' => 5,
            'talk_time' => 90,
            'work_time' => 10,
            'resource_name' => null,
            'agent_name' => null,
        ],
        [
            'session_id_seq' => '1-12346-0',
            'sequence_num' => 0,
            'start_time' => 1775178200000,
            'end_time' => 1775178260000,
            'contact_disposition' => 7,
            'originator_dn' => '60000002',
            'destination_dn' => '4002',
            'csq_names' => 'CSQ inexistente',
            'queue_time' => 30,
            'ring_time' => 0,
            'talk_time' => 0,
            'work_time' => 0,
        ],
    ];

    Http::preventStrayRequests();
    Http::fake([
        'https://cuic.test/cuic/rest/es_ES/reports/execute/newRest/*' => Http::response([
            'dataSetId' => 'dataset-1',
        ]),
        'https://cuic.test/cuic/rest/es_ES/reports/execute/dataset-1*' => Http::response([
            'executionResult' => [
                'status' => 'READY',
                'jsonData' => json_encode($rows, JSON_THROW_ON_ERROR),
            ],
        ]),
    ]);

    $service = new CuicReportService;
    $pollInterval = (new ReflectionClass($service))->getProperty('pollInterval');
    $pollInterval->setAccessible(true);
    $pollInterval->setValue($service, 0);

    $lookup = $this->mock(EmployeeLookupRepositoryInterface::class);
    $lookup->shouldReceive('resolve')->twice()->andReturn(null);

    $action = new SyncCuicDataAction($service, $lookup);
    $count = $action->execute(
        Carbon::create(2026, 4, 2, 0),
        Carbon::create(2026, 4, 2, 1),
    );

    expect($count['calls'])->toBe(2);

    $aborted = CallRecord::where('cisco_call_id', '1-12345-0')->firstOrFail();
    expect($aborted->sequence_number)->toBe(0)
        ->and($aborted->queue_id)->toBe($queue->id)
        ->and($aborted->phone_number)->toBe('60000001')
        ->and($aborted->destination_number)->toBe('4001')
        ->and($aborted->dialed_number)->toBe('800-1234')
        ->and($aborted->application_name)->toBe('IVR Principal')
        ->and($aborted->status)->toBe('aborted');

    $rejected = CallRecord::where('cisco_call_id', '1-12346-0')->firstOrFail();
    expect($rejected->status)->toBe('rejected')
        ->and($rejected->queue_id)->toBeNull();
});
