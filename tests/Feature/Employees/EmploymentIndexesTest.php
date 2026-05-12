<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('has team_id, deleted_at and composite index on employees and parent index on employment_statuses', function () {
    expect(Schema::hasColumn('employees', 'team_id'))->toBeTrue();
    expect(Schema::hasColumn('employees', 'deleted_at'))->toBeTrue();
    expect(Schema::hasColumn('employment_statuses', 'parent_id'))->toBeTrue();

    $driver = DB::getDriverName();

    if ($driver === 'sqlite') {
        $employeesIndexes = collect(DB::select("PRAGMA index_list('employees');"))->pluck('name')->toArray();
        $statusIndexes = collect(DB::select("PRAGMA index_list('employment_statuses');"))->pluck('name')->toArray();
    } else {
        $employeesIndexes = collect(DB::select("SELECT indexname AS name FROM pg_indexes WHERE tablename = 'employees';"))->pluck('name')->toArray();
        $statusIndexes = collect(DB::select("SELECT indexname AS name FROM pg_indexes WHERE tablename = 'employment_statuses';"))->pluck('name')->toArray();
    }

    expect(in_array('employees_team_status_deleted_idx', $employeesIndexes))->toBeTrue();
    expect(in_array('employment_statuses_parent_idx', $statusIndexes))->toBeTrue();
});
