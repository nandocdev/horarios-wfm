<?php

declare(strict_types=1);

namespace App\Src\Connect\Infrastructure\Persistence;

use App\Src\Connect\Application\Mappers\ConnectMapper;
use App\Src\Connect\Domain\Entities\ChatRecord;
use App\Src\Connect\Domain\Repositories\ChatRecordRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentChatRecordRepository implements ChatRecordRepositoryInterface
{
    public function save(ChatRecord $record): ChatRecord
    {
        $data = ConnectMapper::chatRecordToEloquent($record);

        if ($record->id() !== null) {
            $eloquent = EloquentChatRecord::findOrFail($record->id());
            $eloquent->update($data);
        } else {
            $eloquent = EloquentChatRecord::create($data);
        }

        return ConnectMapper::chatRecordToDomain($eloquent->fresh());
    }

    public function findById(int $id): ?ChatRecord
    {
        $eloquent = EloquentChatRecord::find($id);
        return $eloquent ? ConnectMapper::chatRecordToDomain($eloquent) : null;
    }

    public function findByConversationId(string $conversationId): ?ChatRecord
    {
        $eloquent = EloquentChatRecord::where('conversation_id', $conversationId)->first();
        return $eloquent ? ConnectMapper::chatRecordToDomain($eloquent) : null;
    }

    public function findByEmployee(int $employeeId, string $dateFrom, string $dateTo): array
    {
        return EloquentChatRecord::where('employee_id', $employeeId)
            ->whereBetween('start_time', [$dateFrom, $dateTo])
            ->orderBy('start_time')
            ->get()
            ->map(fn (EloquentChatRecord $e) => ConnectMapper::chatRecordToDomain($e))
            ->toArray();
    }

    public function bulkInsert(array $records): void
    {
        if (empty($records)) {
            return;
        }

        $data = [];
        foreach ($records as $record) {
            $data[] = ConnectMapper::chatRecordToEloquent($record);
        }

        DB::table('chat_records')->insert($data);
    }
}
