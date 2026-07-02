# Especificación Técnica Detallada: PersonnelModule (Módulo de Recursos Humanos y Organigrama)

> Documento RUP Centrado en Arquitectura
> **Módulo:** PersonnelModule
> **Ruta:** `app/Modules/PersonnelModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **PersonnelModule** es el sistema de registro maestro (Master Data Management) del capital humano de la organización. Centraliza la estructura organizacional jerárquica (Direcciones, Departamentos, Equipos, Puestos) y la ficha completa del Empleado (datos personales, médicos y estado de contratación).

Su propósito es ser la **única fuente de verdad (Single Source of Truth)** para los datos del personal. Otros módulos (como WFM, Operaciones, Nómina o Helpdesk) asumen que la información de este módulo es exacta y vigente. Además, cuenta con integraciones críticas para aprovisionar automáticamente a los empleados en plataformas telefónicas externas (ej. Cisco UCCX/Finesse) mediante sincronización asíncrona.

---

## 2. Casos de Uso Detallados

Dada la gran amplitud del módulo, se destacan los flujos más críticos:

### CU-PE-01: Gestión del Organigrama Empresarial

- **Actor:** Analista de RRHH / Administrador.
- **Descripción:** Mantenimiento de la jerarquía organizativa.
- **Flujo Principal:**
  1. El analista crea una `Directorate` (Dirección General).
  2. Crea un `Department` vinculándolo a la Dirección.
  3. Crea un `Team` (Equipo de Trabajo / Campaña) vinculándolo al Departamento.
  4. Crea un `Position` (Puesto de Trabajo) que describe las funciones y nivel salarial.

### CU-PE-02: Alta e Importación Masiva de Empleados

- **Actor:** Reclutamiento / RRHH.
- **Descripción:** Ingreso de nuevo personal al sistema, individual o por lotes.
- **Flujo Principal (Lote):**
  1. RRHH sube un archivo Excel en la vista `ImportEmployees` (Livewire).
  2. El sistema despacha el `ImportEmployeesAction`, que crea un `EmployeeImportBatch` y divide el archivo en trozos.
  3. El `ProcessEmployeeImportChunkJob` procesa las filas, creando o actualizando registros en la tabla `Employee` y vinculándolos a su `Position` y `Team`.
  4. Al finalizar, notifica por correo al usuario sobre registros exitosos y fallidos.

### CU-PE-03: Sincronización de Aprovisionamiento (Cisco)

- **Actor:** Sistema (Event Listener).
- **Descripción:** Reflejar cambios del organigrama en el sistema telefónico.
- **Flujo Principal:**
  1. Al cambiar un empleado de equipo mediante `AssignEmployeeToTeamAction`.
  2. Se dispara el evento `TeamUpdated` o `EmployeeUpdated`.
  3. Un listener captura el evento y encola el `SyncEmployeeTeamsWithCiscoAction`.
  4. Esta acción realiza peticiones API REST al servidor de Cisco para actualizar los *Skills* o *Queues* del agente para que pueda recibir las llamadas correctas inmediatamente.

### CU-PE-04: Gestión de Legajo Médico y Dependientes

- **Actor:** Trabajadora Social / RRHH.
- **Descripción:** Registro de datos sensibles para seguros y subsidios.
- **Flujo Principal:**
  1. En el perfil del empleado (`ShowEmployee`), RRHH registra enfermedades crónicas (`EmployeeDisease`) o discapacidades (`EmployeeDisability`) para reportes al Ministerio de Trabajo.
  2. Se registran los hijos o cónyuges (`EmployeeDependent`) para cálculos de seguros médicos y asignaciones familiares.

---

## 3. Requerimientos Funcionales (RF)

- **RF-PE-01 (Jerarquía Estricta):** El sistema debe garantizar que la relación *Dirección -> Departamento -> Equipo* se mantenga. No se puede borrar una Dirección si tiene departamentos activos (Restricción de Integridad Referencial de Dominio).
- **RF-PE-02 (Trazabilidad de Contratación):** El modelo `EmploymentStatus` debe registrar la fecha de alta, cese y motivo de baja de un empleado. Un empleado inactivo no debe aparecer en las listas de asignación de turnos (WFM).
- **RF-PE-03 (Búsqueda Geo-referenciada):** El sistema debe mantener catálogos geográficos (`Province`, `District`, `Township`) para registrar domicilios estandarizados de los empleados, permitiendo buscar "Agentes que vivan a menos de 10km" en caso de emergencias o asignación de transporte.
- **RF-PE-04 (Soft Deletes y Auditoría):** Ningún registro maestro (Empleado, Departamento) debe eliminarse físicamente de la base de datos para no romper históricos financieros o analíticos (`OperationsModule`). Se usará `SoftDeletes` y se dispararán Observadores (`EmployeeObserver`) para auditar quién hizo la eliminación.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-PE-01 (Manejo de Grandes Volúmenes - Chunking):** La importación de Excel (`ImportEmployeesAction`) debe soportar archivos de hasta 10,000 empleados sin provocar un *TimeOut* en el servidor. Debe usar *Job Batching* o particionado de colas (Redis).
- **RNF-PE-02 (Privacidad y Cifrado de Datos Sensibles):** Datos como enfermedades (`EmployeeDisease`), grado de discapacidad o historial salarial deben estar encriptados en la base de datos (Ej. usando Casts cifrados nativos de Laravel) cumpliendo normativas de protección de datos personales.
- **RNF-PE-03 (Tolerancia a Fallos de Integración):** Las acciones que se comunican con Cisco (`SyncEmployeeDataWithCiscoAction`) deben tener políticas de reintentos (Retries) y *Circuit Breakers* en caso de que el CTI esté caído, evitando que un error de red impida el alta del empleado en la base local.

---

## 5. Modelos de Datos Detallados

La base de datos es altamente relacional:

| Atributo / Modelo | Descripción y Lógica de Negocio |
| :--- | :--- |
| **Jerarquía Organizacional** | |
| `Directorate`, `Department`, `Team` | Estructura en árbol. El `Team` es la unidad mínima donde se asignan los empleados (Ej. "Equipo Ventas Outbound Mañana"). |
| `Position` | Puesto (ej. "Analista Senior", "Agente Telefónico"). Define permisos por defecto o rangos salariales base. |
| **Entidad: `Employee`** | **Datos Personales y Laborales Base** |
| `id` / `user_id` | Identificador propio, vinculado (opcional) a la cuenta de login `User` (CoreModule). |
| `identification_number`| DNI / Pasaporte. Debe ser único en el sistema. |
| `position_id`, `team_id` | Mapeo actual en la estructura organizacional. |
| `birth_date`, `address` | Información demográfica estandarizada. |
| **Módulos Satélite (One-to-Many)**| |
| `EmploymentStatus` | Histórico de contrataciones (Fechas de alta, baja, tipo de contrato, si está en periodo de prueba). |
| `EmployeeDependent` | Carga familiar (Hijos, cónyuge) para cálculo de beneficios. |
| `EmployeeDisease` / `Disability` | Condiciones médicas relevantes para SSO (Seguridad y Salud Ocupacional). |
| **Entidad: `TeamMember`** | Tabla pivote (Team_id, Employee_id) si un empleado pudiese pertenecer a varios equipos o registrar el histórico de transferencias. |

---

## 6. Roles y Permisos (Policies)

- **Seguridad por Capas (`EmployeePolicy`):**
  - **Agentes:** Solo pueden ver y editar sus *propios* datos demográficos de contacto (Teléfono, Dirección de Emergencia).
  - **Supervisores:** Pueden ver la ficha completa de los empleados *exclusivamente* asignados a su `Team`.
  - **Analistas RRHH:** Acceso total (CRUD) sobre cualquier empleado, y permiso exclusivo para ver/editar la tabla de Sueldos y Enfermedades (`hr.sensitive.view`).
  - **Roles de Configuración:** Solo Directores o IT pueden ejecutar operaciones de borrado lógico en `Department` o `Directorate`.

---

## 7. Eventos, Listeners y Notificaciones

El módulo emite muchísimos eventos para orquestar la plataforma:

- **CRUD Events:** `EmployeeCreated`, `EmployeeUpdated`, `TeamStatusToggled`, `PositionUpdated`.
- **Efectos Secundarios Comunes:**
  - `EmployeeCreated` avisa al `CoreModule` que genere un usuario/password temporal.
  - `EmployeeCreated` avisa a IT para la preparación de equipos informáticos.
  - `TeamUpdated` (Si cambian las métricas del equipo) fuerza al `OperationsModule` a re-calcular las cuotas proyectadas del día siguiente.
- **Observers:** Todo Modelo tiene un Observer (`DepartmentObserver`, `EmployeeObserver`) encargado de loguear la auditoría antes de guardar o borrar lógicamente el registro.

---

## 8. Servicios y Acciones Detallados (Actions)

### Patrón Command-Action

El módulo usa *Actions* minuciosamente detallados para evitar lógica en controladores.

- `ProcessEmployeeImportChunkAction`: Toma un array validado de 100 empleados y ejecuta `upsert` (actualizar o crear).
- `AssignEmployeeToTeamAction`: Desvincula al empleado del equipo viejo, limpia colas en Cisco, lo vincula al nuevo equipo, y despacha notificaciones a los respectivos supervisores.
- `SyncTeamsWithCiscoAction`: Cliente HTTP Guzzle encapsulado que realiza un `PUT` a la API de Cisco Finesse para actualizar la configuración de enrutamiento basado en habilidades (*Skill-based routing*) del equipo.

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web)

Utiliza un mix de Controladores Clásicos y Componentes Livewire:

- **Vistas Listado y CRUD Básico (Livewire):**
  - `ListEmployees`, `ListTeams`, `CreateEmployee`. Usados para la gestión rápida de RRHH.
- **Vistas Complejas de Transacción:**
  - `ManageTeamAssignments`: Interfaz *Drag-and-Drop* para mover masivamente empleados entre equipos o turnos.
  - `TeamMemberTransfer`: Proceso formal de traslado de personal, que requiere justificación en texto.
- **Controladores HTTP (`EmployeeExportController`):**
  - Manejan las descargas de Excel nativas `GET /personnel/export`, las cuales son más eficientes resolviendo flujos de *streaming* que haciéndolo dentro de Livewire.

---

## 10. Dependencias con otros Módulos

El PersonnelModule es un **Módulo Raíz / Upstream** para el negocio:

- **Es consumido por `WfmModule`:** Para saber a quiénes planificar horarios.
- **Es consumido por `OperationsModule`:** Para agrupar las métricas de rendimiento por el "Team" o "Department" correcto.
- **Interactúa de salida con plataformas externas (Cisco):** Aprovisionamiento CTI.
- **Dependencia Estricta de `CoreModule`:** Inyección de Usuarios para autenticación, y roles base.

---

## 11. Estructura de Carpetas

```tree
app/Modules/PersonnelModule
├── Actions
│   ├── AssignEmployeesToTeamAction.php
│   ├── AssignEmployeeToTeamAction.php
│   ├── CreateDepartmentAction.php
│   ├── CreateDirectorateAction.php
│   ├── CreateEmployeeAction.php
│   ├── CreatePositionAction.php
│   ├── CreateTeamAction.php
│   ├── ExportEmployeesAction.php
│   ├── ImportEmployeesAction.php
│   ├── ProcessEmployeeImportChunkAction.php
│   ├── RemoveEmployeeFromTeamAction.php
│   ├── SyncEmployeeDataWithCiscoAction.php
│   ├── SyncEmployeeTeamsWithCiscoAction.php
│   ├── SyncTeamsWithCiscoAction.php
│   ├── ToggleDepartmentStatusAction.php
│   ├── ToggleDirectorateStatusAction.php
│   ├── TogglePositionStatusAction.php
│   ├── ToggleTeamStatusAction.php
│   ├── UpdateDepartmentAction.php
│   ├── UpdateDirectorateAction.php
│   ├── UpdateEmployeeAction.php
│   ├── UpdatePositionAction.php
│   └── UpdateTeamAction.php
├── DTOs
│   ├── AssignEmployeesToTeamDTO.php
│   ├── AssignEmployeeToTeamDTO.php
│   ├── CreateEmployeeDTO.php
│   ├── DepartmentDTO.php
│   ├── DirectorateDTO.php
│   ├── EmployeeDTO.php
│   ├── EmployeeExportDTO.php
│   ├── ImportEmployeesDTO.php
│   ├── PositionDTO.php
│   ├── RemoveEmployeeFromTeamDTO.php
│   ├── TeamDTO.php
│   └── UpdateEmployeeDTO.php
├── Events
│   ├── DepartmentStatusToggled.php
│   ├── DepartmentUpdated.php
│   ├── DirectorateStatusToggled.php
│   ├── DirectorateUpdated.php
│   ├── EmployeeCreated.php
│   ├── EmployeeUpdated.php
│   ├── PositionStatusToggled.php
│   ├── PositionUpdated.php
│   ├── TeamStatusToggled.php
│   └── TeamUpdated.php
├── Http
│   ├── Controllers
│   │   ├── DepartmentController.php
│   │   ├── DirectorateController.php
│   │   ├── EmployeeController.php
│   │   ├── EmployeeExportController.php
│   │   ├── LocationController.php
│   │   ├── PositionController.php
│   │   └── TeamController.php
│   └── Requests
│       ├── AssignEmployeeToTeamRequest.php
│       ├── RemoveEmployeeFromTeamRequest.php
│       ├── StoreEmployeeRequest.php
│       └── UpdateEmployeeRequest.php
├── Jobs
│   └── ProcessEmployeeImportChunkJob.php
├── Livewire
│   ├── CreateDepartment.php
│   ├── CreateDirectorate.php
│   ├── CreateEmployee.php
│   ├── CreatePosition.php
│   ├── CreateTeam.php
│   ├── EditDepartment.php
│   ├── EditDirectorate.php
│   ├── EditEmployee.php
│   ├── EditPosition.php
│   ├── EditTeam.php
│   ├── Forms
│   │   └── ImportEmployeesForm.php
│   ├── ImportEmployees.php
│   ├── ListDepartments.php
│   ├── ListDirectorates.php
│   ├── ListEmployees.php
│   ├── ListPositions.php
│   ├── ListTeams.php
│   ├── ManageTeamAssignments.php
│   ├── ManageTeamMembers.php
│   ├── ShowDepartment.php
│   ├── ShowDirectorate.php
│   ├── ShowPosition.php
│   ├── ShowTeam.php
│   ├── StaffingSummary.php
│   └── TeamMemberTransfer.php
├── Models
│   ├── Department.php
│   ├── Directorate.php
│   ├── District.php
│   ├── EmployeeDependent.php
│   ├── EmployeeDisability.php
│   ├── EmployeeDisease.php
│   ├── EmployeeImportBatch.php
│   ├── Employee.php
│   ├── EmployeePosition.php
│   ├── EmploymentStatus.php
│   ├── Position.php
│   ├── Province.php
│   ├── TeamMember.php
│   ├── Team.php
│   └── Township.php
├── Observers
│   ├── DepartmentObserver.php
│   ├── DirectorateObserver.php
│   ├── EmployeeObserver.php
│   ├── EmploymentStatusObserver.php
│   ├── PositionObserver.php
│   └── TeamObserver.php
├── Policies
│   ├── DepartmentPolicy.php
│   ├── DirectoratePolicy.php
│   ├── EmployeePolicy.php
│   ├── PositionPolicy.php
│   └── TeamPolicy.php
├── Providers
│   └── ModuleServiceProvider.php
├── Repositories
│   └── EloquentEmployeeLookupRepository.php
├── Resources
│   └── Views
│       ├── create.blade.php
│       ├── edit.blade.php
│       ├── import.blade.php
│       ├── index.blade.php
│       ├── livewire
│       │   ├── create-department.blade.php
│       │   ├── create-directorate.blade.php
│       │   ├── create-employee.blade.php
│       │   ├── create-position.blade.php
│       │   ├── create-team.blade.php
│       │   ├── edit-department.blade.php
│       │   ├── edit-directorate.blade.php
│       │   ├── edit-employee.blade.php
│       │   ├── edit-position.blade.php
│       │   ├── edit-team.blade.php
│       │   ├── import-employees.blade.php
│       │   ├── list-departments.blade.php
│       │   ├── list-directorates.blade.php
│       │   ├── list-employees.blade.php
│       │   ├── list-positions.blade.php
│       │   ├── list-teams.blade.php
│       │   ├── manage-team-members.blade.php
│       │   ├── show-department.blade.php
│       │   ├── show-directorate.blade.php
│       │   ├── show-position.blade.php
│       │   ├── show-team.blade.php
│       │   ├── staffing-summary.blade.php
│       │   └── team-member-transfer.blade.php
│       ├── location_index.blade.php
│       ├── manage-team-assignments.blade.php
│       └── show.blade.php
└── Routes
    └── web.php
```

---

*Documento técnico profundo generado bajo lineamientos de arquitectura iterativa RUP.*
