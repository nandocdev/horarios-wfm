<?php

declare(strict_types=1);

use App\Modules\ConnectModule\Actions\SyncCuicDataAction;
use App\Modules\ConnectModule\Enums\ContactType;
use App\Modules\ConnectModule\Enums\ParticipantType;
use App\Modules\ConnectModule\Models\CallRecord;
use App\Modules\ConnectModule\Services\CuicReportService;
use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

it('syncs detailed call by call ccdr records with enriched fields and enums', function () {
    config()->set('contact-center.cuic.base_url', 'https://cuic.test');
    config()->set('contact-center.cuic.username', 'user');
    config()->set('contact-center.cuic.password', 'secret');
    config()->set('contact-center.cuic.reports.detailed_call_by_call_ccdr', [
        'id' => 'ccdr-report-id',
        'locale' => 'es_ES',
        'params' => [
            'start_datetime' => 'start-param',
            'end_datetime' => 'end-param',
            'current_user' => 'user-param',
        ],
    ]);

    $rows = [
        [
            'node_id' => 1,
            'session_id' => '10001',
            'sequence_num' => 1,
            'start_time' => 1786590000000,
            'end_time' => 1786590300000,
            'contact_type' => 1, // Incoming
            'contact_disposition' => 2, // Handled
            'originator_type' => 3, // Unknown / Externo
            'originator_dn' => '67890123',
            'originator_id' => null,
            'destination_type' => 1, // Agent
            'destination_dn' => '4005',
            'destination_id' => 'fcastillo',
            'dialed_number' => '5031000',
            'original_dialed_number' => '5031000',
            'application_name' => 'App_CSS_Citas',
            'talk_time' => 180,
            'hold_time' => 25,
            'ring_time' => 10,
            'queue_time' => 35,
            'work_time' => 50,
        ],
        [
            'node_id' => 1,
            'session_id' => '10001',
            'sequence_num' => 2, // Segment 2: Transfer internal
            'start_time' => 1786590300000,
            'end_time' => 1786590500000,
            'contact_type' => 5, // Transfer In
            'contact_disposition' => 2, // Handled
            'originator_type' => 1, // Agent
            'originator_dn' => '4005',
            'originator_id' => 'fcastillo',
            'destination_type' => 1, // Agent
            'destination_dn' => '4009',
            'destination_id' => 'arenteria',
            'dialed_number' => '4009',
            'original_dialed_number' => '5031000',
            'application_name' => 'App_CSS_Citas',
            'talk_time' => 150,
            'hold_time' => 10,
            'ring_time' => 5,
            'queue_time' => 0,
            'work_time' => 35,
        ],
    ];

    Http::preventStrayRequests();
    Http::fake([
        'https://cuic.test/cuic/rest/es_ES/reports/execute/newRest/*' => Http::response([
            'dataSetId' => 'dataset-ccdr-1',
        ]),
        'https://cuic.test/cuic/rest/es_ES/reports/execute/dataset-ccdr-1*' => Http::response([
            'executionResult' => [
                'status' => 'READY',
                'jsonData' => json_encode($rows, JSON_THROW_ON_ERROR),
            ],
        ]),
    ]);

    $emp1 = Employee::factory()->create([
        'cisco_username' => 'fcastillo',
    ]);
    $emp2 = Employee::factory()->create([
        'cisco_username' => 'arenteria',
    ]);

    $service = new CuicReportService;
    $pollInterval = (new ReflectionClass($service))->getProperty('pollInterval');
    $pollInterval->setAccessible(true);
    $pollInterval->setValue($service, 0);

    $lookup = $this->mock(EmployeeLookupRepositoryInterface::class);
    $lookup->shouldReceive('resolve')
        ->with('fcastillo', null)
        ->andReturn($emp1->id);
    $lookup->shouldReceive('resolve')
        ->with('arenteria', null)
        ->andReturn($emp2->id);

    $action = new SyncCuicDataAction($service, $lookup);
    $stats = $action->execute(
        Carbon::create(2026, 8, 13, 0),
        Carbon::create(2026, 8, 13, 1),
    );

    expect($stats['calls'])->toBe(2);

    $seg1 = CallRecord::where('cisco_call_id', '10001')
        ->where('sequence_number', 1)
        ->firstOrFail();

    expect($seg1->node_id)->toBe(1)
        ->and($seg1->contact_type)->toBe(ContactType::Incoming)
        ->and($seg1->contact_type->isInbound())->toBeTrue()
        ->and($seg1->originator_type)->toBe(ParticipantType::Unknown)
        ->and($seg1->destination_type)->toBe(ParticipantType::Agent)
        ->and($seg1->destination_id)->toBe('fcastillo')
        ->and($seg1->employee_id)->toBe($emp1->id)
        ->and($seg1->talk_time)->toBe(180)
        ->and($seg1->hold_time)->toBe(25)
        ->and($seg1->queue_time)->toBe(35)
        ->and($seg1->work_time)->toBe(50)
        ->and($seg1->status)->toBe('closed');

    $seg2 = CallRecord::where('cisco_call_id', '10001')
        ->where('sequence_number', 2)
        ->firstOrFail();

    expect($seg2->node_id)->toBe(1)
        ->and($seg2->contact_type)->toBe(ContactType::TransferIn)
        ->and($seg2->contact_type->isInbound())->toBeTrue()
        ->and($seg2->originator_type)->toBe(ParticipantType::Agent)
        ->and($seg2->originator_id)->toBe('fcastillo')
        ->and($seg2->destination_type)->toBe(ParticipantType::Agent)
        ->and($seg2->destination_id)->toBe('arenteria')
        ->and($seg2->employee_id)->toBe($emp2->id)
        ->and($seg2->hold_time)->toBe(10);
});
