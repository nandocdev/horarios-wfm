<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\UccxCallDataDTO;
use App\Src\Connect\Domain\Entities\ChatRecord;
use App\Src\Connect\Domain\Repositories\ChatRecordRepositoryInterface;
use App\Src\Connect\Domain\Services\CuicDataNormalizationService;
use Illuminate\Support\Facades\Log;

final readonly class ImportUccxChatHandler
{
    public function __construct(
        private CuicDataNormalizationService $normalizer,
        private ChatRecordRepositoryInterface $repository,
    ) {}

    public function handle(UccxCallDataDTO $dto): ChatRecord
    {
        $raw = $dto->rawData;
        $raw['conversation_id'] = $dto->getCiscoCallId();
        $raw['start_time'] = $dto->getStartedAt();
        $raw['end_time'] = $dto->getEndedAt();
        $raw['talk_time'] = $dto->getTalkTime();
        $raw['author_identifier'] = $dto->getOriginatingNumber();
        $raw['destination_identifier'] = $dto->getDestinationNumber();

        $chatRecord = $this->normalizer->normalizeChatRecord($raw);
        $saved = $this->repository->save($chatRecord);

        Log::info('UCCX chat record imported.', [
            'conversation_id' => $dto->getCiscoCallId(),
            'record_id' => $saved->id(),
        ]);

        return $saved;
    }
}
