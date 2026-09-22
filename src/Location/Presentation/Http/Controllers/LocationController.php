<?php

declare(strict_types=1);

namespace Src\Location\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Src\Location\Application\Contracts\LocationCatalogInterface;
use Src\Location\Infrastructure\Persistence\Models\District;
use Src\Location\Infrastructure\Persistence\Models\Province;

class LocationController extends Controller
{
    public function __construct(
        private readonly LocationCatalogInterface $catalog,
    ) {}

    public function index(): View
    {
        $provinces = Province::with(['districts.townships'])
            ->orderBy('name')
            ->get();

        return view('location::location_index', compact('provinces'));
    }

    public function provinces(): JsonResponse
    {
        $provinces = $this->catalog->listProvinces()->map->toArray();

        return response()->json($provinces->values());
    }

    public function districts(Province $province): JsonResponse
    {
        $districts = $this->catalog->listDistricts((int) $province->getKey())->map->toArray();

        return response()->json($districts->values());
    }

    public function townships(District $district): JsonResponse
    {
        $townships = $this->catalog->listTownships((int) $district->getKey())->map->toArray();

        return response()->json($townships->values());
    }
}
