# PRD — Sistema de Evaluación de Llamadas

**Departamento:** Ingeniería y Calidad — DNASA  
**Versión del documento:** 1.0  
**Fecha:** Julio 2026  

---

## 1. Resumen Ejecutivo

Sistema web para que evaluadores del área de calidad registren y administren evaluaciones de llamadas de agentes de call center. Cada llamada se evalúa contra un conjunto de criterios que varían según la cola de atención (trámite, cancelación, farmacia, etc.). Los criterios cambian periódicamente y debe preservarse qué versión se aplicó en cada evaluación.

---

## 2. Objetivos del Negocio

1. **Estandarizar** la medición de calidad en las 11 colas de atención.
2. **Trazabilidad histórica**: saber exactamente qué criterios y puntajes se usaron en cada evaluación, aunque los criterios hayan cambiado después.
3. **Reducir errores** eliminando datos hardcodeados (personal, criterios) y migrándolos a la base de datos.
4. **Auditabilidad**: registrar quién evaluó, quién calibró, quién dio feedback, y cuándo.

---

## 3. Usuarios y Roles

| Rol | Descripción |
|---|---|
| **Evaluador** | Califica llamadas de operadores usando los formularios por cola. |
| **Coordinador** | Supervisa evaluaciones, puede calibrar (re-asignar puntaje) y agregar feedback. |
| **Supervisor** | Visualiza evaluaciones de su equipo. |
| **Operador** | Consulta sus evaluaciones y feedback. (Futuro) |
| **Administrador** | Gestiona usuarios, roles, y mantiene los criterios de evaluación. |

---

## 4. Requerimientos Funcionales

### Módulo 1 — Autenticación y Gestión de Usuarios

| ID | Descripción | Prioridad |
|---|---|---|
| RF-01 | El sistema debe autenticar usuarios contra credenciales almacenadas con hash bcrypt. | Alta |
| RF-02 | El sistema debe exigir cambio de contraseña en el primer inicio. | Alta |
| RF-03 | El administrador debe poder crear, editar, inhabilitar y asignar roles a usuarios desde una interfaz. | Alta |
| RF-04 | Un usuario puede tener múltiples roles (ej: coordinador y evaluador). | Media |
| RF-05 | Los datos del personal (nombre, login, rol) deben leerse de la BD, no de arreglos hardcodeados. | Alta |

### Módulo 2 — Mantenimiento de Colas y Criterios

| ID | Descripción | Prioridad |
|---|---|---|
| RF-06 | El administrador debe poder definir colas de atención (Citas Trámite, Farmacia, SIPE, etc.). | Alta |
| RF-07 | El administrador debe poder crear criterios de evaluación (texto, puntaje, descripción). | Alta |
| RF-08 | Al modificar un criterio existente, el sistema debe crear una nueva versión sin alterar las anteriores. | Alta |
| RF-09 | El administrador debe poder asignar criterios (con su versión vigente) a una cola, definiendo el orden de aparición. | Alta |
| RF-10 | El administrador debe poder activar/desactivar criterios dentro de una cola sin eliminar el histórico. | Alta |
| RF-11 | El sistema debe permitir que un mismo criterio sea compartido entre varias colas. | Media |
| RF-12 | El administrador debe poder mantener las banderas rojas (texto, penalización). | Alta |

### Módulo 3 — Evaluación de Llamadas

| ID | Descripción | Prioridad |
|---|---|---|
| RF-13 | El evaluador selecciona la cola, la fecha/hora de llamada, y los datos del agente evaluado. | Alta |
| RF-14 | El sistema debe presentar dinámicamente los criterios activos de la cola seleccionada. | Alta |
| RF-15 | El evaluador marca cada criterio como "Cumple" (puntaje completo) o "No cumple" (0). | Alta |
| RF-16 | El evaluador puede marcar banderas rojas y seleccionar el motivo. | Alta |
| RF-17 | El sistema calcula automáticamente el puntaje total (suma de puntajes obtenidos). | Alta |
| RF-18 | El evaluador debe agregar observaciones (máx. 2500 caracteres). | Alta |
| RF-19 | La evaluación debe congelar la versión de cada criterio al momento de guardarse. | Alta |
| RF-20 | El evaluador no puede modificar una evaluación después de enviada (solo calibración). | Media |

### Módulo 4 — Feedback

| ID | Descripción | Prioridad |
|---|---|---|
| RF-21 | El coordinador/supervisor puede agregar feedback a una evaluación existente. | Alta |
| RF-22 | El feedback incluye observaciones, fecha y hora. | Alta |
| RF-23 | Una evaluación puede tener múltiples entradas de feedback. | Media |

### Módulo 5 — Calibración

| ID | Descripción | Prioridad |
|---|---|---|
| RF-24 | El coordinador puede calibrar una evaluación: modificar el puntaje total y agregar una observación. | Alta |
| RF-25 | El sistema debe registrar el puntaje anterior, el nuevo, quién calibró y cuándo. | Alta |
| RF-26 | La calibración no altera los puntajes individuales por criterio, solo el score total. | Media |

### Módulo 6 — Consultas y Reportes

| ID | Descripción | Prioridad |
|---|---|---|
| RF-27 | El sistema debe mostrar un histórico con DataTables (búsqueda, ordenamiento, exportación a Excel/PDF). | Alta |
| RF-28 | El histórico debe filtrar por rango de fechas, cola, evaluador, operador, y tipo. | Alta |
| RF-29 | El sistema debe indicar visualmente si una evaluación tiene feedback y/o calibración. | Media |
| RF-30 | El administrador debe poder ver el historial de versiones de un criterio. | Baja |

---

## 5. Requerimientos No Funcionales

| ID | Descripción |
|---|---|
| RNF-01 | **Seguridad**: todas las contraseñas con bcrypt; consultas parametrizadas (prepared statements) para eliminar SQL injection. |
| RNF-02 | **Integridad referencial**: todas las relaciones deben tener FK a nivel de BD. |
| RNF-03 | **Portabilidad**: nombres de tablas y columnas sin guiones ni caracteres especiales. |
| RNF-04 | **Mantenibilidad**: cero datos hardcodeados en PHP; tablas normalizadas. |
| RNF-05 | **Auditabilidad**: cada modificación de criterio, calibración y feedback debe tener timestamp y usuario. |
| RNF-06 | **Rendimiento**: el histórico (30K+ registros) debe cargar en < 3s con DataTables server-side. |

---

## 6. Casos de Uso

### CU-01: Iniciar sesión

```
Actor:      Evaluador, Coordinador, Supervisor, Administrador
Precondición: Usuario existe y está activo en login_credentials.
Flujo:
  1. El usuario ingresa login y contraseña en Principal.php.
  2. El sistema verifica el hash contra login_credentials.
  3. Si es primer inicio (cambio = FALSE), redirige a cambio de contraseña.
  4. Si las credenciales son válidas, redirige al menú principal.
Postcondición: Sesión iniciada.
```

### CU-02: Evaluar llamada

```
Actor:      Evaluador
Precondición: Sesión iniciada, cola con criterios activos.
Flujo:
  1. El evaluador selecciona una cola desde el menú (ElgCri.php).
  2. El sistema carga los criterios activos vía v_queues_criteria_active.
  3. El evaluador completa: evaluador, coordinador, supervisor, operador,
     fecha/hora llamada.
  4. El evaluador marca cada criterio como Sí/No.
  5. El evaluador indica si hay bandera roja y selecciona el motivo.
  6. El evaluador escribe observaciones.
  7. El sistema calcula el puntaje total y guarda:
     - Cabecera en evaluations
     - Cada puntaje en evaluation_scores (apuntando a criteria_versions.id)
     - Banderas rojas en evaluation_red_flags
  8. Redirige al menú principal.
Postcondición: Evaluación registrada con versiones de criterios congeladas.
```

### CU-03: Agregar feedback

```
Actor:      Coordinador, Supervisor
Precondición: Evaluación existe y está activa.
Flujo:
  1. Desde el histórico, el usuario hace clic en "Feedback" de una evaluación.
  2. El sistema carga la evaluación con las observaciones originales (solo lectura).
  3. El usuario escribe las observaciones del feedback.
  4. El sistema guarda en feedback con fecha/hora y redirige.
Postcondición: Feedback registrado, visible en el histórico.
```

### CU-04: Calibrar evaluación

```
Actor:      Coordinador
Precondición: Evaluación existe y está activa.
Flujo:
  1. Desde el histórico, el coordinador selecciona "Calibrar".
  2. El sistema muestra el score actual y los puntajes por criterio.
  3. El coordinador ingresa el nuevo score y una observación.
  4. El sistema registra en calibration_log: score anterior, score nuevo,
     observación, login del coordinador, fecha y hora.
  5. El sistema actualiza evaluations.score.
Postcondición: Calibración registrada sin modificar puntajes originales.
```

### CU-05: Crear/editar criterio

```
Actor:      Administrador
Precondición: Sesión iniciada como admin.
Flujo:
  1. El administrador accede al módulo de criterios.
  2. Si es nuevo: ingresa código, texto, puntaje y descripción.
     El sistema crea criteria + criteria_versions (versión 1, valid_from = hoy).
  3. Si edita existente: el sistema NO modifica la versión actual.
     Crea una nueva criteria_versions (version + 1, valid_from = hoy,
     valid_to de la anterior = hoy).
  4. Asigna el criterio a una o más colas vía queue_criteria.
Postcondición: Nueva versión del criterio creada. Evaluaciones anteriores
               conservan su versión original.
```

### CU-06: Consultar histórico

```
Actor:      Evaluador, Coordinador, Supervisor, Administrador
Precondición: Sesión iniciada.
Flujo:
  1. El usuario accede a "Histórico" desde el menú.
  2. El sistema carga v_evaluations_list y la muestra con DataTables.
  3. El usuario puede buscar, filtrar por fechas/cola/operador, ordenar columnas.
  4. El usuario puede exportar a Excel, PDF o CSV.
  5. El usuario puede hacer clic en una fila para ver el detalle de criterios.
Postcondición: Datos visibles sin modificaciones.
```

---

## 7. Reglas de Negocio

| ID | Regla |
|---|---|
| RN-01 | Una evaluación eliminada (status = 'eliminada') no aparece en el histórico pero persiste en BD. |
| RN-02 | El score total es siempre la suma de puntajes_obtenidos de evaluation_scores, salvo que haya calibración. |
| RN-03 | No se puede eliminar una evaluación si tiene feedback o calibración asociados. |
| RN-04 | Un criterio no puede desasignarse de una cola si existen evaluaciones que lo referencian. |
| RN-05 | El rango de fecha/hora de llamada debe estar entre 06:00 y 19:00. |
| RN-06 | Un usuario bloqueado (3 intentos fallidos) no puede iniciar sesión hasta que un admin lo desbloquee. |

---

## 8. Glosario

| Término | Definición |
|---|---|
| **Cola** | Tipo de atención (Citas Médicas, Farmacia, SIPE, etc.). |
| **Criterio** | Aspecto evaluable de la llamada (ej: "El operador saludó en los primeros 30 segundos"). |
| **Versión de criterio** | Snapchat inmutable del texto y puntaje de un criterio en un momento dado. |
| **Bandera Roja** | Falta grave que penaliza fuertemente la evaluación. |
| **Calibración** | Ajuste del puntaje total por parte de un coordinador, con registro de auditoría. |
| **Feedback** | Retroalimentación del coordinador/supervisor al evaluador sobre una evaluación. |
