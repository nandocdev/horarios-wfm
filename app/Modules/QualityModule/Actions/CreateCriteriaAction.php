<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Actions;

use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class CreateCriteriaAction
{
    /**
     * @param  array{code: string, criterio_text: string, puntaje: int, descripcion?: string|null}  $data
     */
    public function execute(array $data): Criteria
    {
        return DB::transaction(function () use ($data) {
            $criteria = Criteria::create([
                'code' => $data['code'],
            ]);

            CriteriaVersion::create([
                'criteria_id' => $criteria->id,
                'version' => 1,
                'criterio_text' => $data['criterio_text'],
                'puntaje' => $data['puntaje'],
                'descripcion' => $data['descripcion'] ?? null,
                'valid_from' => Carbon::now()->toDateString(),
                'valid_to' => null,
            ]);

            return $criteria;
        });
    }
}
