# PRD — Workforce Management System (HorariosWFM)

> **Product Requirements Document**
> Sistema de Gestión y Optimización del Talento Humano para el Call Center de la Caja de Seguro Social de Panamá
> Versión 1.0 — Julio 2026

---

## 1. Resumen Ejecutivo

**HorariosWFM** es un sistema integral de Workforce Management diseñado para profesionalizar la gestión de turnos, el rendimiento operativo y la calidad del servicio en el Call Center de la Caja de Seguro Social de Panamá. Reemplaza procesos manuales basados en hojas de cálculo y correos electrónicos por una plataforma digital integrada que garantiza transparencia, eficiencia y equidad para cada operador y coordinador.

El sistema cubre el ciclo completo de gestión del capital humano en un call center: desde la planificación semanal de horarios (basada en demanda), el seguimiento intra-día con datos en tiempo real del CTI (Cisco UCCX/Finesse), la reconciliación automática de asistencia y adherencia, la evaluación de calidad de llamadas, hasta la autogestión de permisos, intercambio de turnos y comunicación interna.

---

## 2. Contexto de Negocio

### 2.1 Problema Actual

- **Planificación manual:** Los horarios semanales se gestionan con hojas de cálculo, propensas a errores, sin visibilidad de conflictos de programación.
- **Seguimiento inexistente:** No hay un mecanismo automatizado para verificar si los agentes cumplen con sus horarios planificados. La adherencia y las tardanzas se detectan de forma reactiva.
- **Autogestión limitada:** Los empleados dependen de correos electrónicos y autorización manual para permisos, vacaciones e intercambios de turno.
- **Datos dispersos:** La información de llamadas (Cisco UCCX), planillas de horarios y datos del personal residen en sistemas inconexos.
- **Sin trazabilidad:** Las decisiones administrativas (aprobaciones, cambios de horario, ajustes de nómina) carecen de un registro de auditoría centralizado.

### 2.2 Oportunidad

Un sistema WFM integrado puede:
- Reducir el tiempo de planificación semanal de horas a minutos
- Mejorar la adherencia de los agentes mediante visibilidad en tiempo real
- Automatizar la detección de incidencias de asistencia (tardanzas, ausencias)
- Empoderar a los empleados con autogestión de solicitudes
- Proveer dashboards gerenciales para la toma de decisiones basada en datos

### 2.3 Alcance Organizacional

- **Institución:** Caja de Seguro Social de Panamá
- **Unidad:** Call Center (atención telefónica al asegurado)
- **Usuarios estimados:** ~500-1000 operadores, supervisores, coordinadores, analistas WFM, directores

---

## 3. User Personas

| Persona           | Rol                  | Necesidad Principal                                                       |
| ----------------- | -------------------- | ------------------------------------------------------------------------- |
| **Operador**      | Agente telefónico    | Ver mi horario, solicitar permisos/cambios, ver métricas personales       |
| **Supervisor**    | Líder de equipo      | Monitorear estado en vivo de su equipo, aprobar solicitudes, ver reportes |
| **Coordinador**   | Jefe de operaciones  | Dashboard de productividad, adherencia y calidad por campaña              |
| **Analista WFM**  | Planificador         | Crear/publicar horarios, gestionar excepciones, aprobar intercambios      |
| **Analista QA**   | Evaluador de calidad | Evaluar llamadas contra rúbricas, gestionar feedback                      |
| **RRHH**          | Gestión de personal  | Mantener organigrama, altas/bajas, importación masiva                     |
| **Director**      | Alta dirección       | Reportes ejecutivos, scorecards, tendencias históricas                    |
| **Administrador** | IT / Soporte         | Configuración del sistema, roles y permisos, auditoría                    |

---

## 4. Requerimientos Funcionales por Módulo

### 4.1 CoreModule — Identidad, Acceso y Configuración

| ID         | Requerimiento                                                     | Prioridad |
| ---------- | ----------------------------------------------------------------- | --------- |
| RF-CORE-01 | Autenticación segura con email y contraseña (Laravel Fortify)     | Crítica   |
| RF-CORE-02 | Autenticación de dos factores (TOTP)                              | Alta      |
| RF-CORE-03 | Verificación de email obligatoria                                 | Alta      |
| RF-CORE-04 | Registro de usuarios deshabilitado (solo creación administrativa) | Alta      |
| RF-CORE-05 | Roles y permisos granulares vía Spatie Permission                 | Crítica   |
| RF-CORE-06 | Super-admin bypass (rol admin puede todo)                         | Crítica   |
| RF-CORE-07 | Configuraciones globales en caliente (AppSetting key-value)       | Media     |
| RF-CORE-08 | Políticas de contraseña configurables (longitud, complejidad)     | Alta      |

### 4.2 OrganizationModule — Estructura Organizacional

| ID        | Requerimiento                                               | Prioridad |
| --------- | ----------------------------------------------------------- | --------- |
| RF-ORG-01 | Gestión jerárquica de Direcciones → Departamentos → Puestos | Crítica   |
| RF-ORG-02 | Integridad referencial: no eliminar si tiene hijos activos  | Alta      |
| RF-ORG-03 | Soft deletes en toda la jerarquía                           | Alta      |

### 4.3 GeoModule — Catálogo Geográfico

| ID        | Requerimiento                                             | Prioridad |
| --------- | --------------------------------------------------------- | --------- |
| RF-GEO-01 | Mantener provincias, distritos y corregimientos de Panamá | Media     |
| RF-GEO-02 | Sembrado inicial vía CSV con datos oficiales              | Media     |

### 4.4 PersonnelModule — Gestión de Empleados

| ID         | Requerimiento                                                        | Prioridad |
| ---------- | -------------------------------------------------------------------- | --------- |
| RF-PERS-01 | Ficha completa del empleado (datos personales, contacto, documentos) | Crítica   |
| RF-PERS-02 | Vinculación a usuario de sistema (User)                              | Crítica   |
| RF-PERS-03 | Asignación a equipo y puesto                                         | Crítica   |
| RF-PERS-04 | Importación masiva vía Excel con procesamiento por lotes             | Alta      |
| RF-PERS-05 | Exportación de empleados a Excel                                     | Alta      |
| RF-PERS-06 | Historial de estados de contratación (altas, bajas, motivos)         | Alta      |
| RF-PERS-07 | Registro de dependientes, enfermedades y discapacidades              | Media     |
| RF-PERS-08 | Sincronización de equipos con Cisco Finesse                          | Media     |
| RF-PERS-09 | Soft deletes obligatorio para no romper históricos                   | Alta      |

### 4.5 WfmModule — Planificación y Gestión de Horarios

| ID        | Requerimiento                                                              | Prioridad |
| --------- | -------------------------------------------------------------------------- | --------- |
| RF-WFM-01 | Creación de horarios semanales por equipo                                  | Crítica   |
| RF-WFM-02 | Importación de planillas desde Excel (Erlang-C)                            | Crítica   |
| RF-WFM-03 | Asignación masiva de turnos a empleados por equipo                         | Crítica   |
| RF-WFM-04 | Publicación de horarios con notificación a agentes                         | Crítica   |
| RF-WFM-05 | Validación de colisiones (sin traslapes)                                   | Alta      |
| RF-WFM-06 | Excepciones de horario (tardanzas justificadas, permisos pagados)          | Alta      |
| RF-WFM-07 | Actividades intra-día (breaks, almuerzos, capacitaciones)                  | Alta      |
| RF-WFM-08 | Solicitud y aprobación de intercambio de turnos                            | Alta      |
| RF-WFM-09 | Solicitud y aprobación de permisos/vacaciones                              | Alta      |
| RF-WFM-10 | API de estado esperado del agente para adherencia                          | Alta      |
| RF-WFM-11 | Catálogos configurables (tipos de actividad, motivos de ausencia, estados) | Media     |

### 4.6 ConnectModule — Integración Cisco CTI

| ID        | Requerimiento                                                      | Prioridad |
| --------- | ------------------------------------------------------------------ | --------- |
| RF-CON-01 | Sincronización ETL de registros de llamadas desde CUIC             | Crítica   |
| RF-CON-02 | Sincronización en tiempo real de estados de agente (Cisco Finesse) | Crítica   |
| RF-CON-03 | Sincronización de estados de cola (CSQ) en tiempo real             | Alta      |
| RF-CON-04 | Mapeo de estados nativos Cisco a estados estándar del sistema      | Alta      |
| RF-CON-05 | Tipificación manual/automática de llamadas (case subtypes)         | Alta      |
| RF-CON-06 | Administración de colas y canales (voz, chat)                      | Media     |
| RF-CON-07 | Resiliencia ante caídas de Cisco (circuit breaker, reintentos)     | Crítica   |
| RF-CON-08 | Sincronización programada cada 5s durante horario laboral          | Crítica   |

### 4.7 OperationsModule — KPIs, Adherencia y Dashboards

| ID       | Requerimiento                                                   | Prioridad |
| -------- | --------------------------------------------------------------- | --------- |
| RF-OP-01 | Reconciliación automática de asistencia (planificado vs real)   | Crítica   |
| RF-OP-02 | Cálculo de adherencia en tiempo real                            | Crítica   |
| RF-OP-03 | Dashboard de monitoreo en vivo con widgets                      | Crítica   |
| RF-OP-04 | Scorecard de desempeño por agente (TMO, productividad, calidad) | Alta      |
| RF-OP-05 | Reportes exportables a Excel                                    | Alta      |
| RF-OP-06 | Línea de tiempo del agente (Gantt planificado vs real)          | Alta      |
| RF-OP-07 | Cálculo de productividad avanzada (shrinkage, ocupación)        | Alta      |
| RF-OP-08 | Gestión de incidencias de asistencia (tardanzas, ausencias)     | Alta      |

### 4.8 CommunicationsModule — Comunicación Interna

| ID        | Requerimiento                                                    | Prioridad |
| --------- | ---------------------------------------------------------------- | --------- |
| RF-COM-01 | Publicación de noticias con programación futura                  | Alta      |
| RF-COM-02 | Encuestas con opciones configurables y fecha de cierre           | Alta      |
| RF-COM-03 | Reconocimientos públicos entre pares (shoutouts)                 | Media     |
| RF-COM-04 | Comentarios y reacciones (like, aplauso) con soporte polimórfico | Media     |
| RF-COM-05 | Menciones a usuarios con @notación                               | Media     |
| RF-COM-06 | Moderación de contenido (ocultar sin eliminar)                   | Media     |
| RF-COM-07 | Notificaciones in-app y por email de actividad social            | Alta      |
| RF-COM-08 | Archivado automático de contenido expirado                       | Baja      |

### 4.9 QualityModule — Evaluación de Calidad

| ID       | Requerimiento                                            | Prioridad |
| -------- | -------------------------------------------------------- | --------- |
| RF-QA-01 | Rúbricas de evaluación con criterios y pesos ponderados  | Alta      |
| RF-QA-02 | Evaluación de llamada con reproductor de audio integrado | Alta      |
| RF-QA-03 | Cálculo automático de puntaje final                      | Alta      |
| RF-QA-04 | Errores fatales que anulan toda la calificación          | Alta      |
| RF-QA-05 | Proceso de apelación de calificación (disputas)          | Media     |
| RF-QA-06 | Muestreo aleatorio de llamadas para evaluación           | Media     |
| RF-QA-07 | Feedback y retroalimentación al agente evaluado          | Alta      |

### 4.10 AuditModule — Trazabilidad

| ID        | Requerimiento                                                                  | Prioridad |
| --------- | ------------------------------------------------------------------------------ | --------- |
| RF-AUD-01 | Registro automático de cambios (created/updated/deleted) en entidades críticas | Crítica   |
| RF-AUD-02 | Captura de estado before/after en JSON                                         | Alta      |
| RF-AUD-03 | Filtrado avanzado por entidad, acción, fechas y usuario                        | Alta      |
| RF-AUD-04 | Exportación de logs de auditoría                                               | Alta      |
| RF-AUD-05 | Inmutabilidad: sin endpoints de update/delete sobre audit_logs                 | Crítica   |

### 4.11 WorkflowsModule — Motor de Aprobaciones

| ID       | Requerimiento                                               | Prioridad |
| -------- | ----------------------------------------------------------- | --------- |
| RF-WF-01 | Flujo multinivel de aprobación (Supervisor → WFM → RRHH)    | Alta      |
| RF-WF-02 | Máquina de estados estricta (Pending → Approved → Rejected) | Alta      |
| RF-WF-03 | Historial inmutable de cada decisión                        | Alta      |
| RF-WF-04 | Delegación temporal de aprobadores                          | Media     |

### 4.12 HelpdeskModule — Mesa de Ayuda Interna

| ID       | Requerimiento                                   | Prioridad |
| -------- | ----------------------------------------------- | --------- |
| RF-HD-01 | Tickets de soporte con categorías configurables | Media     |
| RF-HD-02 | Asignación y cambio de estado de tickets        | Media     |
| RF-HD-03 | Comentarios en tickets                          | Media     |

### 4.13 DocumentationModule — Wiki Interna

| ID        | Requerimiento                               | Prioridad |
| --------- | ------------------------------------------- | --------- |
| RF-DOC-01 | Artículos de documentación internos         | Baja      |
| RF-DOC-02 | Vista pública y administración de artículos | Baja      |

### 4.14 FilesystemModule — Gestión de Archivos

| ID       | Requerimiento                                 | Prioridad |
| -------- | --------------------------------------------- | --------- |
| RF-FS-01 | Subida y organización de archivos en carpetas | Media     |
| RF-FS-02 | Cuotas de almacenamiento por usuario          | Baja      |
| RF-FS-03 | Compartir archivos entre usuarios             | Baja      |

### 4.15 KnowledgeModule — Base de Conocimiento

| ID       | Requerimiento                                    | Prioridad |
| -------- | ------------------------------------------------ | --------- |
| RF-KB-01 | Artículos de conocimiento categorizados por cola | Media     |
| RF-KB-02 | Versionado de artículos                          | Baja      |
| RF-KB-03 | Vista de operador con búsqueda                   | Media     |

---

## 5. Requerimientos No Funcionales

| ID     | Requerimiento                   | Descripción                                                                   |
| ------ | ------------------------------- | ----------------------------------------------------------------------------- |
| RNF-01 | Rendimiento — Autenticación     | Búsqueda de email < 5ms (índice único B-Tree)                                 |
| RNF-02 | Rendimiento — Dashboard         | Carga inicial < 2s, widgets en lazy loading                                   |
| RNF-03 | Rendimiento — ETL Cisco         | Upserts por lotes de 1000 registros, sin bucles individuales                  |
| RNF-04 | Rendimiento — Caché             | Caché de permisos en Redis, no consultar DB por request                       |
| RNF-05 | Rendimiento — Prevención N+1    | `Model::preventLazyLoading()` activo globalmente                              |
| RNF-06 | Seguridad — Protección DB       | `DB::prohibitDestructiveCommands()` en producción                             |
| RNF-07 | Seguridad — XSS                 | Sanitización de todo contenido generado por usuarios (HTMLPurifier)           |
| RNF-08 | Seguridad — Contraseñas         | Mínimo 12 caracteres, mixtas, no comprometidas en producción                  |
| RNF-09 | Seguridad — Rate Limiting       | Límite de tasa en reacciones, comentarios, login                              |
| RNF-10 | Disponibilidad — Cisco          | Circuit breaker en integraciones, reintentos, sin bloquear sistema si CTI cae |
| RNF-11 | Disponibilidad — Notificaciones | Todas las notificaciones encoladas (ShouldQueue)                              |
| RNF-12 | Consistencia — Transacciones    | `DB::transaction()` en toda operación de escritura multi-entidad              |
| RNF-13 | Consistencia — Timestamps       | Uso de CarbonImmutable, fechas con timezone                                   |
| RNF-14 | Consistencia — Adherencia       | No fallar si datos CTI están incompletos; marcar como pendiente               |
| RNF-15 | Mantenibilidad — strict_types   | `declare(strict_types=1)` requerido en todo archivo PHP                       |
| RNF-16 | Mantenibilidad — Modular        | Sin dependencias directas entre módulos (solo Events/DTOs/Contracts)          |
| RNF-17 | Mantenibilidad — Actions        | Una acción = un caso de uso, método `execute()`, sin lógica en controllers    |
| RNF-18 | Privacidad — Datos sensibles    | Enfermedades, discapacidades y datos salariales cifrados en DB                |
| RNF-19 | Privacidad — RBAC               | Acceso a datos restringido por jerarquía de rol (`hierarchy_level`)           |
| RNF-20 | Trazabilidad — Auditoría        | Tabla `audit_logs` append-only, sin update/delete                             |

---

## 6. Arquitectura del Sistema

### 6.1 Stack Tecnológico

| Componente    | Tecnología                                |
| ------------- | ----------------------------------------- |
| Backend       | PHP 8.3+, Laravel 13                      |
| Frontend      | Livewire 4, Flux UI 2, TailwindCSS 4      |
| Base de datos | PostgreSQL 16                             |
| Cache         | Redis (vía predis/predis)                 |
| Colas         | Redis (Laravel Horizon)                   |
| WebSockets    | Laravel Reverb + Laravel Echo             |
| Auth          | Laravel Fortify (2FA, email verification) |
| Testing       | Pest 4 + PHPUnit (SQLite in-memory)       |
| Assets        | Vite 8                                    |
| Monitoreo     | Laravel Pulse                             |

### 6.2 Patrón Arquitectónico

**Monolito Modular** con 15 módulos en `app/Modules/` cargados en orden de dependencias. La comunicación entre módulos es exclusivamente vía eventos de dominio (Shared Events), DTOs y contratos (Shared Contracts). No se permiten dependencias directas a modelos de otros módulos.

Cada módulo sigue la estructura canónica:
```
Actions/    → Casos de uso (único método execute(), DB::transaction())
DTOs/       → Objetos de transferencia inmutables (readonly class)
Models/     → Eloquent models (heredan de BaseModel con ULID)
Policies/   → Autorización por recurso (Spatie Permission)
Livewire/   → Componentes UI (sin lógica de negocio, solo orquestación)
  Forms/    → Livewire Form Objects (validación de UI)
Observers/  → Efectos secundarios del modelo
Events/     → Eventos de dominio
Listeners/  → Manejadores de eventos (ShouldQueue)
Providers/  → ModuleServiceProvider
Routes/     → web.php, api.php
Resources/Views/ → Blade templates con FluxUI
```

### 6.3 Flujo de Datos

```
Cisco UCCX/Finesse/CUIC
        ↓
  [ConnectModule]  ← ETL batch + polling en tiempo real
        ↓
  [WfmModule]      ← Planificación de horarios
        ↓
  [OperationsModule] ← Reconciliación, KPIs, adherencia
        ↓
  [CommunicationsModule] ← Notificaciones a agentes
        ↑
  [PersonnelModule] ← Organigrama, empleados, equipos
        ↑
  [CoreModule]     ← Auth, RBAC, configuración global
        ↑
  [AuditModule]    ← Trazabilidad transversal
```

---

## 7. Métricas de Éxito (KPIs del Sistema)

| Métrica                         | Objetivo                        | Cómo se mide                                  |
| ------------------------------- | ------------------------------- | --------------------------------------------- |
| Tiempo de planificación semanal | < 30 minutos                    | Tiempo entre inicio de carga y publicación    |
| Precisión de adherencia         | > 90%                           | (Tiempo real / Tiempo planificado) × 100      |
| Detección de incidencias        | Automática, < 1 hora del evento | Timestamp del incidente vs inicio de tardanza |
| Tiempo de carga de dashboard    | < 2 segundos                    | Lighthouse / Chrome DevTools                  |
| Solicitudes de autogestión      | > 80% vía sistema               | (Solicitudes digitales / total) × 100         |
| Cobertura de pruebas            | > 70% líneas                    | PHPUnit coverage report                       |
| Tiempo de sincronización Cisco  | < 30 segundos por ciclo         | Logs de duración de `cisco:sync`              |

---

## 8. Fases de Implementación

### Fase 1 — Fundación (Completada)
- CoreModule: Auth, RBAC, configuración global
- OrganizationModule: Jerarquía organizacional
- GeoModule: Catálogo geográfico
- PersonnelModule: Empleados, equipos, importación

### Fase 2 — Integración CTI (Completada)
- ConnectModule: Sincronización ETL y tiempo real con Cisco
- Modelos de llamadas, estados, colas

### Fase 3 — Planificación WFM (Completada)
- WfmModule: Horarios semanales, intra-día, excepciones
- Shift swaps, leave requests
- APIs de estado esperado

### Fase 4 — Operaciones y KPIs (Completada)
- OperationsModule: Reconciliación, adherencia, dashboards
- Scorecards, alertas en tiempo real

### Fase 5 — Comunicaciones y Auditoría (Completada)
- CommunicationsModule: Noticias, encuestas, shoutouts
- AuditModule: Trazabilidad de cambios

### Fase 6 — Módulos de Soporte (En curso)
- HelpdeskModule: Tickets de soporte
- DocumentationModule: Wiki
- FilesystemModule: Archivos
- KnowledgeModule: Base de conocimiento
- WorkflowsModule: Motor de aprobaciones multinivel

### Fase 7 — Calidad (En desarrollo)
- QualityModule: Rúbricas, evaluaciones, disputas
- Integración con grabaciones de llamadas

---

## 9. Riesgos y Mitigaciones

| Riesgo                                           | Impacto                                         | Probabilidad | Mitigación                                                        |
| ------------------------------------------------ | ----------------------------------------------- | ------------ | ----------------------------------------------------------------- |
| Caída de infraestructura Cisco                   | Alto — Sin datos CTI en tiempo real             | Media        | Circuit breaker, datos parciales, cola de reintentos              |
| Volumen masivo de registros telefónicos          | Alto — Degradación de performance               | Alta         | Upserts por lotes, tablas UNLOGGED para tiempo real               |
| Condiciones de carrera en aprobaciones           | Medio — Doble aprobación o estado inconsistente | Media        | DB::transaction() + validación de estado al inicio de cada acción |
| Cambios en API de Cisco                          | Alto — Rotura de sync                           | Baja         | Capa de anticorrupción (ConnectModule), DTOs de mapeo             |
| Fuga de datos sensibles (enfermedades, salarios) | Crítico                                         | Media        | Cifrado en reposo, policies estrictas por rol jerárquico          |
| Lentitud en dashboards con muchos agentes        | Medio — Mala UX                                 | Alta         | Widgets lazy, caché Redis, polling optimizado                     |

---

## 10. Dependencias Externas

| Sistema       | Propósito                                  | Tipo de Integración           |
| ------------- | ------------------------------------------ | ----------------------------- |
| Cisco UCCX    | Base de datos de llamadas (CDRs)           | Lectura SQL vía ODBC          |
| Cisco Finesse | Estados de agente en tiempo real           | API REST XML                  |
| Cisco CUIC    | Reportes históricos de colas y agentes     | API REST con UUIDs de reporte |
| Webex         | Notificaciones a equipos de IT/operaciones | API REST (Markdown messages)  |
| PostgreSQL 16 | Base de datos principal                    | SQL nativo                    |
| Redis         | Caché, colas, sesiones (producción)        | Cliente predis                |

---

## 11. Fuera de Alcance (Versión 1.0)

- Módulo de nómina/payroll
- Reclutamiento y selección (applicant tracking)
- Evaluación de desempeño 360°
- Gamificación
- Chatbot interno
- Aplicación móvil nativa (la UI es responsive vía Livewire)
- Integración con Active Directory / LDAP
- Portal de autogestión para el asegurado (cliente externo)
- Machine learning para predicción de demanda (Erlang-C sigue siendo externo)

---

## 12. Glosario

| Término    | Definición                                                        |
| ---------- | ----------------------------------------------------------------- |
| WFM        | Workforce Management — Gestión de la fuerza laboral               |
| Adherencia | Porcentaje del tiempo que un agente cumple su horario planificado |
| AHT / TMO  | Average Handle Time / Tiempo Medio de Operación                   |
| Shrinkage  | Tiempo no productivo (breaks, capacitación, ausencias)            |
| CSQ        | Contact Service Queue — Cola de llamadas en Cisco                 |
| CTI        | Computer Telephony Integration                                    |
| UCCX       | Unified Contact Center Express (plataforma Cisco)                 |
| CUIC       | Cisco Unified Intelligence Center (reportes históricos)           |
| Intra-day  | Gestión de actividades durante la jornada laboral                 |
| RBAC       | Role-Based Access Control (Spatie Permission)                     |
| ULID       | Universally Unique Lexicographically Sortable Identifier          |
