<?php

declare(strict_types=1);

namespace App\Src\Connect\Application\Handlers;

use App\Src\Connect\Application\DTOs\SyncCuicFilterDTO;
use App\Src\Connect\Domain\Ports\CuicIntegrationInterface;
use App\Src\Connect\Domain\Repositories\AgentCallPerformanceRepositoryInterface;
use App\Src\Connect\Domain\Repositories\AgentStateTransitionRepositoryInterface;
use App\Src\Connect\Domain\Repositories\CallRecordRepositoryInterface;
use App\Src\Connect\Domain\Repositories\ChatRecordRepositoryInterface;
use App\Src\Connect\Domain\Services\CuicDataNormalizationService;
use Illuminate\Support\Facades\Log;

final readonly class SyncCuicDataHandler
{
    public function __construct(
        private CuicIntegrationInterface $cuic,
        private CuicDataNormalizationService $normalizer,
        private CallRecordRepositoryInterface $callRecordRepository,
        private ChatRecordRepositoryInterface $chatRecordRepository,
        private AgentStateTransitionRepositoryInterface $transitionRepository,
        private AgentCallPerformanceRepositoryInterface $performanceRepository,
    ) {}

    public function handle(SyncCuicFilterDTO $dto): array
    {
        $results = [
            'calls' => 0,
            'chats' => 0,
            'transitions' => 0,
            'performances' => 0,
        ];

        try {
            $rawData = $this->cuic->executeReport(
                $dto->reportType,
                $dto->dateFrom,
                $dto->dateTo,
                $dto->minutes,
            );

            foreach ($rawData as $row) {
                $type = $row['_type'] ?? $row['record_type'] ?? 'call';

                switch ($type) {
                    case 'chat':
                        $chat = $this->normalizer->normalizeChatRecord($row);
                        $this->chatRecordRepository->save($chat);
                        $results['chats']++;
                        break;

                    case 'transition':
                        $transition = $this->normalizer->normalizeStateTransition($row);
                        $this->transitionRepository->save($transition);
                        $results['transitions']++;
                        break;

                    case 'performance':
                        $performance = $this->normalizer->normalizePerformance($row);
                        $this->performanceRepository->upsert($performance);
                        $results['performances']++;
                        break;

                    default:
                        $record = $this->normalizer->normalizeInboundCall($row);
                        $this->callRecordRepository->save($record);
                        $results['calls']++;
                        break;
                }
            }

            Log::info('CUIC data synced.', [
                'report_type' => $dto->reportType,
                'date_from' => $dto->dateFrom,
                'date_to' => $dto->dateTo,
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            Log::error('CUIC data sync failed.', [
                'report_type' => $dto->reportType,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $results;
    }
}
