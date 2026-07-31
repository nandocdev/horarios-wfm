# Objetivo

Realiza una auditoría funcional y arquitectónica de todos los módulos del sistema HorariosWFM para validar que cada funcionalidad obtiene sus datos desde la fuente correcta.

El análisis debe contrastarse con:

- PRD
- Arquitectura
- Modelo de Datos
- Casos de uso
- Responsabilidades de cada módulo

No asumas implementaciones; identifica explícitamente las fuentes de datos que cada módulo debería consumir.

---

# Reglas generales

Para cada módulo identifica:

1. Qué información consume.
2. De qué tabla/modelo debe provenir.
3. Si existe una única fuente de verdad (Single Source of Truth).
4. Si existen duplicaciones de información.
5. Si algún cálculo utiliza una fuente incorrecta.
6. Si existe acoplamiento innecesario entre módulos.
7. Si el módulo debería consumir datos derivados en lugar de datos crudos.
8. Si requiere una vista materializada, tabla agregada o proceso ETL adicional.
9. Qué información pertenece realmente al módulo Connect y cuál al módulo Operations.
10. Qué cálculos deberían centralizarse en el módulo Operations.

No propongas cambios sin justificar el impacto arquitectónico.

---

# Entregable esperado

Para cada módulo generar la siguiente estructura:

## Módulo

### Responsabilidad

Descripción breve.

### Datos utilizados

| Información | Fuente | Tabla | Observaciones |
| ----------- | ------ | ----- | ------------- |

Ejemplo:

| Llamadas ofrecidas | Cisco CUIC | call_records | Fuente primaria |
| AHT | Cisco CUIC | agent_call_performance | No calcular desde call_records |
| Adherencia | WFM + Cisco | weekly_schedule_assignments + agent_state_transitions | Requiere reconciliación |

---

### Validación

Indicar:

- ✅ Correcto
- ⚠ Puede mejorarse
- ❌ Incorrecto

Explicar el motivo.

---

### Riesgos

Ejemplos:

- duplicación
- cálculo inconsistente
- dependencia incorrecta
- información derivada almacenada
- acoplamiento entre módulos

---

### Recomendación

Indicar la arquitectura recomendada.

No proponer cambios cosméticos.

Solo cambios que reduzcan deuda técnica.

---

# Fuentes oficiales de datos

## 1. Información de colas de llamadas

**Modelo**

```
call_records
```

Esta tabla representa la fuente oficial para todas las métricas relacionadas con la interacción de la llamada con la cola.

Debe utilizarse para:

- llamadas ofrecidas
- llamadas atendidas
- llamadas abandonadas
- ASA
- tiempo en cola
- CSQ
- aplicación
- disposición de la llamada
- duración de cola
- información del flujo ACD

No debe utilizarse para calcular tiempos del agente.

Los campos Cisco correspondientes son:

| Campo                                        | Descripción                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| -------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ID de nodo - ID de sesión - N.º de secuencia | La ID de nodo es la ID numérica exclusiva, que comienza por el 1, que el sistema asigna a cada servidor de Unified CCX en el clúster.<br><br>La ID de sesión es la ID de sesión exclusiva que el sistema asigna a una llamada.<br><br>El número de secuencia de sesión es el número que el sistema asigna a cada segmento de llamada. El número de secuencia de sesión aumenta en uno por cada segmento de llamada.<br><br>Estos tres valores juntos identifican de forma exclusiva a una llamada de distribución de llamada automática (ACD) que procesa el sistema.                                                                                                        |
| Hora de inicio de llamada                    | Fecha y hora en las que comienza la llamada.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Hora de fin de llamada                       | Fecha y hora en las que se desconectó, transfirió o redirigió la llamada.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| Disposición de contacto                      | Disposición de la llamada.<br><br>*   **1**: abandonadas<br>*   **2**: manejadas<br>*   **4**: anuladas<br>*   **De 5 a 98**: rechazadas<br>*   **99**: limpias                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| Número del autor (número que llama)          | Número de teléfono del autor de la llamada.<br><br>*   **1= Agente.** Llamada originada en un agente. Muestra la extensión de Unified CCX del agente.<br>*   **2 = Dispositivo.** Llamada originada por una persona que llama simulada (se utiliza para realizar pruebas) y un agente de teléfono, donde el agente no está actualmente conectado. Muestra el número de puerto de la interfaz de telefonía informática (Unified CCX Computer Telephony Interface, CTI).<br>*   **3 = Desconocido.** La llamada la origina una persona que llama externa a través de una gateway, o bien un dispositivo no supervisado. Muestra el número de teléfono de la persona que llama. |
| Número de destino                            | Número de teléfono de destino.<br><br>*   **1 = Agente.** La llamada se presenta a un agente. Muestra la extensión de Unified CCX o la extensión que no es de Unified CCX del agente.<br>*   **2 = Dispositivo.** La llamada se presenta a un punto de ruta. Muestra el número de puerto de CTI.<br>*   **3 = Desconocido.** La llamada se ha presentado a un destino externo a través de una gateway, o bien a un dispositivo no supervisado. Muestra el número de teléfono marcado.                                                                                                                                                                                        |
| Número marcado                               | Número marcado originalmente por la persona que llama. Si la llamada es una transferencia, se muestra el número al que se transfiere la llamada.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| Nombre de aplicación                         | Aplicación de Unified CCX o Unified IP IVR asociada al punto de ruta.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| Nombres de cola de servicio de contacto      | Cola de servicio de contacto a la cual se ha enrutado la llamada.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| Tiempo en cola                               | Tiempo transcurrido entre el momento en que una llamada entra en la CSQ y la contesta un agente que pertenece a la CSQ.<br><br>**Información de resumen**: valores totales de esta columna.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| Nombre del agente                            | Nombre y apellidos del agente.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Tiempo de timbre                             | Tiempo transcurrido desde que suena el timbre hasta que la llamada se contesta por un agente, se dirige a otro agente o se desconecta. Este campo está vacío si la llamada no se ha dirigido a un agente.<br><br>**Información de resumen**: valores totales de esta columna.                                                                                                                                                                                                                                                                                                                                                                                                |
| Tiempo de conversación                       | Tiempo que el agente ha pasado en el estado En conversación.<br><br>**Información de resumen**: valores totales de esta columna.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| Tiempo de trabajo                            | Tiempo que el agente ha pasado en el estado Trabajo.<br><br>**Información de resumen**: valores totales de esta columna.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     |


La integracion en Database corresponde a:

```
id
cisco_call_id
sequence_number
queue_id
phone_number
destination_number
ivr_started_at
ivr_ended_at
talk_time
ring_time
work_time
queue_time
contact_disposition
employee_id
raw_agent_name
citizen_identifier
case_subtype_id
description
status
closed_at
created_at
updated_at
```
---

## 2. Información del desempeño del operador

**Modelo**

```
agent_call_performance
```

Esta es la fuente oficial para todos los indicadores individuales del agente.

Debe utilizarse para:

- llamadas atendidas por agente
- AHT
- tiempo conversación
- tiempo hold
- ACW
- duración
- llamadas ACD
- llamadas no ACD
- transferencias

No utilizar call_records para estos cálculos salvo que sea estrictamente necesario para conciliación.

Los campos Cisco correspondientes son:

| Campo                               | Descripción                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Nombre del agente                   | Nombre y apellidos del agente.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                           |
| ID de agente                        | ID de conexión del agente.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Extensión                           | Extensión de Unified CCX que Unified Communications Manager asignó al agente.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| Hora de inicio de llamada           | Fecha y hora en las que el segmento de llamada llama en la extensión de agente.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| Hora de finalización de llamada     | Fecha y hora en las que se desconectó o transfirió el segmento de llamada.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               |
| Duración                            | Tiempo transcurrido entre la hora de inicio de la llamada y la hora de fin de la misma.<br><br>**Información de resumen**: valores totales de esta columna.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              |
| Número marcado                      | Número de teléfono que ha marcado la persona que llama.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| ANI de llamada                      | Número de teléfono del autor de la llamada. (ANI = identificación de número automático)                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| Llamada dirigida por centro         | Cola de servicio de contacto (CSQ) que manejó la llamada. Una llamada se considera manejada si la persona que llama se ha conectado a un agente mientras se encontraba en esta cola de servicio de contacto.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| Otras colas de servicio de contacto | Si la llamada se pone en cola en varias CSQ, se muestra el nombre de una de las CSQ en las que se puso en cola la llamada.<br><br>Muestra "…" para indicar que existen más CSQ en las que se puso en cola la llamada.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| Habilidades de llamada              | Habilidades asociadas a la CSQ que manejó la llamada.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |
| Tiempo de conversación              | *   Llamadas de Unified CCX: tiempo transcurrido desde que el agente se conectó a la llamada hasta que esta se desconectó o se transfirió, sin incluir el tiempo en espera.<br>*   Llamadas que no son de Unified CCX: tiempo transcurrido desde que el agente se conectó a la llamada hasta que esta se desconectó o se transfirió.<br><br>**Información de resumen**: valores totales de esta columna.                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Tiempo en espera                    | Tiempo total que el agente puso las llamadas en espera. No se aplica a las llamadas que no son de Unified CCX.<br><br>**Información de resumen**: valores totales de esta columna.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
| Tiempo de trabajo                   | Tiempo que el agente pasó en el estado Trabajo después de la llamada. No se aplica a las llamadas que no son de Unified CCX.<br><br>**Información de resumen**: valores totales de esta columna.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         |
| Tipo de llamada                     | Tipo de llamada.<br><br>*   **1 = Conferencia**: llamada de conferencia.<br>*   **2 = Entrantes ACD**: llamada de Unified CCX que maneja el agente.<br>*   **3 = Entrantes No ACD en IPCC**: llamada que no es de Unified CCX que recibe el agente en una extensión de Unified CCX<br>*   **4 = Entrantes No ACD en No IPCC**: llamada que no es de Unified CCX que recibe el agente en una extensión que no es de Unified CCX<br>*   **5 = Salientes en IPCC**: llamada que marca un agente en una extensión de Unified CCX.<br>*   **6 = Salientes en No IPCC**: llamada que marca un agente en una extensión que no es de Unified CCX.<br>*   **7 = Transferencia entrante**: llamada transferida a un agente.<br>*   **8 = Transferencia saliente**: llamada en la que el agente realiza una transferencia saliente. |


La integracion en Database corresponde a:

```
id
agent_login_id
employee_id
agent_ext
start_time
end_time
total_duration
talk_time
hold_time
work_time
phone_number
ani
csq_name
call_skill
call_type
raw_agent_name
created_at
updated_at
```
---

## 3. Estados del agente

**Modelo**

```
agent_state_transitions
```

Es la fuente oficial para todos los estados históricos del agente.

Debe utilizarse para:

- Ready
- Not Ready
- Talking
- Work
- Reserved
- Logout
- Productividad
- Utilización
- Adherencia
- Ocupación
- Shrinkage
- Not Ready por motivo
- Tiempo por estado

Los campos Cisco correspondientes son:


| Campo                          | Descripción                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| ------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Nombre del agente              | Nombre y apellidos del agente.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                             |
| ID de agente                   | ID de conexión del agente.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                 |
| Extensión                      | Última extensión activa de Unified CCX que Unified Communications Manager asignó al agente.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| Tiempo de transición de estado | Fecha y hora en que el agente cambió de estado.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| Estado de agente               | Estado del agente: Conectado, Desconexión, No preparado, Preparado, Reservado, En conversación o Trabajo.                                                                                                                                                                                                                                                                                                                                                                                                                                                                  |
| Motivo                         | El motivo seleccionado por el agente tras pasar al estado Desconexión o No preparado. Muestra el código de motivo si el motivo no está disponible. Un espacio en blanco indica que:<br><br>*   No se ha configurado ningún código de motivo de desconexión. O  <br>    <br>*   El agente no ha podido seleccionar un motivo. O  <br>    <br>*   Códigos de motivo para los demás estados excepto No preparado y Desconexión.  <br>    <br><br>Para ver una lista de códigos de motivo y sus descripciones, consulte la sección "Códigos de motivo predefinidos" siguiente. |
| Duración                       | Tiempo que el agente pasó en un estado.                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    |

La integracion en Database corresponde a:

```
id
agent_login_id
employee_id
transition_time
agent_state
reason_code
duration
created_at
updated_at
```

---

# Reglas arquitectónicas

Durante el análisis valida además las siguientes reglas:

- ConnectModule únicamente importa y normaliza información proveniente de Cisco.
- ConnectModule no calcula KPIs.
- OperationsModule es responsable de todos los cálculos operativos.
- WfmModule nunca debe depender directamente de Cisco para cálculos históricos.
- Los dashboards deben consumir métricas agregadas cuando sea posible.
- Evitar recalcular indicadores costosos sobre millones de registros.
- Identificar oportunidades para tablas agregadas diarias (`agent_daily_metrics`) o vistas materializadas.

---

# Resultado final

Al finalizar genera un resumen ejecutivo con:

## Matriz de fuentes de datos

| Indicador | Tabla oficial | Módulo propietario | Observaciones |
| --------- | ------------- | ------------------ | ------------- |

---

## Hallazgos críticos

Lista únicamente los problemas que afectan:

- consistencia
- rendimiento
- mantenibilidad
- separación de responsabilidades
- escalabilidad

Ordenarlos por prioridad:

- Crítico
- Alto
- Medio
- Bajo

---

## Recomendaciones finales

Presentar un plan de refactorización priorizado indicando:

3. Cambios opcionales.

Cada recomendación debe justificar su impacto sobre la arquitectura y el mantenimiento del sistema.

---

# Informe de Auditoría Funcional y Arquitectónica

## WfmModule

### Responsabilidad

Gestión de la fuerza laboral operativa (horarios, turnos, descansos) y generación del reporte de adherencia y control de operadores (`DailyOperatorReport`).

### Datos utilizados

| Información | Fuente | Tabla | Observaciones |
| ----------- | ------ | ----- | ------------- |
| Tiempo por estado | Cisco | `agent_state_transitions` | Fuente oficial |
| AHT, ACW, Talk | Cisco | `call_records` / `agent_call_performance` | Delegar a Metrics |
| Productividad/Ocupación | WFM | Derivado de estados | Delegar a Metrics |

### Validación

- ✅ **Correcto (Tras Refactor)**: Originalmente, `CalculateDailyOperatorReportAction` y el componente UI `MyDay` recalculaban AHT, Ocupación, y Adherencia manualmente. Se eliminaron estas fórmulas para inyectar `RealtimeMetrics` y `ServiceQualityMetrics`.

### Riesgos

- **cálculo inconsistente (Resuelto)**: División por cero o exclusión de variables (ej. Auxiliares vs ACW) en la ocupación al estar hardcodeado en la interfaz.

### Recomendación

- El `WfmModule` no debería acceder directamente a tablas de telemetría de Cisco (`call_records`) para consolidar reportes. Debería existir un servicio intermedio o vista materializada en `OperationsModule` que provea la información de KPIs precalculados.

---

## ReportingModule

### Responsabilidad

Generación de reportes consolidados y de negocio a partir de información histórica.

### Datos utilizados

| Información | Fuente | Tabla | Observaciones |
| ----------- | ------ | ----- | ------------- |
| Llamadas por Cola | Cisco | `call_records` | Agrupado y precalculado |
| Rendimiento por Agente | Cisco | `agent_call_performance` | Agrupado y precalculado |

### Validación

- ⚠ **Puede mejorarse (Tras Refactor)**: La clase `EloquentReportDataRepository` sufría del "Fallo Clásico" al calcular el Service Level (dividiendo llamadas SLA entre llamadas Handled en vez de Total Offered). Se mitigó extrayendo totales crudos y procesando con `ServiceQualityMetrics`, pero las sentencias SQL siguen muy acopladas a la lógica de negocio.

### Riesgos

- **acoplamiento entre módulos**: El repositorio de reportes interactúa directamente con los modelos nativos de ConnectModule (`CallRecord`).

### Recomendación

- Crear tablas de agregación diarias (`agent_daily_metrics`, `queue_daily_metrics`) en un proceso ETL nocturno.

---

## OperationsModule (Control Tower)

### Responsabilidad

Proveer herramientas de monitoreo en tiempo real, alarmas operativas y cálculos estadísticos intra-día para los supervisores.

### Datos utilizados

| Información | Fuente | Tabla | Observaciones |
| ----------- | ------ | ----- | ------------- |
| Estado Realtime | Cisco/WFM | `agent_state_transitions` | Vía `TelemetryRealtimeRepositoryInterface` |
| SLA / ASA / AHT | Cisco | `call_records` | Intervalos intradía |

### Validación

- ✅ **Correcto (Tras Refactor)**: Se encontraron múltiples componentes Livewire (`HeroStatsWidget`, `QueueTableWidget`, `SlaAsaChartWidget`, etc.) calculando SLA, Ocupación y Productividad de forma errónea y manual. Fueron actualizados para usar los paquetes puros de la carpeta `Metrics`.

### Riesgos

- **cálculo inconsistente (Crítico - Resuelto)**: Se presentaba el Fallo Clásico en dashboards principales.
- **información derivada almacenada en memoria (Resuelto)**: Cálculos iterativos sobre colecciones pesadas.

---

## Resumen Ejecutivo

### Matriz de fuentes de datos (Corregida y Validada)

| Indicador | Tabla oficial | Módulo propietario | Observaciones |
| --------- | ------------- | ------------------ | ------------- |
| **Volumen (Ofrecidas/Atendidas/Aband.)** | `call_records` | `OperationsModule` | Única fuente de verdad de volumen global. |
| **Service Level (SLA)** | `call_records` | `OperationsModule` | Procesado vía `ServiceQualityMetrics::serviceLevel`. |
| **AHT / TMO Global** | `call_records` | `OperationsModule` | Procesado vía `ServiceQualityMetrics::aht`. |
| **AHT / TMO por Agente** | `agent_call_performance` | `OperationsModule` | `call_records` no es preciso para el agente por transferencias. |
| **Adherencia / Ocupación** | `agent_state_transitions` | `WfmModule` / `Operations` | Calculado estrictamente vía `RealtimeMetrics`. |

### Hallazgos críticos

1. **[Crítico] Fallo Clásico del SLA (Resuelto):** El cálculo histórico de SLA dividía sobre `Handled` en vez de `Offered`. Corregido en Reporting y Operations.
2. **[Alto] Fórmulas Matemáticas en la Interfaz (Resuelto):** Los componentes Livewire hacían los cálculos (ej: Ocupación en `HeroStatsWidget`). Delegado 100% a las Actions y Metrics de `Shared`.
3. **[Medio] Ausencia de Agregación Diaria:** Los dashboards recalculan `call_records` en cada carga. Necesario un Job ETL a futuro.

### Recomendaciones finales

1. **Cambios imprescindibles:**
   - Mantener el bloqueo estricto en PRs: Ningún componente Livewire debe contener símbolos de división o multiplicación para KPIs. Todo debe fluir hacia `App\Shared\Support\Metrics`.
2. **Cambios recomendados:**
   - Crear un proceso ETL que pueble las tablas `agent_daily_metrics` y `queue_daily_metrics` a la medianoche, permitiendo que `ReportingModule` consuma de allí en lugar de leer directamente `call_records`.
3. **Cambios opcionales:**
   - Mover la lógica de validación de adherencia (`checkAdherence`) a un Domain Event en caso de que futuros módulos necesiten reaccionar automáticamente cuando un agente sale de su esquema.