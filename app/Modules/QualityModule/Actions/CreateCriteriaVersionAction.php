<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\Events\CriteriaVersionCreated;
use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class CreateCriteriaVersionAction
{
    /**
     * @param  array{criterio_text: string, puntaje: int, descripcion?: string|null}  $newData
     */
    public function execute(string $criteriaId, array $newData): CriteriaVersion
    {
        return DB::transaction(function () use ($criteriaId, $newData) {
            $criteria = Criteria::findOrFail($criteriaId);

            $currentVersion = $criteria->versions()
                ->whereNull('valid_to')
                ->latest('version')
                ->first();

            $nextVersion = $currentVersion ? $currentVersion->version + 1 : 1;

            if ($currentVersion) {
                $currentVersion->update([
                    'valid_to' => Carbon::now()->subDay()->toDateString(),
                ]);
            }

            $version = CriteriaVersion::create([
                'criteria_id' => $criteriaId,
                'version' => $nextVersion,
                'criterio_text' => $newData['criterio_text'],
                'puntaje' => $newData['puntaje'],
                'descripcion' => $newData['descripcion'] ?? null,
                'valid_from' => Carbon::now()->toDateString(),
                'valid_to' => null,
            ]);

            CriteriaVersionCreated::dispatch($version);

            return $version;
        });
    }
}
