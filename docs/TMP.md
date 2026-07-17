Esta es la **lista maestra consolidada** de indicadores para un Contact Center moderno. He integrado las métricas clásicas de tu documento, las de mi primera respuesta y los indicadores avanzados de análisis de datos para ofrecerte una visión 360°.

---

### 1. Nivel: Agente (Micro-gestión y Desempeño)
*Enfoque: Productividad individual, calidad y cumplimiento de procesos.*

1.  **AHT (Average Handle Time):** Tiempo total que el agente dedica a una interacción.
    *   **Cálculo:** `(Tiempo de Habla + Tiempo en Espera + ACW) / Total de Llamadas Atendidas`
2.  **Productividad (Llamadas por Hora):** Cantidad de contactos gestionados en tiempo efectivo.
    *   **Cálculo:** `Total de Llamadas Atendidas / (Tiempo Total de Conexión - Tiempo en Espera/Pausas)`
3.  **Productividad (%):** Intensidad del trabajo mientras el agente estuvo conectado. Porcentaje del tiempo conectado en estados productivos.
    *   **Cálculo:** `(Minutos en Estados Productivos / Minutos Conectados) x 100`
4.  **Utilización:** Rendimiento del agente contra lo planificado (ajustado por tiempo transcurrido si el turno está en curso).
    *   **Cálculo:** `(Minutos Productivos / Minutos Base) x 100` — Capeado al 100%. El denominador usa el tiempo transcurrido si el turno está activo, o el total planificado si ya terminó.
5.  **Tardanza:** Determina si un agente llegó después de la hora programada, considerando un margen de tolerancia.
    *   **Cálculo:** `(Hora Real - Hora Programada) > Minutos de Gracia` → Devuelve verdadero si supera la tolerancia (por defecto 5 min).
6.  **Adherencia al Horario:** Verifica si el estado real del agente corresponde al tipo de actividad esperada (turno, descanso, fuera de jornada).
    *   **Cálculo:** Evalúa el estado actual contra `SHIFT` (estados productivos), `INTRADAY`/`EXCEPTION` (NOT_READY) u `OFF` (desconectado).
7.  **Conformance:** Cumplimiento de la cantidad total de tiempo trabajado versus lo programado.
    *   **Cálculo:** `(Minutos Reales Trabajados / Minutos Programados) x 100`
8.  **Quality Score (Calidad):** Calificación de la interacción según la pauta de monitoreo.
    *   **Cálculo:** `(Puntos Obtenidos / Puntos Totales Evaluados) x 100`
9.  **Tasa de Error Crítico (Compliance):** Interacciones con fallos legales o de seguridad.
    *   **Cálculo:** `(Interacciones con Fallo Crítico / Total Evaluadas) x 100`
10. **ACW Time (After Call Work):** Tiempo dedicado a tareas administrativas tras colgar.
    *   **Cálculo:** `Tiempo Total en Estado "Post-llamada" / Total de Llamadas Atendidas`

---

### 2. Nivel: Equipo (Supervisión y Liderazgo)
*Enfoque: Gestión de grupos, cohesión operativa y desarrollo de personas.*

11. **Shrinkage (Pérdida de Tiempo):** Tiempo que el personal no está disponible para atender (vacaciones, reuniones, pausas).
    *   **Cálculo:** `(Horas No Disponibles / Horas Totales Programadas) x 100`
12. **Attrition (Rotación):** Índice de agentes que abandonan el equipo.
    *   **Cálculo:** `(Bajas en el periodo / Promedio de agentes en el periodo) x 100`
13. **Coaching Coverage:** Porcentaje de agentes que recibieron retroalimentación.
    *   **Cálculo:** `(Agentes con sesión de coaching / Total de agentes en el equipo) x 100`
14. **Variabilidad del AHT:** Mide la consistencia del equipo.
    *   **Cálculo:** `Desviación Estándar de los AHT individuales del equipo.`

---

### 3. Nivel: Servicio / Cola (Experiencia en el Canal)
*Enfoque: Accesibilidad, rapidez y efectividad de la respuesta.*

15. **SLA (Service Level Agreement):** Porcentaje de llamadas contestadas en un tiempo meta.
    *   **Cálculo:** `(Llamadas contestadas antes de X segundos / Total llamadas recibidas) x 100`
16. **Tasa de Abandono:** Clientes que cuelgan antes de ser atendidos.
    *   **Cálculo:** `(Llamadas Abandonadas / Llamadas Entradas) x 100`
17. **ASA (Average Speed of Answer):** Tiempo promedio que un cliente espera en cola.
    *   **Cálculo:** `Tiempo Total de Espera de Llamadas Atendidas / Total de Llamadas Atendidas`
18. **FCR (First Contact Resolution):** Problemas resueltos en el primer intento.
    *   **Cálculo:** `(Casos resueltos en 1er contacto / Total de casos resueltos) x 100`
19. **Containment Rate (Tasa de Contención):** Clientes resueltos por el Bot/IVR sin pasar a un agente.
    *   **Cálculo:** `(Sesiones resueltas en autoservicio / Total de sesiones iniciadas en ese canal) x 100`
20. **Tasa de Transferencia:** Frecuencia con la que un cliente es movido entre agentes.
    *   **Cálculo:** `(Llamadas transferidas / Total de llamadas atendidas) x 100`

---

### 4. Nivel: Departamento (Operaciones y Finanzas)
*Enfoque: Eficiencia de recursos, costos y planificación.*

21. **Cobertura Operativa (Coverage Rate):** Porcentaje de agentes disponibles frente a los programados. Indica si hay suficientes recursos para cubrir la operación.
    *   **Cálculo:** `(Agentes Disponibles / Agentes Programados) x 100`
22. **Tasa de Ausentismo:** Porcentaje de tiempo ausente no planificado sobre el tiempo productivo programado.
    *   **Cálculo:** `(Minutos Ausentes / Minutos Productivos Programados) x 100`
23. **Personal Ausente (Headcount):** Diferencia entre el personal programado para estar productivo y el que efectivamente está presente.
    *   **Cálculo:** `max(0, Personal Programado - Personal Presente)`
24. **Costo por Contacto (CPC):** Lo que cuesta operar cada interacción.
    *   **Cálculo:** `Costos Operativos Totales (Nómina + Tecnología + Sitio) / Volumen Total de Contactos`
25. **Forecasting Accuracy (Precisión del Pronóstico):** Diferencia entre lo planeado y lo real.
    *   **Cálculo:** `1 - (ABS[Volumen Real - Volumen Pronosticado] / Volumen Real)`
26. **Ocupación de Agentes:** Tiempo que el agente está realmente ocupado versus disponible (excluyendo tiempo en auxiliares/pausas).
    *   **Cálculo:** `(Tiempo de Habla + Hold + ACW) / (Tiempo de Conexión Total - Tiempo en Auxiliares) x 100`
27. **Edad Media de la Consulta (Backlog):** Tiempo que los casos pendientes llevan abiertos.
    *   **Cálculo:** `Suma de días/horas de casos abiertos / Total de casos abiertos`

---

### 5. Nivel: Global (Estratégico y Negocio)
*Enfoque: Salud de la marca, lealtad del cliente e impacto financiero.*

28. **CSAT (Customer Satisfaction Score):** Satisfacción inmediata con la atención.
    *   **Cálculo:** `(Número de clientes "Satisfechos" (4 y 5) / Total de encuestas) x 100`
29. **NPS (Net Promoter Score):** Lealtad a largo plazo y recomendación.
    *   **Cálculo:** `% Promotores (9-10) - % Detractores (0-6)`
30. **CES (Customer Effort Score):** Facilidad de resolución.
    *   **Cálculo:** `Promedio de calificación de la escala de esfuerzo (1 al 7).`
31. **Churn Rate Atribuido al Servicio:** Clientes que se van tras una mala experiencia.
    *   **Cálculo:** `(Bajas de clientes tras interacción negativa / Total de clientes que interactuaron)`
32. **Sentiment Score (IA):** Análisis automático del tono del cliente.
    *   **Cálculo:** `Promedio de puntaje de sentimiento extraído de transcripciones (Speech Analytics).`
33. **Revenue por Contacto:** Dinero generado o retenido en la interacción.
    *   **Cálculo:** `Total Ingresos Generados en el periodo / Total Contactos Atendidos`

---

### Recomendación del Analista de Datos:
Para que esta lista sea efectiva, no intentes medir las 33 métricas en un solo panel. Divide los reportes:
*   **Real-Time (Dashboard):** SLA, Abandono, Ocupación y ASA.
*   **Semanal (Coaching):** Calidad, AHT, Adherencia y FCR.
*   **Mensual (Ejecutivo):** NPS, Costo por Contacto, Attrition y ROI.