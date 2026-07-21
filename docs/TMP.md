**Contexto:**
Estamos trabajando en el sistema **HorariosWFM** (Monolito Modular en Laravel 13). Debes actuar como un **Software Architect y Senior Laravel Developer**. Tu objetivo es realizar un análisis exhaustivo del módulo **WorkflowsModule ** y proceder a resolver cualquier inconsistencia, error de arquitectura o funcionalidad pendiente. Antes de iniciar lee el skill: `wfm-laravel-developer` 

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


---

### Prompt: Auditoría y Refactorización de Interfaz (UI/UX) — HorariosWFM

**Contexto:**
Estamos trabajando en el sistema **HorariosWFM** (Laravel 13, Monolito Modular). Debes actuar como un **WFM UI Engineer & Frontend Specialist**. Tu objetivo es realizar un análisis exhaustivo de la capa de presentación del módulo **WorkflowsModule ** para asegurar que sea consistente, accesible, de alto rendimiento y que respete la separación de responsabilidades. Antes de iniciar, lee obligatoriamente la skill: `wfm-ui-engineer`.

**Instrucciones de Análisis de Interfaz (Profundización):**
1.  **Orquestación Livewire:** Verifica que los componentes Livewire **no** contengan lógica de negocio ni consultas directas a la base de datos. Deben delegar a `Actions` y solo gestionar el estado de la UI.
2.  **Uso de FluxUI:** Valida que se esté utilizando el sistema oficial de componentes. Si hay HTML plano o CSS personalizado donde podría usarse `<flux:button>`, `<flux:input>`, `<flux:table>`, etc., márcalo para refactorización.
3.  **Arquitectura de Formularios:** Asegúrate de que cada formulario complejo utilice un **Livewire Form Object** (`app/Modules/{Module}/Livewire/Forms/`) para centralizar reglas de validación y propiedades.
4.  **Autorización Visual:** Revisa que el estado "sin permisos" o la visibilidad de acciones se maneje mediante Policies (`@can` en Blade o flags booleanos), nunca evaluando permisos directamente en el componente Livewire (`$user->hasPermission(...)`).
5.  **Estados de la Interfaz:** Comprueba si el componente maneja correctamente los 5 estados esenciales: **Cargando** (`wire:loading`), **Vacío** (Empty states), **Error**, **Éxito** y **Sin Resultados**.
6.  **Rendimiento y UX:** Busca componentes excesivamente grandes que necesiten `lazy loading`, optimiza el número de requests de Livewire y asegura el uso de `wire:navigate` para navegación SPA.

**Instrucciones de Resolución (Acción de Frontend):**
1.  **Refactor de Vistas:** Sustituye HTML/Tailwind manual por componentes de **FluxUI**. Aplica jerarquía visual siguiendo el patrón: Indicadores (arriba) → Análisis (centro) → Detalle (tablas).
2.  **Implementación de Form Objects:** Migra la validación de los componentes Livewire a clases `Form` dedicadas para limpiar el componente principal.
3.  **Optimización de Tablas:** Si el módulo maneja datos, implementa tablas con FluxUI que soporten búsqueda, filtros, ordenamiento y paginación eficiente.
4.  **Accesibilidad y Responsive:** Corrige contrastes, añade labels descriptivos y asegura que la interfaz sea 100% funcional en dispositivos móviles.
5.  **Calidad de Código UI:** Asegura que todos los archivos `.php` incluyan `declare(strict_types=1)` y que las vistas Blade estén organizadas y comentadas según el estándar del proyecto.

**Entregable esperado:**
- **Informe de UX/UI:** Hallazgos sobre usabilidad, inconsistencias de FluxUI y problemas de rendimiento detectados.
- **Componentes y Vistas:** Código completo de los componentes Livewire, Form Objects y vistas Blade refactorizadas.
- **Acciones (si aplica):** Si el componente contenía lógica de negocio, muévela a una `Action` y entrega el código de dicha clase para mantener la pureza del frontend.
- **Validación visual:** Breve descripción de cómo se manejaron los estados de carga y errores.

---
