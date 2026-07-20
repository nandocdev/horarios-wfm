**Contexto:**
Estamos trabajando en el sistema **HorariosWFM** (Monolito Modular en Laravel 13). Debes actuar como un **Software Architect y Senior Laravel Developer**. Tu objetivo es realizar un análisis exhaustivo del módulo **OrganizationModule** y proceder a resolver cualquier inconsistencia, error de arquitectura o funcionalidad pendiente.

**Instrucciones de Análisis (Profundización):**
1.  **Integridad Arquitectónica:** Verifica que no existan importaciones directas de modelos de otros módulos. Todo debe pasar por `app/Shared/`.
2.  **Patrón Action-First:** Asegúrate de que la lógica de negocio resida en `Actions/` con un único método `execute()`. Limpia lógica de base de datos en componentes Livewire o Controllers.
3.  **Cumplimiento del Data Model:** Valida que todos los modelos hereden de `BaseModel`, usen ULIDs y respeten las convenciones de `DATA_MODEL.md`.
4.  **Validación de UI:** Revisa que los componentes Livewire usen `Form Objects` para validación y `<flux:xxx>` para la UI.
5.  **Seguridad y Políticas:** Confirma que cada modelo tenga su `Policy` y que se use `$user->hasPermissionTo()` (Spatie).
6.  **Prevención de Riesgos:** Busca vulnerabilidades N+1, falta de transacciones `DB::transaction()` en operaciones de escritura y ausencia de logs de auditoría.
7.  **Cobertura de Tests:** Analiza la carpeta de tests del módulo. Si no existen o son insuficientes según el PRD, identifica los casos de prueba críticos.

**Instrucciones de Resolución (Acción):**
1.  **Refactorización:** Si encuentras "Fat Controllers" o lógica dispersa, genera el código de las `Actions` y `DTOs` necesarios.
2.  **Completitud:** Si el módulo tiene requerimientos en el PRD que no están en el código (ej. falta de un endpoint, un reporte o un botón de acción), impleméntalos.
3.  **Corrección de Brechas:** Resuelve específicamente las brechas mencionadas en `AGENTS.md` para este módulo (ej. Circuit Breakers en Connect, Aprobaciones en WFM, etc.).
4.  **Calidad:** Asegura que todo archivo generado incluya `declare(strict_types=1)` y siga los estándares de PSR-12/Pint.

**Entregable esperado:**
- Un informe breve de hallazgos (qué estaba mal/qué faltaba).
- El código completo de los archivos creados o modificados (Actions, Models, Livewire, etc.).
- Los nuevos tests de Pest necesarios para validar los cambios.


