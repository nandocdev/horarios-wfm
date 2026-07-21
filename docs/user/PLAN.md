# Plan de Documentación de Usuario — HorariosWFM

> **Responsable:** Product Owner (aliasing@css.gob.pa)
> **Versión:** 1.0 — Julio 2026
> **Propósito:** Definir qué documentación de usuario producir, para quién, en qué orden y con qué estándares.

---

## 1. Principios Rectores

| Principio | Aplicación |
|-----------|-----------|
| **Una guía por persona, no por módulo** | Cada persona (Operador, Supervisor, etc.) recibe una guía que cruza todos los módulos que usa. El sistema tiene 14 módulos; el operador no necesita saber que "Mi Horario" pertenece a WfmModule. |
| **La documentación entra por el menú** | Las guías se organizan alrededor de las 12 secciones del menú (`MENU_MAP.md`), no de la estructura interna de módulos. |
| **Prioridad por frecuencia de uso** | Primero lo que el usuario usa todos los días (Mi Trabajo, Dashboard), después lo semanal (Planificación), después lo ocasional (Admin). |
| **Formato único** | Cada guía es un archivo markdown en `docs/user/` con estructura estándar: objetivo, requisitos previos, pasos, FAQ. |
| **Sin mezclar con docs técnicos** | La documentación de usuario no incluye arquitectura, datos, migraciones ni configuración técnica. Eso queda en `docs/ARCHITECTURE.md`, `docs/DATA_MODEL.md`, etc. |

---

## 2. Personas y Alcance

### 2.1 Matriz Persona → Secciones del Menú

| Persona | Secciones que usa | Frecuencia | Prioridad doc |
|---------|------------------|-----------|---------------|
| **Operador** (~500-800) | Dashboard, Mi Trabajo (todo), Notificaciones | Diaria | **Crítica** |
| **Supervisor** (~40-60) | Dashboard, Mi Trabajo, Mi Equipo, Aprobar Permisos, Soporte (tickets) | Diaria | **Crítica** |
| **Coordinador** (~10-15) | Dashboard, Operaciones (todos), Mi Equipo, Calidad (ver) | Diaria | **Alta** |
| **Analista WFM** (~5-8) | Planificación (todo), Operaciones (reportes), Archivos | Semanal | **Alta** |
| **Analista QA** (~5-8) | Calidad (todo), Dashboard | Diaria | **Alta** |
| **RRHH** (~3-5) | Administración (empleados, equipos, organigrama), Archivos | Semanal | **Media** |
| **Director** (~3-5) | Dashboard, Operaciones (reportes), Comunicaciones | Semanal/Mensual | **Media** |
| **Administrador** (~2-3) | Administración (todo), Auditoría, Configuración, Documentación | Semanal | **Media** |

### 2.2 Lo que NO se documenta para usuarios

- Módulo KnowledgeModule — la base de conocimiento ES documentación, no necesita guía aparte.
- Módulo DocumentationModule — es la wiki del sistema; su contenido es el manual de usuario mismo.
- Módulo WorkflowsModule — no existe como tal (ver PLAN.md análisis técnico).
- Comandos Artisan, scripts de producción, configuración de Horizon/Pulse — son文档 técnica, no de usuario.

---

## 3. Estructura del Contenido

Cada guía sigue esta plantilla:

```markdown
# [Nombre de la Guía] — [Persona]

> **Público objetivo:** [rol/es]
> **Secciones del menú cubiertas:** [lista]
> **Tiempo estimado de lectura:** [min]

---

## 1. Objetivo
[qué problema resuelve esta guía]

## 2. Requisitos previos
[qué necesita el usuario antes de empezar: sesión iniciada, permisos, etc.]

## 3. [Procedimiento 1 — nombre]
[pasos numerados, imágenes referenciadas como screenshots/]

## 4. [Procedimiento 2 — nombre]
...

## 5. Preguntas Frecuentes
[entre 3 y 5 preguntas realistas por guía]

## 6. Solución de Problemas
[errores comunes y qué hacer]
```

---

## 4. Catálogo de Guías a Producir

Priorizadas por impacto de negocio (no por facilidad de redacción).

### Fase 1 — Operadores (Crítica, Semana 1-2)

| # | Guía | Secciones | Archivo propuesto |
|---|------|-----------|-------------------|
| 1 | **Mi Horario Semanal** — cómo ver turnos, entender colores, navegar semanas | Mi Trabajo → Mi Horario | `docs/user/operador-mi-horario.md` |
| 2 | **Mi Día en Tiempo Real** — qué significa cada estado, cómo leer la jornada actual | Mi Trabajo → Mi Día | `docs/user/operador-mi-dia.md` |
| 3 | **Mis Métricas** — cómo leer productividad, adherencia, shrinkage, AHT | Mi Trabajo → Mis Métricas | `docs/user/operador-mis-metricas.md` |
| 4 | **Solicitar un Permiso** — tipos, cuándo usar cada uno, límites, estado de la solicitud | Mi Trabajo → Solicitar Permiso | `docs/user/operador-solicitar-permiso.md` |
| 5 | **Intercambiar un Turno** — cómo encontrar compañero, fechas válidas, seguimiento | Mi Trabajo → Solicitar Cambio de Turno | `docs/user/operador-intercambio-turno.md` |
| 6 | **Notificaciones** — qué notificaciones llegan, cómo verlas, marcarlas como leídas | Mi Trabajo → Notificaciones | `docs/user/operador-notificaciones.md` |
| 7 | **Dashboard Personal** — qué KPIs ve el operador, qué significan | Dashboard | `docs/user/operador-dashboard.md` |

### Fase 2 — Supervisores (Crítica, Semana 2-3)

| # | Guía | Secciones | Archivo propuesto |
|---|------|-----------|-------------------|
| 8 | **Mi Equipo** — vista del equipo, asignaciones, excepciones, incidencias | Mi Equipo | `docs/user/supervisor-mi-equipo.md` |
| 9 | **Aprobar Permisos** — bandeja de aprobación, decisión, qué pasa después | Mi Equipo → Aprobar Permisos | `docs/user/supervisor-aprobar-permisos.md` |
| 10 | **Resumen de Solicitudes** — panel de conteos por estado | Mi Equipo → Resumen de Solicitudes | `docs/user/supervisor-resumen-solicitudes.md` |
| 11 | **Monitoreo en Tiempo Real** — grid de agentes, filtros, interpretación de estados | Operaciones → Monitoreo en Tiempo Real | `docs/user/supervisor-monitoreo.md` |
| 12 | **Reporte Diario de Equipo** — lectura del reporte, KPIs por agente | Operaciones → Reporte Diario | `docs/user/supervisor-reporte-diario.md` |

### Fase 3 — Analistas WFM (Alta, Semana 3-4)

| # | Guía | Secciones | Archivo propuesto |
|---|------|-----------|-------------------|
| 13 | **Planificación Semanal** — crear semana, asignar equipos, publicar | Planificación → Planificación Semanal | `docs/user/wfm-planificacion-semanal.md` |
| 14 | **Turnos Base** — CRUD de definiciones de turnos | Planificación → Turnos Base | `docs/user/wfm-turnos-base.md` |
| 15 | **Actividades Intradía** — definir períodos aprobados, asignar operadores | Planificación → Actividades Intradía | `docs/user/wfm-actividades-intradia.md` |
| 16 | **Excepciones de Horario** — registrar y gestionar excepciones | Planificación → Excepciones de Horario | `docs/user/wfm-excepciones.md` |
| 17 | **Catálogos WFM** — tipos de actividad, motivos de ausencia, estados de agente | Planificación (varios) | `docs/user/wfm-catalogos.md` |
| 18 | **Aprobar Intercambios (WFM)** — bandeja WFM, aplicar intercambios | Planificación → Aprobar Cambios de Turno | `docs/user/wfm-aprobar-intercambios.md` |
| 19 | **Reportes Operativos** — adherencia, cobertura, scorecards, disponibilidad intradía | Operaciones (reportes) | `docs/user/wfm-reportes.md` |
| 20 | **Configuración Operativa** — parámetros del sistema | Admin → Configuración Operativa | `docs/user/wfm-configuracion.md` |

### Fase 4 — Analistas QA (Alta, Semana 4-5)

| # | Guía | Secciones | Archivo propuesto |
|---|------|-----------|-------------------|
| 21 | **Evaluar una Llamada** — flujo completo de evaluación con rúbrica | Calidad → Nueva Evaluación | `docs/user/qa-evaluar-llamada.md` |
| 22 | **Gestionar Evaluaciones** — buscar, filtrar, ver detalle | Calidad → Evaluaciones | `docs/user/qa-gestionar-evaluaciones.md` |
| 23 | **Administrar Criterios** — crear/modificar criterios, asignar a colas, versionado | Calidad → Criterios, Criterios por Cola | `docs/user/qa-criterios.md` |
| 24 | **Administrar Colas de Calidad** — CRUD de colas | Calidad → Colas | `docs/user/qa-colas.md` |

### Fase 5 — Coordinadores (Alta, Semana 5)

| # | Guía | Secciones | Archivo propuesto |
|---|------|-----------|-------------------|
| 25 | **Dashboard de Productividad** — scorecards, analytics avanzados, top/bottom performers | Operaciones (Scorecard, Productividad) | `docs/user/coordinador-productividad.md` |
| 26 | **Desempeño por Cola y Equipo** — SLA, AHT, volumen, drill-down | Operaciones (Desempeño por Cola, Resumen por Equipo) | `docs/user/coordinador-desempeno.md` |
| 27 | **Disponibilidad Intradía** — programados vs conectados, períodos de riesgo | Operaciones → Disponibilidad Intradía | `docs/user/coordinador-disponibilidad.md` |

### Fase 6 — RRHH (Media, Semana 6)

| # | Guía | Secciones | Archivo propuesto |
|---|------|-----------|-------------------|
| 28 | **Gestionar Empleados** — crear, editar, buscar, importación masiva | Admin → Empleados | `docs/user/rrhh-empleados.md` |
| 29 | **Gestionar Equipos** — crear equipos, asignar miembros, transferencias | Admin → Equipos | `docs/user/rrhh-equipos.md` |
| 30 | **Organigrama** — direcciones, departamentos, cargos | Admin → Organigrama | `docs/user/rrhh-organigrama.md` |
| 31 | **Reportes de Personal** — staffing summary | Admin → Reportes de Personal | `docs/user/rrhh-reportes.md` |

### Fase 7 — Administradores (Media, Semana 6-7)

| # | Guía | Secciones | Archivo propuesto |
|---|------|-----------|-------------------|
| 32 | **Usuarios y Acceso** — crear usuarios, roles, permisos | Admin → Usuarios, Roles | `docs/user/admin-usuarios.md` |
| 33 | **Auditoría** — consultar logs de auditoría, exportar | Admin → Auditoría | `docs/user/admin-auditoria.md` |
| 34 | **Centro de Contacto (Catálogos)** — colas, canales, subtipos de caso | Centro de Contacto | `docs/user/admin-catalogos-contact-center.md` |
| 35 | **Archivos y Cuotas** — explorador, compartir, cuotas | Archivos | `docs/user/admin-archivos.md` |
| 36 | **Comunicaciones (Admin)** — noticias, encuestas, shoutouts, moderación | Comunicaciones (admin) | `docs/user/admin-comunicaciones.md` |
| 37 | **Wiki y Documentación** — gestionar artículos | Documentación | `docs/user/admin-wiki.md` |
| 38 | **Soporte (Bandeja)** — gestionar tickets, asignar, resolver | Soporte → Bandeja de Soporte | `docs/user/admin-soporte.md` |
| 39 | **Mantenimiento del Sistema** — modo mantenimiento, configuraciones globales | Admin → Mantenimiento | `docs/user/admin-mantenimiento.md` |

### Fase 8 — Directores (Media, Semana 7)

| # | Guía | Secciones | Archivo propuesto |
|---|------|-----------|-------------------|
| 40 | **Dashboard Ejecutivo** — lectura de KPIs globales, tendencias | Dashboard | `docs/user/director-dashboard.md` |
| 41 | **Reportes Gerenciales** — scorecards, resúmenes por equipo | Operaciones (reportes) | `docs/user/director-reportes.md` |

---

## 5. Recursos Compartidos

Además de las guías por persona, se producen estos recursos transversales:

| Recurso | Archivo | Contenido |
|---------|---------|-----------|
| **Glosario de Términos** | `docs/user/recursos/glosario.md` | Definiciones de WFM, adherencia, AHT, shrinkage, ocupación, SLA, CSQ, etc. |
| **Guía de Inicio Rápido** | `docs/user/recursos/inicio-rapido.md` | Cómo iniciar sesión, 2FA, recuperar contraseña, navegación básica (1 página). |
| **Significado de Estados** | `docs/user/recursos/estados-agente.md` | Tabla de todos los estados de agente (READY, TALKING, NOT_READY, etc.) con significado y color. |
| **FAQ General** | `docs/user/recursos/faq-general.md` | Preguntas frecuentes que no pertenecen a una guía específica. |
| **Mapa del Sistema** | `docs/user/recursos/mapa.md` | Diagrama de navegación con todas las secciones del menú y sus relaciones. |

---

## 6. Estándares de Producción

### 6.1 Formato y estilo

- Archivos markdown (.md) con encoding UTF-8.
- Títulos en español, con el formato `# Título — Persona`.
- Procedimientos numerados (1., 2., 3.) con acción en negrita al inicio: **Haz clic en** "Publicar".
- Capturas de pantalla: archivos PNG en `docs/user/screenshots/` nombrados como `{guia}-{paso}.png`.
- Sin emojis a menos que reproduzcan iconos reales del sistema.
- Cada guía auto-contenida: no asumir que el usuario leyó otra guía primero.

### 6.2 Control de calidad

| Criterio | Estándar |
|----------|----------|
| Ortografía y redacción | Revisión por PO antes de publicación. |
| Veracidad técnica | Cada procedimiento debe ejecutarse en el sistema para confirmar que los pasos son correctos. |
| Actualización | Cada vez que se agrega un campo/botón/flujo, la guía correspondiente se actualiza en el mismo PR. |
| Idioma | Español panameño (usted, no tú). |

### 6.3 Proceso de creación

1. El PO escribe el borrador contra la plantilla.
2. Un miembro del rol target revisa que los pasos tengan sentido (validación de negocio).
3. El desarrollador que implementó la funcionalidad confirma que los pasos son técnicamente correctos.
4. El PO publica y notifica a los usuarios.

---

## 7. Priorización y Cronograma

| Fase | Semana | Guías | Personas cubiertas | Acumulado |
|------|--------|-------|-------------------|-----------|
| 1 | 1-2 | 7 (guías 1-7) | Operador | 7 |
| 2 | 2-3 | 5 (guías 8-12) | Supervisor | 12 |
| 3 | 3-4 | 8 (guías 13-20) | Analista WFM | 20 |
| 4 | 4-5 | 4 (guías 21-24) | Analista QA | 24 |
| 5 | 5 | 3 (guías 25-27) | Coordinador | 27 |
| 6 | 6 | 4 (guías 28-31) | RRHH | 31 |
| 7 | 6-7 | 8 (guías 32-39) | Administrador | 39 |
| 8 | 7 | 2 (guías 40-41) | Director | 41 |
| — | 7 | 5 recursos | Todos | 46 total |

**Criterios de priorización:**

- **Fase 1-2 (Crítica):** El 90%+ de los usuarios (operadores + supervisores) no puede operar el sistema sin estas guías. Sin ellas, el Call Center se detiene.
- **Fase 3-5 (Alta):** Roles especializados que necesitan las guías para su trabajo semanal. Sin ellas, hay ineficiencia pero no parálisis.
- **Fase 6-8 (Media):** Roles administrativos y ejecutivos. Pueden operar por prueba y error o con apoyo del equipo de implementación.

---

## 8. Mantenimiento Posterior

Una vez producidas las 41 guías + 5 recursos compartidos:

| Actividad | Frecuencia | Responsable |
|-----------|-----------|-------------|
| Revisión de guías contra funcionalidad actual | Trimestral | PO |
| Actualización por nuevo RF implementado | En cada PR que toque UI | PO + Dev |
| Baja de guías por funcionalidad eliminada | Inmediato | PO |
| Traducción a inglés (futuro) | Por definir | PO |

---

## 9. Riesgos y Mitigaciones

| Riesgo | Impacto | Mitigación |
|--------|---------|------------|
| Las guías se desactualizan rápido (Fase 7 de desarrollo en curso) | Medio | No documentar QualityModule hasta que la Fase 7 esté completa. Priorizar fases completadas (1-5). |
| Los usuarios no leen la documentación | Alto | La documentación se vincula desde la UI (enlace "Ayuda" en el sidebar). |
| Las capturas de pantalla cambian con cada release | Medio | Usar capturas genéricas (sin datos reales) y reemplazar solo cuando la UI cambie estructuralmente. |
| Falta de tiempo del equipo para validar | Medio | La validación la hace el PO con un usuario real; no requiere al desarrollador en todos los casos. |

---

## 10. Aprobación

| Rol | Fecha | ¿Aprueba? |
|-----|------|-----------|
| Product Owner | Julio 2026 | — |
| Arquitecto de Software | — | — |
| Usuario representante (Operador) | — | — |
| Usuario representante (Supervisor) | — | — |
