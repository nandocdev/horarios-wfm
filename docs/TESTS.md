Vamos a generar los tests para `App\Modules\CommunicationsModule` usando Pest, contra Postg  reSQL 16
(RefreshDatabase, no SQLite — los CHECK constraints y rangos no se comportan igual).

CONTEXTO QUE TIENES: las Actions, DTOs, Models, Policies, Observers y FormRequests del
módulo CommunicationsModule (pegados abajo o en el repo). El DDL completo del sistema.

NO HAGAS: tests de getters/setters, tests de que Laravel funciona, tests triviales de
"el modelo se puede crear". Esos son ruido — cobertura falsa.

GENERA tests reales (código Pest ejecutable) en estas 6 categorías, SOLO las que apliquen
al módulo (si el módulo no tiene Policy, no inventes la sección):

1. ACTIONS — lógica de negocio
   - Camino feliz: 1 test por Action, mínimo necesario.
   - Invariantes de negocio específicas del módulo (ej: no permitir doble pending,
     validar solapamiento de fechas, validar team_id antes de escribir).
   - Qué pasa si la transacción falla a mitad (rollback real, no asumido —
     forzar la excepción y assertDatabaseMissing).
   - Race conditions: dos requests concurrentes mutando el mismo recurso
     (ej: dos aprobaciones simultáneas al mismo leave_request — usar lockForUpdate
     real o demostrar que falta).

2. POLICIES — autorización
   - Una tabla rol × acción × resultado esperado, cruzada contra 06_Permisos.md,
     traducida a un test parametrizado (`it()->with([...])`) por cada combinación.
     Si el código no coincide con la matriz, generá el test que falla y marcalo
     [BUG?] en un comentario arriba del test, no lo arregles.
   - Scope cruzado: usuario de team_id=A intentando actuar sobre recurso de team_id=B.
   - El caso WFM transversal (no jerárquico) — confirma que NO hereda scope de team.
   - before() de Admin/WFM no debe ser bypasseable desde rutas no protegidas.

3. CONSTRAINTS DE BASE DE DATOS — los que el código podría no estar respetando
   - Unique constraints compuestos (ej: weekly_schedule_assignments
     weekly_schedule_id+employee_id) — test que inserta duplicado y espera
     QueryException, no validación de Laravel.
   - Checks (status enums, employees_parent_not_self) — igual, directo contra la BD.
   - Comportamiento real de ON DELETE (CASCADE vs RESTRICT vs SET NULL) — testear
     contra la entidad relacionada real, no mockear.
   - Si el módulo toca rangos de fecha/hora: overnight shifts, timezone (UTC-5 storage,
     TSTZRANGE si aplica), fin de semana.

4. EDGE CASES ESPECÍFICOS DEL DOMINIO
   - Los 3-5 edge cases reales de ESTE módulo según las reglas de negocio del
     02_requisitos.md / 03_casos_uso.md (no genéricos). Ejemplo si es LeaveRequest:
     solicitud parcial que cruza medianoche, empleado sin team_id activo, excepción
     ya existente en la misma fecha.
   - Si el módulo participa en ScheduleResolverService u otro Shared\Service: testear
     la prioridad de resolución, no solo el caso aislado.

5. EVENTS / OBSERVERS
   - El evento se dispara exactamente una vez por operación (`Event::fake()` +
     `assertDispatchedTimes`, no `assertDispatched` solo).
   - Listener de Audit registra before/after correctamente — assert sobre el contenido
     real del jsonb, no solo "que se llamó".
   - Si hay un Observer de auditoría: confirmar que NO se puede hacer update/delete
     sobre audit_logs (intento real de UPDATE/DELETE, no solo ausencia de ruta HTTP).

6. CONTRATO HTTP (si el módulo expone Controller/FormRequest)
   - authorize() del FormRequest realmente delega a la Policy (no hardcodea true) —
     test que cambia el resultado de la Policy y confirma que el HTTP responde 403.
   - Falla con 422 en payload inválido, no con 500.
   - Mass assignment: confirmar que $fillable bloquea campos no esperados
     (intento de inyectar team_id o status desde el payload, assert que NO cambió).

CONVENCIONES DE CÓDIGO:
- Pest puro (`it()`/`test()`, no clases). `uses(RefreshDatabase::class)` en el Pest.php
  del módulo o vía `uses()->in()`.
- Factories de los módulos relacionados (Employee, Team, etc.) — si no existen,
  usa `Model::factory()->create([...])` asumiendo que existen, y al final del output
  lista qué factories faltan crear.
- Nombres de test descriptivos en español, estilo
  `it('rechaza aprobación si el coordinador no pertenece al mismo team_id', ...)`.
- Un archivo de test por Action/Policy/Model relevante, no todo en un solo archivo.
  Indica la ruta sugerida: `tests/Feature/Modules/CommunicationsModule/...`.
- Sin mocks de Eloquent. Contra base de datos real (RefreshDatabase). Mock solo
  servicios externos genuinos (mail, notificaciones, colas).

FORMATO DE SALIDA:
- Código Pest completo, agrupado por archivo, con la ruta del archivo como header.
- Cada bloque de test lleva comentario [BUG?] si sospechas que el código actual
  fallaría ese test — no lo arregles, solo señálalo y deja el test como está
  (debe fallar en rojo para evidenciar el bug).
- Al final: lista de factories/seeders que asumiste que existen y no viste en el
  código, lista de fixtures de datos que el suite necesita (ej: roles/permissions
  sembrados), y conteo total de tests generados con P0 (rompen producción) vs
  P1 (nice to have). Si el total P0 pasa de 15, decí que el módulo es demasiado
  grande y debería dividirse.