# ADR-0014: Permisos del Menú y Matriz de Accesos

- **Estado:** Aceptado
- **Fecha:** 2026-08-27
- **Fuente:** `app/Shared/Helpers/MenuHelper.php:30` (`buildItems`, `filterByPermission:327`, `userCan:357`, `getFooterItems:372`)
- **Doc relacionado:** `docs/MENU_Y_ACCESOS.md`

## Contexto

HorariosWFM expone ~75 rutas en un sidebar colapsable. La visibilidad se decide en runtime vía `MenuHelper::getSidebarItems(Auth::user())` filtrando con Spatie `hasPermissionTo()`. Existen 4 niveles de permisos: (a) sin `permission` → todo `auth`, (b) permiso único, (c) array OR, (d) herencia padre+subitems. Sin documentación, los reclamos "no veo el menú X" se diagnosticaban como bug de UI.

Se requiere una matriz auditable que mapee cada `route`/`pattern` a funcionalidad (Job to be Done) y permiso, y definir roles de negocio sugeridos.

## Decisión

1. **Fuente única es `MenuHelper::buildItems()`** — no duplicar permisos en seeder, policy o blade. `filterByPermission` es el gatekeeper; `markActive:283` solo afecta estilo, no acceso.
2. **Semántica OR para arrays** — `userCan()` itera `(array)$permissions` y retorna en el primer `hasPermissionTo() === true`. `PermissionDoesNotExist` se silencia (`catch:364`) para permitir despliegue incremental de permisos.
3. **Herencia estricta padre→hijo** — un subitem solo es visible si el padre también pasó su filtro. Si todos los hijos se filtran, el padre se oculta (`empty submenu → null:344`). Evita secciones vacías.
4. **Footer sin filtro** — `getFooterItems()` siempre visible (Configuración + Logout). Decisión consciente: no condicionar logout a permiso.
5. **Roles sugeridos, no hardcodeados** — `MenuHelper` solo habla de permisos; la composición rol→permisos vive en `roles.view` (Spatie). Tabla de la sección 3 de `MENU_Y_ACCESOS.md` es recomendación, no código.

## Consecuencias

### Positivas

- Diagnóstico de accesos en 10s: revisar `admin/roles` que el rol tenga el permiso listado en `MENU_Y_ACCESOS.md §2`.
- Añadir un ítem nuevo implica documentar su `permission` antes de codear — evita "permiso olvidado".
- `PermissionDoesNotExist` silenciado permite crear items de menú antes de registrar el permiso en BD (despliegue sin downtime).

### Negativas / Deuda

- **Forecast sin `permission` hijo** — `Planificación:Forecast|Dotación|Capacidad|Merma|Escenarios` heredan solo `schedules.view_all` (`MenuHelper.php:78-82`). Todo planner ve forecast aunque no tenga `analytics.view`. Si se quiere segmentar, añadir `'permission' => 'analytics.view'` a cada uno.
- **Silenciar `PermissionDoesNotExist` oculta typos** — un permiso mal escrito (`schedules.view_team` vs `schedule.view_team`) pasa silencioso como "sin acceso" en vez de explotar. Mitigación: test de integridad que enumere todos los `permission` de `buildItems()` y falle si no existen en `permissions` table.
- **`markActive` usa `Str::is` + `str_starts_with` (`:298`) además de `route === currentRoute`** — puede marcar activo una ruta con `pattern` que colisiona (ej. `schedules/activity-types|schedules/absence-reasons*`). Riesgo bajo, solo visual.

## Validación

```bash
# 1. Listar permisos referenciados en MenuHelper y verificar que existan
grep -oP "'permission' => .*?]" app/Shared/Helpers/MenuHelper.php

# 2. Test de integridad (a crear): tests/Arch/MenuPermissionsTest.php
#    - Carga buildItems() vía Reflection
#    - Aserta que todo permission string existe en DB (o en config/permissions.php)

# 3. Regenerar doc tras cambiar MenuHelper
php artisan tinker --execute "echo 'Revisa docs/MENU_Y_ACCESOS.md';"
```

## Alternativas consideradas

- **Gate por Policy en cada Item** — rechazado: duplicaría lógica ya cubierta por `filterByPermission`; `MenuHelper` centraliza mejor.
- **Middleware `can:` por ruta** — complementario, no sustituto. El menú oculta; el middleware bloquea acceso directo por URL. Ambos deben alinearse.

---

_Próximo paso: implementar `tests/Arch/MenuPermissionsTest.php` para evitar typos silenciosos._
