# Location — Casos de Uso

Módulo: Catálogo Geográfico Administrativo de Panamá (provincias, distritos, corregimientos). No confundir con ubicaciones físicas (Directory).

## UC-LOC-01 — Listar catálogo completo

- Actor: cualquier usuario autenticado (`auth` + `verified`)
- Precondiciones: tablas `provinces`, `districts`, `townships` seededas
- Flujo: `GET /location` → `LocationController@index` → `Province::with(districts.townships)` → `view('location::location_index')`
- Invariantes: ordenado por `name`, sin escritura
- Eventos: ninguno

## UC-LOC-02 — Cascada para alta/edición de empleado

- Actor: usuario con `employees.create` / `employees.update` (Personnel)
- Pre: `LocationCatalogInterface` bindeado
- Flujo: `CreateEmployee::getSelectOptionsProperty` / `EditEmployee::loadOptions` → `LocationCatalogInterface::listProvinces/listDistricts(provinceId)/listTownships(districtId)` → DTOs → `pluck(name,id)` para selects
- Invariante: `township_id` validado contra existencia vía contrato, no vía FK directa en memoria
- Dependencia: Personnel → Location (solo Contracts/DTOs, R2)

## UC-LOC-03 — APIs JSON cascada

- Actor: frontend Livewire (cascada provincia→distrito→corregimiento)
- Rutas: `GET /location/provinces`, `GET /location/districts/{province}`, `GET /location/townships/{district}`
- Pre: `auth`
- Flujo: `LocationController::{provinces,districts,townships}` → `LocationCatalogInterface` → `JsonResponse` con `id,name` (+ `code` en provinces si se expone)
- Invariantes: filtrado por FK padre, orden `name`

## UC-LOC-04 — Seed geografía Panamá

- Actor: seeder `PanamaGeographySeeder` (src)
- Pre: CSVs en `database/data/provincias.csv`, `distritos.csv`, `corregimientos.csv`
- Flujo: `DB::transaction` → `firstOrCreate` por tabla, `code` province validado por `ProvinceCode` VO
- Invariantes: idempotente, `unique [province_id,name]` y `[district_id,name]`
- FK diferida: `employees.township_id → townships.id` permanece física hasta `refactor/personnel` (R1 excepción documentada @cross-module-read readonly)

## Notas arquitectura

- Module: `location`, alias: `location`, routes: `Presentation/Routes/web.php`
- Domain: `ProvinceCode` VO (2 letras), `InvalidProvinceCodeException`
- Application: `LocationCatalogInterface` (única superficie importable por otros módulos) + DTOs `ProvinceData/DistrictData/TownshipData` + Actions `List*`
- Infrastructure: Models anémicos intra-Location, Repository `EloquentLocationCatalogRepository`, Migrations/Factories/Seeders
- Presentation: `LocationController` tonto (delega a contrato), Views `location::location_index`
- FK `employees.township_id` documentada como deuda tolerada hasta Personnel (ADR-0015 pendiente)
