# Analisis de Modulos

Desde un punto de vista de **Arquitectura de Monolito Modular (DDD)** y revisando la estructura actual, existen varias oportunidades de reestructuración para mejorar la **cohesión** y reducir el **acoplamiento** que actualmente viola algunas de nuestras "Reglas de Oro".

Aquí mi análisis y propuesta de reestructuración:

### 1. Problemas Identificados

* **Fragmentación del Dominio "Persona":** Tienes los datos del empleado dispersos en `EmployeesModule`, `OrganizationModule` y `LocationModule`. Esto obliga a realizar joins cruzados entre módulos o importar modelos (ej: `Position`, `Team`) en casi todas partes, rompiendo el aislamiento.
* **Acoplamiento Plan vs. Realidad:** `WfmModule` (el "Deber Ser") y `ConnectModule` (el "Hecho") están fuertemente acoplados. Para calcular la **Adherencia**, un módulo tiene que "espiar" las tablas del otro.
* **Módulos "Anémicos":** `OperationsModule`, `LocationModule` y `WorkflowsModule` tienen muy poca lógica (principalmente modelos y rutas CRUD), lo que genera sobrecarga administrativa sin beneficios de aislamiento reales.
* **Falta de Capa de Orquestación:** El dashboard de `RealtimeMonitoring` vive en `WfmModule`, pero consume datos de telemetría de `ConnectModule`. Esto no es "Scheduling", es "Operación".

### 2. Propuesta de Reestructuración (Hacia 4 Ejes Claros)

Propongo consolidar los 12 módulos actuales en **6 módulos robustos** alineados al negocio:

#### A. `PersonnelModule` (Consolidación de Identidad)

* **Fusión de:** `EmployeesModule` + `OrganizationModule` + `LocationModule`.
* **Razón:** Un empleado no existe sin su cargo, equipo y ubicación. Son una unidad de dominio.
* **Responsabilidad:** Gestión de la estructura jerárquica y el maestro de personas.

#### B. `ConnectModule` (Capa de Infraestructura/Telemetría)

* **Renombrar:** `ConnectModule` → `ConnectModule`.
* **Responsabilidad:** Exclusivamente el **ETL y la Integración con Cisco (UCCX/Finesse)**. Debe ser el "adaptador" que convierte datos crudos en eventos de dominio (`AgentStateChanged`, `CallFinished`). No debería calcular KPIs de negocio, solo persistir "Hechos".

#### C. `WfmModule` (Capa de Planeación)

* **Renombrar:** `WfmModule` → `WfmModule`.
* **Responsabilidad:** Pronóstico (Forecasting), Capacidad y Programación (Scheduling). Su único output es el "Plan" (Turnos, Pausas, Excepciones).

#### D. `OperationsModule` (Capa de Inteligencia y Negocio)

* **Acción:** Mover aquí el `RealtimeMonitoring`, `Scorecards` y `Analytics`.
* **Responsabilidad:** Cruzar el **Plan** (`WfmModule`) con los **Hechos** (`ConnectModule`) para generar **KPIs**. Es el módulo que "entiende" el negocio en tiempo real.

#### E. `Shared` (Contratos Reales)

* Implementar interfaces en `Shared/Contracts` para que `OperationsModule` pueda pedir el estado esperado a `WfmModule` sin conocer sus modelos internos.

### 3. Matriz de Cambios Sugeridos

| Módulo Actual | Destino Propuesto | Razón Arquitectónica |
| :--- | :--- | :--- |
| `OrganizationModule` | `PersonnelModule` | Cohesión: El cargo y equipo son atributos de la persona. |
| `LocationModule` | `PersonnelModule` | Simplificación: Evita acoplamiento innecesario por datos estáticos. |
| `ConnectModule` | `ConnectModule` | Desacoplamiento: Separar la captura de datos (SCD) de la lógica de negocio. |
| `WfmModule` | `WfmModule` | Claridad: WFM es un dominio más amplio que solo "horarios". |
| `Dashboards (en varios)`| `OperationsModule` | Centralización: Un solo lugar para la toma de decisiones. |

### [RIESGOS]

* **Refactorización de Namespaces:** Este cambio requiere una actualización masiva de `use` statements y namespaces en toda la aplicación.
* **Migraciones:** Habría que mover modelos a nuevos directorios, aunque las tablas de la DB pueden permanecer igual para minimizar el impacto en datos.

**¿Te gustaría que diseñemos un plan de migración atómico para empezar por la consolidación de `PersonnelModule`?**
