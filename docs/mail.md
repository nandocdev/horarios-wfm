# Guía Técnica: Diccionario de Métricas y Fórmulas de Desempeño (WFM)

**Para:** Equipo de Operaciones y Supervisión  
**De:** Departamento de Workforce Management (WFM)  
**Asunto:** Definición técnica de métricas, fórmulas y variables (Actualizado)

---

Estimados,

Presentamos la versión actualizada del diccionario de métricas, ajustada a los estados específicos de nuestro sistema y optimizando la precisión del cálculo de tiempo de conexión.

---

### 1. Tiempo Productivo (TP)
Mide la duración acumulada de las tareas de gestión directa o reserva para atención.

*   **Fórmula:** `TP = T_Habla + T_Hold + T_Work + T_Reserved + T_Outbound`
*   **Variables:**
    *   `T_Habla`: Tiempo total en conversación con clientes (entrante).
    *   `T_Hold`: Tiempo total de clientes en espera durante la llamada.
    *   `T_Work`: Tiempo de gestión posterior a la llamada (registrado como estado **Work**).
    *   `T_Reserved`: Tiempo en que el sistema reserva al agente para una llamada entrante inminente.
    *   `T_Outbound`: Tiempo total en gestiones de llamadas salientes.

### 2. Tiempo Logueado Total (TLT)
Representa la sumatoria de toda la actividad registrada por el agente en la plataforma.

*   **Fórmula:** `TLT = Σ (Duración_Actividad)`
*   **Variables:**
    *   `Duración_Actividad`: Tiempo transcurrido en cada uno de los estados registrados por el sistema (Available, Busy, Work, Reserved, Aux, etc.).
    *   `Σ`: Sumatoria de todos los registros de actividad dentro del periodo evaluado.
    *   *(Nota: Este método es más preciso que el cálculo por sesiones, ya que elimina discrepancias por micro-desconexiones o solapamientos).*

### 3. Productividad (PRD)
Evalúa la eficiencia del tiempo de conexión en relación a las tareas productivas.

*   **Fórmula:** `PRD = (TP / TLT) x 100`
*   **Variables:**
    *   `TP`: Tiempo Productivo (ver punto 1).
    *   `TLT`: Tiempo Logueado Total (ver punto 2).

### 4. Tiempo Programado Total (TPT)
Es el compromiso de tiempo definido en el Roster o malla horaria.

*   **Fórmula:** `TPT = H_Fin_Turno - H_Inicio_Turno`
*   **Variables:**
    *   `H_Inicio_Turno`: Hora de entrada programada.
    *   `H_Fin_Turno`: Hora de salida programada.

### 5. Tiempo en Actividad Programada (TAP)
Mide la coincidencia entre la realidad operativa y la planificación.

*   **Fórmula:** `TAP = Σ (Duración donde Estado_Real == Estado_Programado)`
*   **Variables:**
    *   `Estado_Real`: El estado en que se encontraba el agente en el sistema.
    *   `Estado_Programado`: La actividad que el agente debía estar realizando según su malla.

### 6. Adherencia (ADH)
Mide el cumplimiento disciplinado del horario asignado.

*   **Fórmula:** `ADH = (TAP / TPT) x 100`
*   **Variables:**
    *   `TAP`: Tiempo en Actividad Programada (ver punto 5).
    *   `TPT`: Tiempo Programado Total (ver punto 4).

### 7. Utilización (UTL)
Mide la disponibilidad real para la operación, excluyendo tiempos administrativos exentos.

*   **Fórmula:** `UTL = (TLT / (TPT - T_Exento)) x 100`
*   **Variables:**
    *   `TLT`: Tiempo Logueado Total (ver punto 2).
    *   `TPT`: Tiempo Programado Total (ver punto 4).
    *   `T_Exento`: Tiempos programados no disponibles para atención (Almuerzo, Capacitación, Pausas de Ley).

### 8. Ocupación (OCP)
Mide la intensidad de trabajo del agente durante sus periodos de disponibilidad.

*   **Fórmula:** `OCP = (T_Habla + T_Hold + T_Work) / (TLT - T_Auxiliares)`
*   **Variables:**
    *   `T_Auxiliares`: Tiempos en estados no disponibles (Break, Almuerzo, Baño, etc.).

---

Atentamente,

**Equipo de Workforce Management**
