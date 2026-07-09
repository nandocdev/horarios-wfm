<?php

declare(strict_types=1);

namespace App\Modules\GeoModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GeoModule\Models\District;
use App\Modules\GeoModule\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LocationController extends Controller
{
    /**
     * Mostrar listado de ubicaciones.
     */
    public function index(): View
    {
        $provinces = Province::with(['districts.townships'])->get();

        return view('geo::location_index', compact('provinces'));
    }

    /**
     * Obtener todas las provincias.
     */
    public function provinces(): JsonResponse
    {
        $provinces = Province::select('id', 'name')->get();

        return response()->json($provinces);
    }

    /**
     * Obtener distritos de una provincia.
     */
    public function districts(Province $province): JsonResponse
    {
        $districts = $province->districts()->select('id', 'name')->get();

        return response()->json($districts);
    }

    /**
     * Obtener corregimientos de un distrito.
     */
    public function townships(District $district): JsonResponse
    {
        $townships = $district->townships()->select('id', 'name')->get();

        return response()->json($townships);
    }
}
