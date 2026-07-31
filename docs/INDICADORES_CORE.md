# Catálogo de Indicadores Core — WFM

Catálogo canónico de los indicadores del motor de métricas de **horarios-wfm**. Define la fórmula, las variantes de entrada, la salida y los fallos típicos de cada métrica para que todos los módulos (WFM, Operations, Quality) compartan una única definición.

## Convenciones

- **ID**: cada indicador tiene un ID estable (`IND-###`). Los IDs no se reutilizan ni reasignan.
- **Taxonomía**: los indicadores se agrupan por dominio funcional en seis categorías (Forecast & Modelos, Capacity Planning, Scheduling, Real-Time Operations, Service Quality, Cost & Workforce Health), separando indicadores históricos, predictivos y reglas de control.
- **Horizonte**: cada categoría opera sobre un horizonte distinto — predictivo (forecast/capacity), plan (schedule), tiempo real (RTA) o histórico.
- **Cross-referencias**: cuando un indicador se relaciona con otro se referencia con su ID (`Ver IND-XXX`).
- **Unidades**: porcentajes (%), segundos (s), Erlangs, contactos o agentes. Indicadas en la salida de cada indicador.

## Índice

| Categoría                                               | Horizonte               | Indicadores       |
| ------------------------------------------------------- | ----------------------- | ----------------- |
| [1. Forecast & Modelos](#1-forecast--modelos)           | Predictivo / Accuracy   | IND-001 → IND-009 |
| [2. Capacity Planning](#2-capacity-planning)            | Predictivo              | IND-010 → IND-018 |
| [3. Scheduling](#3-scheduling)                          | Plan / Proceso          | IND-019 → IND-022 |
| [4. Real-Time Operations](#4-real-time-operations)      | Tiempo real / Histórico | IND-023 → IND-033 |
| [5. Service Quality](#5-service-quality)                | Histórico               | IND-034 → IND-038 |
| [6. Cost & Workforce Health](#6-cost--workforce-health) | Histórico               | IND-039 → IND-040 |

---

## 1. Forecast & Modelos

### IND-001 · Forecast de Volumen (Contact Volume)
- **Descripción**: Predicción de contactos entrantes por intervalo (15/30 min). Base de todo el staffing.
- **Método de cálculo**:
  Histórico + estacionalidad + eventos + tendencia (ARIMA/Prophet/regresión).
  `F_t = base × seasonality × event_factor × trend`
- **Variantes de entrada**:
  - Gross vs Net (después de abandonos/reintentos)
  - Por skill/cola/canal
  - Incluye o excluye callbacks/IVR
  - Intervalo 15 vs 30 min
- **Salida**: Contactos esperados por intervalo + intervalo de confianza.
- **Fallo típico**: No separar offered vs handled → overstaff crónico.

### IND-002 · Forecast de AHT
- **Descripción**: Tiempo medio de manejo predicho por intervalo/skill.
- **Método de cálculo**:
  Media ponderada histórica + ajuste por mix de contactos + cambios de proceso.
  `AHT = Talk + Hold + ACW`
- **Variantes de entrada**:
  - Talk Time puro vs Talk+Hold
  - ACW incluido o separado
  - Por tipo de contacto (nuevo vs recurrente)
  - Outliers truncados (percentil 95/99)
- **Salida**: Segundos por contacto.
- **Trade-off**: AHT inflado por ACW largo mata Occupancy real.
- **Relación**: El desglose del AHT real está en [IND-037](#ind-037--average-handle-time-components).

### IND-003 · Intra-day Variance / Forecast Accuracy
- **Descripción**: Error del forecast vs real en tiempo real.
- **Método de cálculo**:
  `MAPE = |Actual - Forecast| / Actual`
  o WAPE ponderado.
- **Variantes de entrada**:
  - Por intervalo
  - Volumen vs AHT vs Staffing
- **Salida**: Error %.
- **Uso**: Dispara reforecast y moves en RTA. Para WAPE y RMSE ver [IND-005](#ind-005--wape) e [IND-006](#ind-006--rmse); para el sesgo sistemático, [IND-004](#ind-004--forecast-bias).

### IND-004 · Forecast Bias
- **Descripción**: Sesgo sistemático del forecast. MAPE no lo detecta.
- **Método de cálculo**:
  `Bias = (Forecast - Actual) / Actual`
  (o media de los errores relativos)
- **Variantes de entrada**:
  - Por intervalo, día, skill
  - Volumen vs AHT
  - Signed vs absolute
- **Salida**: % (positivo = sobre-forecast).
- **Impacto**: Bias +4 % constante = sobrestaff permanente y costo oculto.

### IND-005 · WAPE (Weighted Absolute Percentage Error)
- **Descripción**: Más estable que MAPE. Plataformas modernas lo prefieren.
- **Método de cálculo**:
  `WAPE = Σ|Error| / ΣActual`
- **Variantes de entrada**:
  - Ponderado por volumen o por valor de negocio
  - Solo intervalos con Actual > 0
- **Salida**: %.
- **Ventaja**: No explota con Actual cercanos a cero.

### IND-006 · RMSE (Root Mean Square Error)
- **Descripción**: Penaliza errores grandes. Complemento obligatorio de MAPE.
- **Método de cálculo**:
  `RMSE = √(Σ(error²) / n)`
- **Variantes de entrada**:
  - Sobre volumen, AHT o staffing
  - Por horizonte (intraday vs D+7)
- **Salida**: Misma unidad que la métrica.
- **Uso**: Comparar modelos. RMSE bajo + Bias alto = modelo “bonito” pero sesgado.

### IND-007 · Overdispersion (Coeficiente de Variación)
- **Descripción**: Erlang C asume que las llamadas llegan en un proceso de Poisson puro (la varianza de las llegadas es igual a la media). En centros de contactos reales, la llegada es *sobredispersa* (ráfagas por caídas de sistemas, campañas o fallas masivas).
- **Método de cálculo**:
  `Variance-to-Mean Ratio (VMR) = Var(Arrivals) / Mean(Arrivals)`
  Si VMR > 1, Erlang C subestima sistemáticamente el personal necesario.
- **Salida**: Factor de corrección para el volumen de entrada antes de correr Erlang.

### IND-008 · Intraday Reforecast
- **Descripción**: Ajuste del forecast original con la tendencia real del día. No es un forecast nuevo desde cero.
- **Método de cálculo**:
  `Forecast_Nuevo = Forecast_Original + Real_Trend_Adjustment`
- **Variantes de entrada**:
  - Trend = ratio Actual/Forecast de intervalos ya cerrados
  - Suavizado (EWMA) vs crudo
  - Horizontes restantes del día
- **Salida**: Nuevo volumen/AHT por intervalo restante.
- **Uso**: Dispara moves de RTA.

### IND-009 · Channel Mix
- **Descripción**: Distribución de volumen por canal. Impacta forecast, AHT y concurrency.
- **Método de cálculo**:
  `Channel Mix = Volume_Canal / Volume_Total`
  (Voice, Chat, Email, Backoffice, etc.)
- **Variantes de entrada**:
  - Por intervalo o día
  - Offered vs Handled
  - Con o sin transferencias entre canales
- **Salida**: % por canal.
- **Impacto**: Cambio de mix sin recalibrar AHT y concurrency = error de staffing inmediato.

---

## 2. Capacity Planning

### IND-010 · Offered Load (Traffic Intensity)
- **Descripción**: Intensidad de tráfico ofrecida antes de aplicar Erlang. Unidad base de todo cálculo de staffing.
- **Método de cálculo**:
  `Load = ArrivalRate × AHT`
  (ArrivalRate en contactos/intervalo, AHT en la misma unidad de tiempo → resultado en Erlangs)
- **Variantes de entrada**:
  - ArrivalRate = Offered vs Handled
  - AHT con o sin Hold/ACW
  - Intervalo 15 vs 30 min
  - Por skill o agregado
- **Salida**: Erlangs.
- **Fallo**: Usar Handled en vez de Offered subestima carga real → understaff.

### IND-011 · Erlang C / Erlang A (Staffing Requirement)
- **Descripción**: Agentes necesarios para cumplir Service Level dado volumen + AHT + objetivo SL.
- **Método de cálculo**:
  Erlang C (sin abandonos) o Erlang A (con abandonos).
  `Agents = f(λ, μ, P(wait > T), abandonment)`
  Donde λ = arrival rate, μ = 1/AHT.
- **Variantes de entrada**:
  - Erlang C clásico vs Erlang A (más realista)
  - Target SL 80/20 vs 90/30
  - Incluye o no multi-skill efficiency
  - Shrinkage aplicado antes o después
- **Salida**: Agentes requeridos por intervalo (Required Staff).
- **Edge case**: Erlang C sobreestima en colas con alto abandono → headcount inflado.

### IND-012 · Required Staff (Net Staffing)
- **Descripción**: Agentes netos necesarios después de shrinkage.
- **Método de cálculo**:
  `Required = Erlang_Agents / (1 - Shrinkage)`
  o
  `Net = Scheduled - Shrinkage_expected`
- **Variantes de entrada**:
  - Shrinkage fijo vs dinámico (por día/hora)
  - Incluye o no overtime/auxiliares
  - Multi-skill credit (0.7–0.9)
- **Salida**: Headcount neto vs scheduled. Over/Under por intervalo.
- **Relación**: En tiempo real, la posición neta se mide con [IND-028](#ind-028--net-staffing-position-overunder) y el déficit con [IND-031](#ind-031--service-deficit-missing-staff).

### IND-013 · Shrinkage
- **Descripción**: % de tiempo no productivo (breaks, training, absenteeism, meetings, system downtime).
- **Método de cálculo**:
  `Shrinkage = 1 - (Productive_Time / Paid_Time)`
  Componentes: Planned + Unplanned.
- **Variantes de entrada**:
  - Paid vs Logged time
  - Incluye o no ACW/Hold como productivo
  - Por agente vs por equipo
- **Salida**: % (típicamente 25–35 %).
- **Fallo**: Usar shrinkage estático anual → sub-staff diario.

### IND-014 · Interval Shrinkage Forecast
- **Descripción**: Shrinkage esperado por intervalo (no el histórico promedio).
- **Método de cálculo**:
  `Forecast Shrinkage = Planned Shrinkage + Expected Unplanned`
- **Variantes de entrada**:
  - Planned = breaks + training + meetings
  - Unplanned = absenteeism histórico del mismo día/hora
  - Por equipo o site
- **Salida**: %.
- **Impacto**: Usar shrinkage anual fijo = error sistemático de staffing.

### IND-015 · Interval Occupancy Forecast
- **Descripción**: Ocupación esperada antes de publicar el schedule. No es la ocupación real.
- **Método de cálculo**:
  `Forecast Occupancy = Forecast Load / Forecast Agents`
- **Variantes de entrada**:
  - Load con o sin shrinkage
  - Agents = Required vs Scheduled
  - Por skill o multi-skill
- **Salida**: %.
- **Uso**: Si > 85–88 % antes de schedule → replanificar.

### IND-016 · Queue Delay Probability
- **Descripción**: Probabilidad de que un contacto tenga que esperar. Sale directo de Erlang.
- **Método de cálculo**:
  `P(wait) = ErlangC(Agents, Load)`
  (o ErlangA con abandonos)
- **Variantes de entrada**:
  - Erlang C vs A
  - Con o sin multi-skill efficiency
- **Salida**: Probabilidad (0–1).
- **Uso**: Dashboard de riesgo en tiempo real.

### IND-017 · Expected Wait Time (EWT)
- **Descripción**: Tiempo de espera predicho (no histórico). ASA es pasado; EWT es futuro.
- **Método de cálculo**:
  `EWT = f(ErlangC o ErlangA, Agents, Load, AHT)`
- **Variantes de entrada**:
  - Con o sin tasa de abandono
  - Ajuste por backlog actual
- **Salida**: Segundos (ej. “Estimated wait: 2m 10s”).
- **Edge case**: Sin backlog real el EWT miente.

### IND-018 · Multi-skill Efficiency
- **Descripción**: Crédito de productividad cuando un agente atiende múltiples skills.
- **Método de cálculo**:
  `Effective Staff = Σ (agent_skill_weight)`
  (weight típico 0.7–0.95 según overlap)
- **Variantes de entrada**:
  - Weight fijo vs dinámico por carga
  - Por agente o por skill group
- **Salida**: Agentes efectivos.
- **Fallo**: Poner weight = 1.0 → overpromise de capacidad.

---

## 3. Scheduling

### IND-019 · Schedule Efficiency / Coverage
- **Descripción**: Qué tan bien el roster cubre el Required Staff.
- **Método de cálculo**:
  `Coverage = min(Scheduled, Required) / Required`
  o
  `Efficiency = 1 - |Scheduled - Required| / Required`
- **Variantes de entrada**:
  - Por intervalo vs día
  - Con o sin overtime
- **Salida**: % de cobertura / over-under netos.
- **Relación**: Complementa a [IND-020](#ind-020--staffing-efficiency) y [IND-021](#ind-021--schedule-fit-score), que miran el ajuste del schedule desde otros ángulos.

### IND-020 · Staffing Efficiency
- **Descripción**: Qué tan ajustado está el schedule al requerimiento. Diferente de Coverage.
- **Método de cálculo**:
  `Efficiency = Required Staff / Scheduled Staff`
- **Variantes de entrada**:
  - Required con o sin shrinkage
  - Scheduled incluye o no overtime
  - Por intervalo vs día
- **Salida**: Ratio (ideal ≈ 1.0).
- **Fallo**: > 1.0 sistemático = understaff crónico; < 0.9 = sobre-costo.

### IND-021 · Schedule Fit Score
- **Descripción**: Distancia absoluta entre Required y Scheduled. Usado por Genesys/NICE para rankear horarios.
- **Método de cálculo**:
  `Fit Score = Σ |Required_i - Scheduled_i|`
  (menor = mejor)
- **Variantes de entrada**:
  - Ponderado por volumen o no
  - Solo intervalos productivos
  - Normalizado por total Required
- **Salida**: Valor absoluto o normalizado.
- **Nota**: No es Coverage. Coverage mira solo el mínimo; Fit mira la forma completa.

### IND-022 · Schedule Compliance
- **Descripción**: KPI administrativo. Verifica existencia, publicación, aceptación y modificaciones del horario.
- **Método de cálculo**:
  Checklist binario o score:
  Publicado + Aceptado + Sin cambios no autorizados + Dentro de ventana de publicación.
- **Variantes de entrada**:
  - Incluye o no cambios de último minuto aprobados
  - Por agente o por equipo
- **Salida**: % o score.
- **Nota**: No mide comportamiento del agente. Mide proceso de WFM.

---

## 4. Real-Time Operations

### IND-023 · Occupancy (Ocupación)
- **Descripción**: % de tiempo que el agente está manejando contactos vs tiempo logueado disponible.
- **Método de cálculo**:
  `Occupancy = (Talk + Hold + ACW) / Staffed_Time`
  o
  `Handled × AHT / (Agents × Interval_Length)`
- **Variantes de entrada**:
  - Incluye Hold o no
  - Staffed = Available + Busy vs solo Available
  - Target 75–85 % (por encima quema agentes)
- **Salida**: %.
- **Trade-off**: Occupancy > 90 % destruye FCR y aumenta attrition.
- **Relación**: El límite superior de control está en [IND-033](#ind-033--occupancy-ceiling).

### IND-024 · Utilization (Utilización)
- **Descripción**: Tiempo productivo total / tiempo pagado (más amplio que Occupancy).
- **Método de cálculo**:
  `Utilization = Productive_Time / Paid_Time`
- **Variantes de entrada**:
  - Productive = solo handle vs handle + training obligatorio
  - Excluye o incluye overtime
- **Salida**: %. Diferencia clave vs Occupancy: incluye downtime planificado.

### IND-025 · Adherence (RTA – Real-Time Adherence)
- **Descripción**: % de tiempo que el agente está en el estado programado (Available, Break, etc.).
- **Método de cálculo**:
  `Adherence = Time_in_Scheduled_State / Scheduled_Time`
  (ventana de tolerancia ±2–5 min)
- **Variantes de entrada**:
  - Strict (estado exacto) vs Flexible (cualquier productivo)
  - Incluye o no login tardío
  - Por intervalo vs día completo
  - Conformance (cumplió el bloque) vs Adherence (estado exacto)
- **Salida**: %.
- **Edge case**: Agentes que se “esconden” en ACW o Aux para inflar Adherence.

### IND-026 · Conformance
- **Descripción**: % de tiempo trabajado dentro de la ventana del schedule (independiente del estado exacto).
- **Método de cálculo**:
  `Conformance = Time_Worked_in_Schedule_Window / Scheduled_Time`
- **Variantes de entrada**:
  - Solo login/logout vs actividad real
  - Tolerancia de minutos
- **Salida**: %. Más laxo que Adherence. Usar ambos o estás ciego.

### IND-027 · Login Compliance
- **Descripción**: Desviación exacta de hora de login vs schedule. Distinto de Conformance.
- **Método de cálculo**:
  `Login Compliance = Actual_Login_Timestamp - Scheduled_Login_Timestamp`
  (en segundos)
- **Variantes de entrada**:
  - Solo primer login del día vs cada bloque
  - Tolerancia ±X minutos
- **Salida**: Segundos (positivo = tarde).
- **Uso RTA**: Alerta temprana de understaff.

### IND-028 · Net Staffing Position (Over/Under)
- **Descripción**: Diferencia entre agentes logueados productivos y Required en tiempo real.
- **Método de cálculo**:
  `Net = Actual_Staffed_Productive - Required`
- **Variantes de entrada**:
  - Solo Available vs Available+Busy
  - Ajustado por Adherence real
- **Salida**: +over / -under por intervalo. Dispara acciones RTA (breaks, overtime, skill moves).
- **Relación**: La versión planificada del net staffing es [IND-012](#ind-012--required-staff-net-staffing); el déficit orientado a acción es [IND-031](#ind-031--service-deficit-missing-staff).

### IND-029 · Interval Service Variance
- **Descripción**: Desviación del Service Level real vs objetivo. Disparador de RTA.
- **Método de cálculo**:
  `Service Variance = SL_Actual - SL_Target`
- **Variantes de entrada**:
  - Por intervalo o rolling
  - Incluye o no abandonos en el cálculo de SL
- **Salida**: Puntos porcentuales.
- **Acción**: Negativo sostenido → moves, overtime o skill change.

### IND-030 · Queue Backlog
- **Descripción**: Contactos abiertos que exceden la capacidad actual. Crítico en chat/email.
- **Método de cálculo**:
  `Backlog = Open Contacts - (Active Agents × Capacity_per_Agent)`
- **Variantes de entrada**:
  - Capacity = 1 (voice) vs >1 (chat concurrent)
  - Incluye o no contactos en hold
- **Salida**: Número de contactos.
- **Acción RTA**: Si > 0 sostenido → reasignar o abrir overtime.

### IND-031 · Service Deficit (Missing Staff)
- **Descripción**: Agentes faltantes orientados a impacto operativo. Relacionado con Net Staffing pero enfocado en déficit.
- **Método de cálculo**:
  `Missing Staff = Required - Actual_Staffed_Productive`
- **Variantes de entrada**:
  - Actual = solo Available vs Available+Busy
  - Ajustado por Adherence real
- **Salida**: Número de agentes (positivo = déficit).
- **Acción**: Prioriza overtime, skill moves o VTO inverso.

### IND-032 · Agent Availability Ratio
- **Descripción**: Proporción de agentes realmente disponibles sobre los logueados.
- **Método de cálculo**:
  `Availability Ratio = Available_Agents / Logged_Agents`
- **Variantes de entrada**:
  - Available = Ready + Idle
  - Excluye o incluye agentes en ACW/Hold
- **Salida**: %.
- **Uso dashboard**: Si cae < 70–75 % con Occupancy alta → problema de estados o burnout.

### IND-033 · Occupancy Ceiling
- **Descripción**: Regla de control, no fórmula compleja. Límite superior de Occupancy para evitar burnout.
- **Método de cálculo**:
  `if Occupancy > Ceiling (típicamente 85–88 %) → Alerta de riesgo`
- **Variantes de entrada**:
  - Ceiling fijo vs dinámico por skill/día
  - Occupancy real vs forecast
- **Salida**: Flag binario + valor actual.
- **Trade-off**: Ceiling alto sube throughput corto plazo y destroza FCR/attrition largo plazo.

---

## 5. Service Quality

### IND-034 · Service Level (SL)
- **Descripción**: % de contactos contestados dentro del umbral (ej. 80 % en 20 s).
- **Método de cálculo**:
  `SL = Answered_within_Threshold / (Offered - Abandoned_within_Threshold)`
  o variante “of offered”.
- **Variantes de entrada**:
  - Incluye abandonos o no
  - Threshold por skill
  - Rolling vs fixed interval
- **Salida**: %.
- **Fallo clásico**: Contar solo handled → SL artificialmente alto.
- **Relación**: La desviación frente al objetivo se vigila con [IND-029](#ind-029--interval-service-variance).

### IND-035 · ASA (Average Speed of Answer)
- **Descripción**: Tiempo medio de espera hasta contestación.
- **Método de cálculo**:
  `ASA = Total_Wait_Time / Answered_Contacts`
- **Variantes de entrada**:
  - Solo contestados vs incluye abandonos (entonces es AWT)
- **Salida**: Segundos.

### IND-036 · Abandonment Rate
- **Descripción**: % de contactos que cuelgan antes de ser atendidos.
- **Método de cálculo**:
  `Abandon = Abandoned / Offered`
- **Variantes de entrada**:
  - Short abandons (<5 s) excluidos o no
  - Por skill/cola
- **Salida**: %. Correlacionado inversamente con staffing.

### IND-037 · Average Handle Time Components
- **Descripción**: Desglose obligatorio de AHT. Dashboards que solo muestran AHT total ocultan problemas.
- **Método de cálculo**:
  `AHT = Talk Time + Hold Time + ACW`
  Cada componente se calcula como suma total / contactos handled.
- **Variantes de entrada**:
  - Talk Time puro vs Talk+Hold
  - ACW (After Call Work / Wrap) incluido o separado
  - Hold Time con o sin mute
  - Outliers truncados (p95/p99)
- **Salida**: Segundos por componente + AHT total.
- **Trade-off**: ACW largo infla AHT y destruye Occupancy real.
- **Relación**: La versión predictiva del AHT está en [IND-002](#ind-002--forecast-de-aht).

### IND-038 · FCR (First Contact Resolution) Real — Auditoría de Reincidencia
- **Descripción**: % de clientes que no vuelven a contactar por el mismo motivo en una ventana estricta. La tipificación del agente miente; el dato del ANI no.
- **Método de cálculo**:
  `FCR = 1 - (Unique_Callers_With_Repeat_Contact_Within_X_Hours / Total_Unique_Callers)`
  (Ventana estándar: 24 a 72 horas según vertical)
- **Variantes de entrada**:
  - Reincidencia por mismo canal vs omnicanal (ej. chat + voz)
  - Exclusión intencional de seguimientos programados
- **Salida**: %.
- **Fallo típico**: Medir FCR preguntándole al cliente en encuesta o confiando en el tag del CRM (“Problema Resuelto”). Destruye la rentabilidad oculta por llamadas repetidas (churn operativo).

---

## 6. Cost & Workforce Health

### IND-039 · Cost per Contact / Cost per Resolution (CPC / CPR)
- **Descripción**: Costo financiero real de procesar un contacto o, peor aún, de resolverlo efectivamente. Vincula WFM con finanzas.
- **Método de cálculo**:
  `CPC = Total_Operating_Cost_Interval / Handled_Volume`
  `CPR = Total_Operating_Cost_Interval / Resolved_Volume (FCR ajustado)`
- **Variantes de entrada**:
  - Costo hora agente (Full-loaded: salario + cargas sociales + infraestructura + licencias software)
  - Costo por canal (Voz vs Chat vs Self-service/IVR)
- **Salida**: Moneda por contacto.
- **Trade-off**: Ahorrar en staffing (bajar costos) dispara el AHT y hunde el FCR, elevando el Cost per Resolution total.

### IND-040 · Attrition / Churn Rate Operativo
- **Descripción**: Velocidad a la que la capacidad operativa (headcount entrenado) se degrada por renuncias o bajas.
- **Método de cálculo**:
  `Attr_Rate = (Agents_Left_In_Period / Average_Active_Agents) × 100`
  Medido mensual y anualizado (desglosando la curva de mortalidad del agente: los primeros 90 días queman el mayor % de attrition).
- **Salida**: % de rotación.
- **Impacto WFM**: Un attrition alto descalabra el Shrinkage proyectado (más tiempo en nesting, curvas de aprendizaje planas que inflan el AHT un 30–50 %).

---

## Mapa de categorías

La taxonomía separa el motor de métricas en seis dominios con distinto horizonte, módulo natural y uso:

| Categoría                                               | Horizonte               | Módulo natural              | Uso principal                                         |
| ------------------------------------------------------- | ----------------------- | --------------------------- | ----------------------------------------------------- |
| [1. Forecast & Modelos](#1-forecast--modelos)           | Predictivo / Accuracy   | WFM (planning)              | Alimentar Capacity Planning y auditar el forecast     |
| [2. Capacity Planning](#2-capacity-planning)            | Predictivo              | WFM                         | Convertir volumen/AHT en Required Staff               |
| [3. Scheduling](#3-scheduling)                          | Plan / Proceso          | WFM                         | Medir qué tan bien el roster cubre el requerimiento   |
| [4. Real-Time Operations](#4-real-time-operations)      | Tiempo real / Histórico | Operations                  | Disparar acciones RTA y monitoreo en vivo             |
| [5. Service Quality](#5-service-quality)                | Histórico               | Operations / Connect        | Medir la experiencia de servicio entregada            |
| [6. Cost & Workforce Health](#6-cost--workforce-health) | Histórico               | Quality / Finanzas (futuro) | Vincular WFM con costos y sostenibilidad de plantilla |

**Notas de alcance (v1.0, ver PRD §11):**

- La predicción avanzada (ML para forecast de demanda) y Erlang-C nativo están **fuera de alcance** en v1.0 — Erlang-C se importa desde planillas Excel (RF-WFM-02) y el forecast entra por importación.
- Las métricas financieras (IND-039) y de attrition (IND-040) son orientativas para diseño de datos; no son compromiso de entrega v1.0.
- Los indicadores de esta categoría (IND-038 FCR, IND-009 Channel Mix) requieren datos de contacto (ANI, canal) que hoy residen en ConnectModule.
