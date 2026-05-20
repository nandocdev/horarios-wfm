<?php

declare(strict_types=1);

namespace App\Modules\PersonnelModule\Repositories;

use App\Modules\PersonnelModule\Models\Employee;
use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Implementación Eloquent del contrato de lookup de empleados.
 *
 * Resuelve employee_id desde identificadores de Cisco CUIC sin exponer
 * el modelo Eloquent fuera de EmployeesModule.
 *
 * Estrategia de resolución (orden de precisión):
 *   1. $ciscoCache  : cisco_username exacto        → employee_id
 *   2. $loginCache  : username exacto              → employee_id
 *   3. $nameCache   : variantes de nombre en cache → employee_id
 *      a) "nombre apellido"
 *      b) "apellido nombre"   (invertido, común en CUIC)
 *   4. DB fallback  : ILIKE con CONCAT en Postgres → employee_id
 *      Cubre diferencias de espacios, tildes, y otros formatos.
 *      Resultado cacheado en $dbFallbackCache para no repetir queries.
 *
 * [RIESGOS]
 * - Cache en memoria → válido solo para el proceso actual (CLI/Job).
 * - Colisión de nombre si dos empleados tienen el mismo nombre completo.
 *   Mitigado priorizando loginId y cisco_username sobre el nombre.
 * - El fallback DB lanza una query por cada raw_name desconocido.
 *   Para backfill masivo, los resultados se cachean para evitar repetición.
 * - cisco_username / username pueden ser null → se omiten sin error.
 */
final class EloquentEmployeeLookupRepository implements EmployeeLookupRepositoryInterface
{
    /** @var array<string, int> cisco_username → employee_id */
    private array $ciscoCache = [];

    /** @var array<string, int> username → employee_id */
    private array $loginCache = [];

    /**
     * Variantes de nombre indexadas:
     *   "primer nombre apellido"  → employee_id
     *   "apellido primer nombre"  → employee_id
     *
     * @var array<string, int>
     */
    private array $nameCache = [];

    /**
     * Cache de resultados del fallback DB para raw_names ya consultados.
     * null = consultado y no encontrado (evita re-queries fallidos).
     *
     * @var array<string, int|null>
     */
    private array $dbFallbackCache = [];

    private bool $warmedUp = false;

    // -------------------------------------------------------------------------
    // API pública
    // -------------------------------------------------------------------------

    /**
     * Precarga todos los empleados activos en memoria.
     * Llamar una sola vez por proceso (ej. al inicio del Command o Job).
     */
    public function warmup(): void
    {
        $employees = Employee::where('is_active', true)
            ->get(['id', 'username', 'cisco_username', 'first_name', 'last_name']);

        $this->ciscoCache = [];
        $this->loginCache = [];
        $this->nameCache = [];
        $this->dbFallbackCache = [];

        foreach ($employees as $emp) {
            $id = (int) $emp->id;
            $firstName = (string) ($emp->first_name ?? '');
            $lastName = (string) ($emp->last_name ?? '');

            // — Login caches —
            if (! empty($emp->cisco_username)) {
                $this->ciscoCache[strtolower($emp->cisco_username)] = $id;
            }
            if (! empty($emp->username)) {
                $this->loginCache[strtolower($emp->username)] = $id;
            }

            // — Name cache (variante a: "nombre apellido") —
            $nameNormal = $this->normalize("{$firstName} {$lastName}");
            if ($nameNormal !== '') {
                $this->nameCache[$nameNormal] = $id;
            }

            // — Name cache (variante b: "apellido nombre" — orden CUIC) —
            $nameInverted = $this->normalize("{$lastName} {$firstName}");
            if ($nameInverted !== '' && $nameInverted !== $nameNormal) {
                $this->nameCache[$nameInverted] = $id;
            }
        }

        $this->warmedUp = true;
    }

    /**
     * Busca primero en ciscoCache y luego en loginCache.
     * {@inheritdoc}
     */
    public function findByLoginId(?string $loginId): ?int
    {
        if ($loginId === null || $loginId === '') {
            return null;
        }

        $key = strtolower($loginId);

        return $this->ciscoCache[$key] ?? $this->loginCache[$key] ?? null;
    }

    /**
     * Busca en nameCache (variantes en memoria).
     * {@inheritdoc}
     */
    public function findByFullName(?string $fullName): ?int
    {
        if ($fullName === null || $fullName === '') {
            return null;
        }

        return $this->nameCache[$this->normalize($fullName)] ?? null;
    }

    /**
     * Orden de precisión:
     *   1. cisco_username / username   (exacto)
     *   2. nombre completo en cache    (variantes normal/invertido)
     *   3. Fallback DB con CONCAT      (ILIKE, sin tildes)
     *
     * {@inheritdoc}
     */
    public function resolve(?string $loginId, ?string $fullName = null): ?int
    {
        $this->ensureWarmedUp();

        // 1. Intentar por Login ID exacto (cisco_username o username)
        $id = $this->findByLoginId($loginId);
        if ($id) {
            return $id;
        }

        // 2. Intentar por Full Name en caché de memoria
        $id = $this->findByFullName($fullName);
        if ($id) {
            return $id;
        }

        // 3. Si el loginId parece un nombre (contiene espacios), intentarlo en el caché de nombres
        if ($loginId && str_contains($loginId, ' ')) {
            $id = $this->findByFullName($loginId);
            if ($id) {
                return $id;
            }
        }

        // 4. Fallback a Base de Datos (Postgres CONCAT + ILIKE)
        // Intentamos primero con el fullName y luego con el loginId si parece nombre
        return $this->findByRawNameDb($fullName)
            ?? ($loginId && str_contains($loginId, ' ') ? $this->findByRawNameDb($loginId) : null);
    }

    // -------------------------------------------------------------------------
    // Fallback DB
    // -------------------------------------------------------------------------

    /**
     * Query Postgres: busca por CONCAT(first_name, ' ', last_name) o invertido.
     *
     * Usa ILIKE (case-insensitive) y normaliza espacios múltiples via REGEXP_REPLACE.
     * El resultado (hit o miss) se guarda en $dbFallbackCache para evitar repetición.
     *
     * @param  string|null  $rawName  Nombre exacto como viene de CUIC
     */
    private function findByRawNameDb(?string $rawName): ?int
    {
        if ($rawName === null || $rawName === '') {
            return null;
        }

        $normalizedRaw = $this->normalize($rawName);
        $cacheKey = "db_{$normalizedRaw}";

        if (array_key_exists($cacheKey, $this->dbFallbackCache)) {
            return $this->dbFallbackCache[$cacheKey];
        }

        /**
         * Estrategia SQL (PostgreSQL):
         * 1. Normalizamos acentos del nombre en DB usando translate().
         * 2. Verificamos si tanto el nombre como el apellido de la DB están CONTENIDOS en el nombre de CUIC.
         *    Esto resuelve casos como "Celidet Rodriguez" (DB) vs "Celidet Nohemi Rodriguez M." (CUIC).
         */
        $result = DB::selectOne("
            SELECT id
            FROM employees
            WHERE is_active = true
              AND (
                  -- Caso 1: Nombre y Apellido están contenidos en el string de CUIC
                  (
                    ? ILIKE '%' || translate(LOWER(first_name), 'áéíóúÁÉÍÓÚäëïöüÄËÏÖÜñÑ', 'aeiouAEIOUaeiouAEIOUnN') || '%'
                    AND
                    ? ILIKE '%' || translate(LOWER(last_name), 'áéíóúÁÉÍÓÚäëïöüÄËÏÖÜñÑ', 'aeiouAEIOUaeiouAEIOUnN') || '%'
                  )
                  OR
                  -- Caso 2: El string de CUIC está contenido en la concatenación (por si acaso)
                  (
                    translate(LOWER(first_name || ' ' || last_name), 'áéíóúÁÉÍÓÚäëïöüÄËÏÖÜñÑ', 'aeiouAEIOUaeiouAEIOUnN') ILIKE '%' || ? || '%'
                  )
              )
            ORDER BY LENGTH(first_name || last_name) DESC -- Preferir el match más largo/específico
            LIMIT 1
        ", [$normalizedRaw, $normalizedRaw, $normalizedRaw]);

        $id = $result ? (int) $result->id : null;
        $this->dbFallbackCache[$cacheKey] = $id;

        return $id;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Normaliza una cadena para comparación: sin tildes + lowercase + trim + colapsa espacios.
     * También elimina iniciales y puntos para mejorar el matching entre CUIC y DB.
     */
    private function normalize(string $value): string
    {
        // 1. Eliminar acentos/tildes
        $value = Str::ascii($value);

        // 2. Eliminar puntos
        $value = str_replace('.', '', $value);

        // 3. Eliminar iniciales aisladas (ej: "Luis A Martinez" -> "Luis Martinez")
        $value = (string) preg_replace('/\s+\b[A-Z]\b\s*/i', ' ', $value);

        // 4. Normalizar espacios y lowercase
        return strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    private function ensureWarmedUp(): void
    {
        if (! $this->warmedUp) {
            $this->warmup();
        }
    }
}
