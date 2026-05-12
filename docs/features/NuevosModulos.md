# Arquitectura Modular Completa — Sistema WFM Call Center CSS

**Proyecto:** Sistema de Gestión de Horarios (WFM) — Call Center CSS
**Versión:** v2.0 (Propuesta Mejorada)
**Fecha:** Marzo 2026
**Stack:** PHP 8.3 · Laravel 12 · PostgreSQL 16
**Fase RUP:** Elaboración (Refinamiento)

---

## ⚠️ VALIDACIÓN DE PROPUESTA

### ✅ Alineación con documentos existentes

Tu propuesta es **parcialmente correcta**. La documentación oficial define:

| Módulo propuesto    | Estado en DDL | En Requisitos      | Incluir            |
| ------------------- | ------------- | ------------------ | ------------------ |
| Gestión de Horarios | ✅ Definido    | ✅ RF-25 a RF-54    | ✅ **CORE**         |
| Gestión de Clientes | ❌ No existe   | ❌ Fuera de alcance | ⚠️ **AMPLIAR v2.0** |
| Comunicaciones      | ❌ No existe   | ❌ Fuera de alcance | ⚠️ **AMPLIAR v2.0** |
| Reportes            | ✅ Mencionado  | ✅ RNF-01, RNF-04   | ✅ **SOPORTE v1.0** |
| Configuración       | ✅ Implícito   | ✅ RF-03 a RF-24    | ✅ **CORE**         |

### ❌ Problemas identificados

1. **"Gestión de Clientes"** no está en el alcance oficial (Visión, sección 1.2)
   - El sistema es para **Call Center CSS** (institución pública)
   - Los "clientes" son **ciudadanos** que llaman
   - Esto requiere un módulo separado: `CitizenManagement`

2. **"Comunicaciones internas"** (Noticias, Encuestas) no está en el DDL
   - Requiere nuevas tablas: `news`, `surveys`, `comments`, etc.
   - Viables como módulo adicional `Communications`

3. **"Reportes personalizados"** debe ser módulo separado de `Reports`
   - Distinguir entre: Reportes predefinidos vs. generador personalizado

---

## 📊 NUEVA ARQUITECTURA MODULAR PROPUESTA

### Estructura en capas completa

```
┌─────────────────────────────────────────────────────────┐
│                 FOUNDATION LAYER                        │
│  (Todos los módulos pueden depender de esto)            │
│  Audit | Cache | Configuration | Shared Contracts       │
└─────────────────────────────────────────────────────────┘
                            ▲
┌─────────────────────────────────────────────────────────┐
│              ORGANIZATION LAYER                         │
│  Caja de Seguro Social · Directorates · Departments     │
│  Positions · Teams · Geography                          │
└─────────────────────────────────────────────────────────┘
                            ▲
┌─────────────────────────────────────────────────────────┐
│              SECURITY LAYER                             │
│  Core (Users, Roles, Permissions) · Session             │
└─────────────────────────────────────────────────────────┘
                            ▲
┌─────────────────────────────────────────────────────────┐
│              WORKFORCE LAYER                            │
│  Employees · EmployeeWellness · Welfare Records         │
└─────────────────────────────────────────────────────────┘
                            ▲
┌─────────────────────────────────────────────────────────┐
│         WORKFORCE OPERATIONS LAYER — CORE WFM           │
│  Scheduling · Planning (Semanal) · IntradayPlanning     │
│  LeaveRequests · ShiftSwap · Attendance · Incidents     │
└─────────────────────────────────────────────────────────┘
                            ▲
┌─────────────────────────────────────────────────────────┐
│           REPORTING & ANALYTICS LAYER                   │
│  Reports (predefinidos) · CustomReports · Dashboard     │
└─────────────────────────────────────────────────────────┘
                            ▲
┌─────────────────────────────────────────────────────────┐
│        COMMUNICATION & ENGAGEMENT LAYER (v2.0)          │
│  Communications · Citizens · Surveys · Messaging        │
└─────────────────────────────────────────────────────────┘
```

---

## 🏗️ MÓDULOS DEFINIDOS POR CAPAS

> **Estado auditado del repositorio al 30/03/2026**
>
> Criterio usado para etiquetar cada módulo:
> - **[Completado]**: existe implementación funcional consistente en el repositorio.
> - **[En Proceso]**: hay base parcial (modelos, migraciones o parte de la lógica), pero faltan piezas clave.
> - **[Pendiente]**: no existe implementación verificable en el repositorio actual.

### CAPA 1: Foundation (Base del Sistema)

**Responsabilidad:** Servicios transversales, configuración global, logging.
**Dependencias:** Ninguna interna.
**Dependencia externa:** Laravel framework.

#### 1.1 `Audit`

**Estado actual:** [En Proceso]

```
Almacena y consulta logs de auditoría del sistema.

Tablas:
  - audit_logs (USER, ENTITY, ACTION, BEFORE/AFTER)

Responsabilidades:
  ✓ Registrar CRUD de entidades críticas
  ✓ Inmutabilidad de logs (soft deletes prohibidos)
  ✓ Consulta de auditoría por entidad/usuario/fecha

Models:
  - AuditLog

Eventos: (solo escucha, no emite)
  - Escucha EntityCreated, EntityUpdated, EntityDeleted

Políticas:
  - AuditLogPolicy (solo Admin + WFM)
```

#### 1.2 `Cache`

**Estado actual:** [En Proceso]

```
Gestión centralizada de caché del sistema.

Responsabilidades:
  ✓ Invalidación de caché por eventos
  ✓ Tagging y expiración de caché
  ✓ Precalcado de horarios efectivos

Listeners:
  - EmployeeUpdatedListener → limpia caché de empleado
  - SchedulePublishedListener → recalcula caché de horarios
  - LeaveApprovedListener → invalida disponibilidad

Servicios:
  - CacheManagementService (interfaz con Redis)
```

#### 1.3 `Configuration`

**Estado actual:** [Pendiente]

```
Parámetros globales del sistema.

Tablas:
  - settings (key, value, group, description)

Responsabilidades:
  ✓ Almacenar umbrales, tolerancias, configuraciones WFM
  ✓ Control de acceso a settings por rol
  ✓ Auditar cambios en configuración crítica

Models:
  - Setting

Ejemplos de settings:
  - wfm.max_daily_leaves (máximo de permisos por día)
  - wfm.min_advance_notice (días previos para solicitud)
  - wfm.vacation_threshold (días para escalamiento a Jefe)
  - notification.email_enabled
  - report.default_format
```

---

### CAPA 2: Organization (Estructura Institucional)

**Dependencias:** Foundation, Geography.

#### 2.1 `Organization`

**Estado actual:** [Completado]

```
Estructura de directorios, departamentos, cargos.

Tablas:
  - directorates
  - departments
  - positions
  - teams

Responsabilidades:
  ✓ CRUD de estructura organizacional
  ✓ Validar integridad jerárquica
  ✓ Importar estructura desde CSV

Models:
  - Directorate
  - Department
  - Position
  - Team

Políticas:
  - OrganizationPolicy (solo Admin)
```

#### 2.2 `Geography`

**Estado actual:** [Completado]

```
Provincias, distritos, corregimientos.

Tablas:
  - provinces
  - districts
  - townships

Responsabilidades:
  ✓ Catálogo de ubicaciones
  ✓ Datos maestros de República de Panamá

Models:
  - Province
  - District
  - Township

Seeder:
  - PanamaGeographySeeder (datos iniciales)
```

---

### CAPA 3: Security (Autenticación y Autorización)

**Dependencias:** Foundation, Organization.

#### 3.1 `Core`

**Estado actual:** [Completado]

```
Usuarios del sistema, roles, permisos.

Tablas:
  - users
  - roles
  - permissions
  - role_has_permissions
  - model_has_roles
  - model_has_permissions

Responsabilidades:
  ✓ Autenticación Sanctum
  ✓ RBAC jerárquico
  ✓ Rate limiting en login
  ✓ Recuperación de contraseña

Models:
  - User
  - Role
  - Permission

Controllers:
  - AuthController (login, logout, password reset)
  - UserController (CRUD de usuarios)

Políticas:
  - UserPolicy
  - RolePolicy
  - PermissionPolicy

Servicios:
  - PermissionCacheService (caché de permisos)
  - HierarchyService (validar nivel de acceso)

Eventos:
  - UserRegistered
  - PasswordChanged
  - UserDeactivated
```

---

### CAPA 4: Workforce (Gestión de Personas)

**Dependencias:** Security, Organization, Geography.

#### 4.1 `Employee`

**Estado actual:** [Completado]

```
Entidad central del empleado — Separada de User.

Tablas:
  - employees
  - employment_statuses
  - employee_positions (historial de cargos)
  - team_members (historial de pertenencia a equipos)

Responsabilidades:
  ✓ CRUD de empleados
  ✓ Importar empleados desde CSV
  ✓ Gestionar jerarquía (parent_id)
  ✓ Historial de cargos y equipos
  ✓ Estados laborales

Models:
  - Employee
  - EmploymentStatus
  - EmployeePosition
  - TeamMember

Controllers:
  - EmployeeController
  - EmployeeImportController

Servicios:
  - EmployeeImportService (validación y carga CSV)
  - HierarchyResolverService (calcular cadena de mando)

Políticas:
  - EmployeePolicy (ver solo si mismo equipo o superior)

Eventos:
  - EmployeeHired
  - EmployeePromoted
  - EmployeeTransferred
  - EmployeeTerminated

Observers:
  - EmployeeObserver (auditar cambios, crear audit_log)
```

#### 4.2 `EmployeeWellness`

**Estado actual:** [En Proceso]

```
Información médica y familiar del empleado.

Tablas:
  - employee_dependents
  - employee_diseases
  - employee_disabilities
  - disability_types
  - disease_types

Responsabilidades:
  ✓ Registro de dependientes
  ✓ Registro de enfermedades crónicas
  ✓ Registro de discapacidades
  ✓ Información de bienestar (solo lectura para empleado)

Models:
  - EmployeeDependent
  - EmployeeDisease
  - EmployeeDisability
  - DiseaseType
  - DisabilityType

Políticas:
  - EmployeeDependentPolicy
  - EmployeeDiseasePolicy
  - EmployeeDisabilityPolicy

Servicios:
  - WellnessReportService (generador de reportes médicos para RH)
```

---

### CAPA 5: Workforce Operations — CORE WFM

**Dependencias:** Employee, Organization, Core.

#### 5.1 `Schedule`

**Estado actual:** [En Proceso]

```
Catálogo de horarios base y plantillas de descanso.

Tablas:
  - schedules
  - break_templates

Responsabilidades:
  ✓ CRUD de horarios base (9:00-17:00, 8:00-16:00, etc.)
  ✓ CRUD de plantillas de descanso por equipo
  ✓ Cálculo de minutos trabajados
  ✓ Validación de horarios (start < end)

Models:
  - Schedule
  - BreakTemplate

Políticas:
  - SchedulePolicy (solo WFM + Admin)
  - BreakTemplatePolicy (WFM + Coordinator)

Servicios:
  - ScheduleValidationService (validar no-solapamientos)
```

#### 5.2 `WeeklyPlanning`

**Estado actual:** [En Proceso]

```
Planificación semanal — Núcleo del WFM.

Tablas:
  - weekly_schedules
  - weekly_schedule_assignments
  - employee_break_overrides

Responsabilidades:
  ✓ Crear semana en draft
  ✓ Asignar turnos por empleado y semana
  ✓ Publicar planificación (draft → published)
  ✓ Permitir override de descansos por coordinador
  ✓ Validar solapamientos y reglas de cobertura

Models:
  - WeeklySchedule
  - WeeklyScheduleAssignment
  - EmployeeBreakOverride

Controllers:
  - WeeklyScheduleController
  - WeeklyScheduleAssignmentController

Políticas:
  - WeeklySchedulePolicy (solo WFM)
  - EmployeeBreakOverridePolicy (Coordinator)

Servicios:
  - ScheduleResolverService (calcular horario efectivo diario)
  - SchedulePublishingService (validar antes de publicar)

Actions:
  - CreateWeeklyScheduleAction
  - PublishWeeklyScheduleAction
  - AssignEmployeeScheduleAction
  - OverrideBreakAction

Events:
  - WeeklyScheduleCreated
  - WeeklySchedulePublished
  - ScheduleAssignmentCreated

Observers:
  - WeeklyScheduleObserver (auditar cambios, recalcular caché)
```

#### 5.3 `IntradayPlanning`

**Estado actual:** [En Proceso]

```
Actividades dentro del turno (reuniones, capacitaciones, coaching).

Tablas:
  - intraday_activities
  - intraday_activity_assignments

Responsabilidades:
  ✓ Crear actividades con tipo, hora, cupo máximo
  ✓ Asignar empleados a actividades
  ✓ Validar no exceder cupo
  ✓ Validar no solapar con turno general

Models:
  - IntradayActivity
  - IntradayActivityAssignment

Políticas:
  - IntradayActivityPolicy (WFM)
  - IntradayActivityAssignmentPolicy (WFM + Coordinator)

Servicios:
  - IntradayConflictService (detectar solapamientos)

Actions:
  - CreateIntradayActivityAction
  - AssignEmployeeToActivityAction
  - RemoveEmployeeFromActivityAction

Events:
  - IntradayActivityCreated
  - EmployeeAssignedToActivity
```

#### 5.4 `LeaveRequest`

**Estado actual:** [En Proceso]

```
Permisos y licencias laborales.

Tablas:
  - leave_requests
  - leave_request_approvals
  - incident_types (reutilizado para tipos de permiso)

Responsabilidades:
  ✓ Solicitar permiso (total o parcial)
  ✓ Aprobación por Coordinador (un solo paso)
  ✓ Rechazo con justificación
  ✓ Historial de aprobaciones
  ✓ Validar no duplicados ni solapados

Models:
  - LeaveRequest
  - LeaveRequestApproval
  - IncidentType (compartido con Attendance)

Controllers:
  - LeaveRequestController

Políticas:
  - LeaveRequestPolicy

Servicios:
  - LeaveConflictService (validar solapamientos)
  - LeaveApprovalService (ejecutar flujo de aprobación)

Actions:
  - CreateLeaveRequestAction
  - ApproveLeaveRequestAction
  - RejectLeaveRequestAction

Events:
  - LeaveRequestCreated
  - LeaveRequestApproved
  - LeaveRequestRejected

Observers:
  - LeaveRequestObserver (auditar, notificar)
```

#### 5.5 `ShiftSwap`

**Estado actual:** [En Proceso]

```
Cambios de turno entre empleados.

Tablas:
  - shift_swap_requests
  - shift_swap_approvals

Responsabilidades:
  ✓ Solicitar intercambio con otro empleado
  ✓ Aceptación por empleado destino
  ✓ Aprobación por Coordinador
  ✓ Validar compatibilidad de horarios
  ✓ Validar no conflictos con excepciones

Models:
  - ShiftSwapRequest
  - ShiftSwapApproval

Políticas:
  - ShiftSwapRequestPolicy

Servicios:
  - ShiftSwapValidationService

Actions:
  - CreateShiftSwapRequestAction
  - RespondToShiftSwapAction
  - ApproveShiftSwapAction

Events:
  - ShiftSwapRequested
  - ShiftSwapAccepted
  - ShiftSwapApproved
  - ShiftSwapRejected
```

#### 5.6 `Attendance`

**Estado actual:** [En Proceso]

```
Registro de asistencia e incidencias operativas.

Tablas:
  - attendance_incidents
  - incident_types

Responsabilidades:
  ✓ Registrar tardanzas, inasistencias, salidas anticipadas
  ✓ Justificar incidencias
  ✓ Vincular a permisos aprobados
  ✓ Generar reportes de cumplimiento

Models:
  - AttendanceIncident
  - IncidentType

Controllers:
  - AttendanceIncidentController

Políticas:
  - AttendanceIncidentPolicy (Coordinator + WFM)

Servicios:
  - AttendanceReportService

Actions:
  - RecordAttendanceIncidentAction
  - JustifyIncidentAction

Events:
  - AttendanceIncidentRecorded
  - IncidentJustified

Observers:
  - AttendanceIncidentObserver (auditar, calcular impacto)
```

---

### CAPA 6: Reporting & Analytics

**Dependencias:** WeeklyPlanning, LeaveRequest, Attendance, Employee.

#### 6.1 `Reports`

**Estado actual:** [Pendiente]

```
Reportes predefinidos del sistema.

Responsabilidades:
  ✓ Reporte de asistencia por empleado/equipo/fecha
  ✓ Reporte de cumplimiento de horarios
  ✓ Reporte de cobertura operativa
  ✓ Reporte de ausentismo
  ✓ Exportación a CSV/Excel

Controllers:
  - ReportController

Servicios:
  - AttendanceReportService
  - ScheduleComplianceReportService
  - CoverageReportService
  - AbsenteeismReportService
  - ReportExportService (CSV, Excel)

Políticas:
  - ReportPolicy (Team lead +)

Vistas:
  - Listado de reportes disponibles
  - Vista previa de reporte
  - Botón de exportación
```

#### 6.2 `Dashboard`

**Estado actual:** [Pendiente]

```
Indicadores y KPIs operativos.

Responsabilidades:
  ✓ KPI de asistencia (%).
  ✓ KPI de cobertura de turnos.
  ✓ KPI de ausentismo.
  ✓ Gráficos de tendencias.
  ✓ Alertas operativas (baja cobertura).

Controllers:
  - DashboardController

Servicios:
  - DashboardMetricsService
  - KPICalculationService
  - AlertingService

Políticas:
  - DashboardPolicy (todos los roles autenticados)

Vistas:
  - Dashboard principal
  - Widgets de KPI
  - Gráficos Recharts/Chart.js
```

---

### CAPA 7: Communication & Engagement (v2.0 — Futuro)

**Dependencias:** Employee, Core, Organization.
**Status:** Fuera del alcance v1.0, incluir en v2.0.

#### 7.1 `Communications`

**Estado actual:** [Completado]

```
Noticias, anuncios, comunicados internos.

Tablas (NUEVAS):
  - news_articles
  - news_categories
  - news_comments
  - news_reactions

Responsabilidades:
  ✓ Publicar noticias internas
  ✓ Categorización de noticias
  ✓ Comentarios en noticias
  ✓ Reacciones (like, emoji)
  ✓ Notificaciones de nuevas noticias

Models:
  - NewsArticle
  - NewsCategory
  - NewsComment
  - NewsReaction

Controllers:
  - NewsController
  - NewsCommentController

Políticas:
  - NewsArticlePolicy (publicar: WFM + Director)

Servicios:
  - NewsPublishingService
  - NotificationService (integración con notificaciones)

Actions:
  - PublishNewsArticleAction
  - CommentOnArticleAction
  - ReactToArticleAction

Events:
  - ArticlePublished
  - ArticleCommented
  - ArticleReacted
```

#### 7.2 `Citizens`

**Estado actual:** [Pendiente]

```
Registro y seguimiento de ciudadanos (externos).

Tablas (NUEVAS):
  - citizens
  - citizen_calls
  - citizen_interactions

Responsabilidades:
  ✓ CRUD de ciudadanos
  ✓ Registrar llamadas entrantes
  ✓ Seguimiento de casos
  ✓ Historial de interacciones

Models:
  - Citizen
  - CitizenCall
  - CitizenInteraction

Políticas:
  - CitizenPolicy (Operators +)

Servicios:
  - CitizenHistoryService
  - CallRecordingService (integración con sistema telefónico)
```

#### 7.3 `Surveys`

**Estado actual:** [Pendiente]

```
Encuestas internas para empleados.

Tablas (NUEVAS):
  - surveys
  - survey_questions
  - survey_responses
  - survey_analytics

Responsabilidades:
  ✓ Crear y publicar encuestas
  ✓ Registrar respuestas
  ✓ Análisis de resultados
  ✓ Notificaciones de nueva encuesta

Models:
  - Survey
  - SurveyQuestion
  - SurveyResponse

Controllers:
  - SurveyController

Políticas:
  - SurveyPolicy (crear: HR/Director)

Servicios:
  - SurveyAnalyticsService

Actions:
  - CreateSurveyAction
  - SubmitSurveyResponseAction
```

---

## 🔗 MATRIZ DE DEPENDENCIAS ENTRE MÓDULOS

```
Foundation (Audit, Cache, Configuration)
    ▲
    │
    └─── Organización (Organization, Geography)
            ▲
            │
            └─── Security (Core)
                    ▲
                    │
                    └─── Workforce (Employee, EmployeeWellness)
                            ▲
                            │
                            └─── WFM Operations (Schedule, WeeklyPlanning, IntradayPlanning,
                                                 LeaveRequest, ShiftSwap, Attendance)
                                    ▲
                                    │
                                    └─── Reporting (Reports, Dashboard)
                                            ▲
                                            │
                                            └─── Communications (v2.0)
```

### ✅ Reglas de dependencias

```
✅ PERMITIDO:
  Module X → Module en capa inferior
  Module X → Foundation layer
  Module X → Events de otros módulos (desacoplado)
  Module X → Shared/Contracts

❌ PROHIBIDO:
  Module X → Module en capa superior (invertir dependencia)
  Module X → Modelos de otros módulos (usar relaciones Eloquent)
  Module X → Controllers/Actions de otros módulos
  Module X → Vistas de otros módulos
```

---

## 📋 MATRIZ DE FEATURES POR MÓDULO

| Feature                   | Módulo           | Status v1.0 | DDL                         |
| ------------------------- | ---------------- | ----------- | --------------------------- |
| Login                     | Core             | ✅ Incluir   | ✅ users                     |
| RBAC                      | Core             | ✅ Incluir   | ✅ roles, permissions        |
| Empleados CRUD            | Employee         | ✅ Incluir   | ✅ employees                 |
| Importar empleados CSV    | Employee         | ✅ Incluir   | ✅ employees                 |
| Estructura organizacional | Organization     | ✅ Incluir   | ✅ directorates, departments |
| Horarios base             | Schedule         | ✅ Incluir   | ✅ schedules                 |
| Planificación semanal     | WeeklyPlanning   | ✅ Incluir   | ✅ weekly_schedules          |
| Publicar horarios         | WeeklyPlanning   | ✅ Incluir   | ✅ weekly_schedules.status   |
| Actividades intradía      | IntradayPlanning | ✅ Incluir   | ✅ intraday_activities       |
| Solicitar permiso         | LeaveRequest     | ✅ Incluir   | ✅ leave_requests            |
| Aprobar permiso           | LeaveRequest     | ✅ Incluir   | ✅ leave_request_approvals   |
| Cambio de turno           | ShiftSwap        | ✅ Incluir   | ✅ shift_swap_requests       |
| Registro de asistencia    | Attendance       | ✅ Incluir   | ✅ attendance_incidents      |
| Reportes asistencia       | Reports          | ✅ Incluir   | —                           |
| Dashboard KPIs            | Dashboard        | ✅ Incluir   | —                           |
| Auditoría                 | Audit            | ✅ Incluir   | ✅ audit_logs                |
| Noticias internas         | Communications   | ⏳ v2.0      | —                           |
| Encuestas                 | Surveys          | ⏳ v2.0      | —                           |
| Ciudadanos                | Citizens         | ⏳ v2.0      | —                           |

---

## 🎯 PLAN DE IMPLEMENTACIÓN POR FASE

### Fase 1: Foundation + Core (Semanas 1-4)

```
✅ Audit
✅ Configuration
✅ Core (Auth, Users, Roles)
✅ Organization
✅ Geography
```

### Fase 2: Workforce (Semanas 5-8)

```
✅ Employee
✅ EmployeeWellness
```

### Fase 3: WFM Operations — CORE (Semanas 9-16)

```
✅ Schedule
✅ WeeklyPlanning
✅ IntradayPlanning
✅ LeaveRequest
✅ ShiftSwap
✅ Attendance
```

### Fase 4: Reporting (Semanas 17-20)

```
✅ Reports
✅ Dashboard
```

### Fase 5: Communication (v2.0 — Futuro)

```
⏳ Communications
⏳ Surveys
⏳ Citizens
```

---

## 🚨 RIESGOS Y MITIGACIONES

| Riesgo                                              | Severidad | Mitigación                                                                  |
| --------------------------------------------------- | --------- | --------------------------------------------------------------------------- |
| WeeklyPlanning se vuelve gigante                    | 🔴 Alta    | Usar Events para desacoplar, dividir en AcceptanceUseCase y DraftingUseCase |
| Circular dependency entre Schedule y WeeklyPlanning | 🔴 Alta    | Schedule es propietario de modelos base, WeeklyPlanning solo asigna         |
| Caché invalidado incorrectamente                    | 🟡 Media   | Usar eventos de modelo para invalidar, no lógica dispersa                   |
| Performance en cálculo de horarios efectivos        | 🟡 Media   | Precalcular en cola, usar índices en PostgreSQL (employee_id, date)         |
| Integración tardía de Reporting                     | 🟡 Media   | Implementar reportes en paralelo con WFM, no al final                       |

---

## 📝 CHECKLIST PARA CADA MÓDULO NUEVO

Cuando crees un módulo nuevo, valida:

- [ ] ¿Existe `ModuleServiceProvider.php`?
- [ ] ¿Están registradas las rutas?
- [ ] ¿Están registrados los Observers?
- [ ] ¿Están registradas las Policies?
- [ ] ¿Los eventos están definidos en `Events/`?
- [ ] ¿Los listeners están registrados?
- [ ] ¿Las DTOs tienen método `fromArray()` o `fromRequest()`?
- [ ] ¿Las Actions tienen un método público único (`execute()` o `handle()`)?
- [ ] ¿Los modelos tienen `$fillable` definido?
- [ ] ¿Tienen `SoftDeletes` si son críticos?
- [ ] ¿Las vistas usan el namespace correcto (`moduleName::view`)?
- [ ] ¿Las rutas llevan middleware de autenticación?
- [ ] ¿Las rutas de escritura llevan middleware de rol?

---

## 📞 CONTACTO Y NOTAS

**Propuesta creada:** Marzo 2026
**Validada contra:** DDL v1.1, Visión v1.0, Requisitos v1.0
**Pendiente de aprobación:** Stakeholders + Dirección WFM

### Próximas sesiones

1. **Validación de módulos** con equipo técnico
2. **Estimación de esfuerzo** por módulo
3. **Priorización** de v2.0 (Communications)
4. **Detalle técnico** de cada módulo (Actions, DTOs, Policies)

---

*Documento de arquitectura modular mejorada — WFM CSS v2.0. Alineado con documentación oficial del proyecto.*
