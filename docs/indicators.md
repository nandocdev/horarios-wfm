# Diccionario de Indicadores y Métricas WFM

Este documento centraliza la lista de indicadores, variables y métricas operativas implementadas y gestionadas en el sistema WFM.

## 1. Gestión de Personal y Tiempos (WFM)

### Headcount y Estructura
*   **Headcount Activo**: Cantidad de personal disponible por dirección, departamento y equipo.
*   **Jerarquía Operativa**: Mapeo de Operadores -> Supervisores -> Coordinadores.

### Horarios y Planificación
*   **Turnos Base**: Definición de horarios estándar (ej. 08:00 - 17:00).
*   **Asignaciones Semanales**: Programación específica por empleado para el ciclo actual.
*   **Excepciones de Horario**: Modificaciones puntuales al plan (permisos médicos, capacitaciones, etc.).
*   **Cambios de Turno (Shift Swaps)**: Intercambios gestionados y aprobados entre pares.

### Control de Asistencia (Attendance)
*   **Ausentismo**: Días u horas programadas no laboradas.
*   **Tardanzas**: Ingresos posteriores al inicio de turno programado.
*   **Salidas Tempranas**: Desconexiones antes del fin de turno programado.
*   **Vacaciones**: Periodos de descanso legal programados.
*   **Justificaciones**: Documentación de soporte para incidentes de asistencia.

---

## 2. Monitoreo y Adherencia (Real-Time)

### Adherencia Operativa
*   **Porcentaje de Adherencia**: Coincidencia entre el estado programado (Plan) y el estado real del agente.
*   **Estados de Adherencia**:
    *   **In Adherence**: El agente está en el estado correcto según su horario.
    *   **Out Missing**: El agente debería estar laborando pero no hay actividad detectada.
    *   **Out Unscheduled**: El agente está conectado fuera de su horario programado.

### Estados del Agente (Cisco / Digital)
*   **Ready**: Tiempo disponible esperando interacciones.
*   **Not Ready**: Tiempo en estados auxiliares (Break, Almuerzo, etc.).
*   **Talk**: Tiempo en conversación activa (Llamadas/Chat).
*   **Work (ACW)**: Tiempo de trabajo administrativo posterior a la interacción.

---

## 3. Desempeño Histórico y Eficiencia

### Métricas de Productividad (Cubo WFM)
*   **Talk Time (Conversación)**: Tiempo neto de interacción con el cliente.
*   **Hold Time (Retención)**: Tiempo en que el cliente permaneció en espera durante la llamada.
*   **Work Time (After Call Work)**: Tiempo dedicado a cerrar el caso tras finalizar la interacción.
*   **Talk Time (Chat)**: Tiempo de atención en canales digitales.

### Indicadores de Eficiencia
*   **Ocupancia (Occupancy)**: `(Talk + Hold + ACW) / (Logged Time - Not Ready Time)`. Mide qué tan ocupado estuvo el agente mientras estaba disponible.
*   **Utilización (Utilization)**: `(Talk + Hold + ACW + Ready) / Logged Time`. Mide el aprovechamiento del tiempo total de conexión.
*   **Desconexiones**: Frecuencia y duración de las sesiones de conexión.

---

## 4. Gestión de Interacciones (Telefonía e IVR)

### Producción del Operador
*   **Llamadas Atendidas (Handled)**: Cantidad de llamadas completadas exitosamente por cola.
*   **Chats Atendidos**: Cantidad de interacciones digitales completadas.
*   **AHT (Average Handle Time)**: Tiempo promedio de gestión `(Talk + Hold + ACW) / Atendidas`.

### Rendimiento del Sistema y Colas
*   **Llamadas Recibidas (Offered)**: Total de llamadas que ingresaron al sistema.
*   **Flujo IVR**: Cantidad de llamadas que ingresaron y navegaron el menú de voz.
*   **Llamadas en Cola (Queued)**: Interacciones que esperaron por un agente disponible.
*   **Abandono**:
    *   **Llamadas Abandonadas**: Clientes que colgaron antes de ser atendidos.
    *   **Tiempo de Espera para Abandono**: Duración de la llamada antes de la desconexión del cliente.
*   **Atención**:
    *   **Llamadas Atendidas**: Total de llamadas que llegaron a un operador.
    *   **Tiempo de Espera para Atención (ASA)**: Tiempo promedio que el cliente esperó en cola.
*   **Nivel de Servicio (SLA)**: Porcentaje de llamadas atendidas dentro del umbral de tiempo definido (ej. 80% en 20 segundos).
*   **Rellamadores**: Clientes que intentan comunicarse nuevamente tras un abandono o desconexión previa.
