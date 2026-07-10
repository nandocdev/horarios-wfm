# Catálogo funcional de reportes WFM

## Objetivo

Definir un catálogo funcional de reportes orientado a operaciones de contacto, con foco en WFM, supervisión operativa y toma de decisiones. El diseño debe permitir analizar el desempeño desde cuatro niveles de agregación:

- Individual
- Equipo
- Jefatura
- Global

Y debe soportar filtros transversales comunes para poder comparar y drill down en cualquier reporte.

---

## 1. Principios de diseño

### 1.1 Estructura transversal

Todos los reportes deben responder a la misma lógica de navegación:

- Dimensión de análisis: persona, equipo, jefatura, global
- Dominio operativo: asistencia, adherencia, productividad, telefonía, calidad y capacidad
- Periodo: fecha, semana, mes, año
- Segmentación: cola, equipo, coordinador, jefatura, turno, empleado

### 1.2 Requisitos funcionales comunes

Todos los reportes deben permitir:

- Visualización por periodo seleccionado
- Comparación con periodo anterior
- Drill-down hacia niveles inferiores
- Exportación a CSV/Excel/PDF
- Filtros persistentes por usuario
- Alertas o indicadores de desviación

### 1.3 Requisitos de experiencia

Los reportes deben ser:

- Rápidos de consultar
- Claros en su lectura operativa
- Asequibles para supervisores y dirección
- Compatibles con consumo en escritorio y tablet

---

## 2. Niveles de agregación y filtros

### 2.1 Niveles de agregación

1. Individual
   - Empleado o agente
   - Útil para seguimiento de desempeño, cumplimiento y riesgo

2. Equipo
   - Grupo de agentes o unidad operativa
   - Útil para gestión diaria y balance de carga

3. Jefatura
   - Agrupación funcional o regional
   - Útil para decisiones de supervisión y capacidad

4. Global
   - Operación completa o negocio completo
   - Útil para resumen ejecutivo y dirección

### 2.2 Filtros obligatorios

Todos los reportes deberán soportar filtros por:

- Fecha
- Semana
- Mes
- Cola
- Equipo
- Coordinador
- Jefatura
- Turno
- Empleado

### 2.3 Comportamiento esperado

- Un filtro de mayor granularidad debe poder desagregarse hacia niveles superiores.
- Los filtros deben aplicarse de forma consistente a todos los reportes del catálogo.
- La selección de filtros debe modificar la vista sin recargar toda la aplicación.

---

## 3. Catálogo funcional de reportes

## 3.1 Reporte de asistencia y ausentismo

### Propósito

Medir la disponibilidad real del personal y los impactos de ausencias sobre la operación.

### KPIs

- % Ausentismo
- Ausencias justificadas
- Ausencias injustificadas
- Incapacidades
- Vacaciones
- Permisos
- Tardanzas
- Salidas anticipadas
- Minutos perdidos
- Días afectados

### Drill-down

- Empleado
- Equipo
- Motivo
- Tipo de incidencia
- Tendencia diaria y semanal

### Visualizaciones recomendadas

- Tendencia diaria y semanal
- Distribución por motivo
- Comparativo por equipo
- Heatmap de ausencia por día

---

## 3.2 Reporte de desconexión

### Propósito

Identificar tiempos en los que el agente no estuvo conectado cuando debía estarlo.

### KPIs

- Tiempo desconectado
- % desconexión
- Eventos de desconexión
- Duración promedio
- Mayor desconexión
- Horario donde ocurre

### Clasificación recomendada

- Inicio de turno
- Almuerzo
- Break
- Fin anticipado
- Desconexión injustificada

### Valor operativo

Este reporte es crítico para detectar pérdida de capacidad y problemas de cumplimiento.

---

## 3.3 Reporte de uso y abuso de auxiliares

### Propósito

Analizar el uso de auxiliares para controlar cumplimiento de política y riesgo de sobreuso.

### KPIs por auxiliar

- Tiempo total
- Cantidad de usos
- Duración promedio
- Máximo
- Mínimo

### Rankings

- Auxiliares más utilizados
- Auxiliares menos utilizados
- Auxiliares fuera de política

### Abuso

- Permanencia excesiva
- Frecuencia excesiva
- Uso fuera del horario permitido

### Valor operativo

Este reporte es muy importante en WFM porque impacta directamente en productividad, capacidad y costos operativos.

---

## 3.4 Reporte de rendimiento y productividad

### Propósito

Medir el desempeño real del agente y del equipo en relación con la capacidad disponible.

### KPIs

- Tiempo conectado
- Tiempo productivo
- Tiempo improductivo
- Ocupación
- Utilización
- Productividad %

### Estados recomendados

- Talking
- ACW
- Ready
- Not Ready
- Hold
- Auxiliares

### Comparativos

- Meta
- Resultado
- Variación
- Ranking

### Valor operativo

Permite distinguir entre eficiencia real, capacidad no utilizada y comportamiento operativo.

---

## 3.5 Reporte de atención de llamadas

### Propósito

Medir el desempeño de la operación de telefonía a nivel general y por cola.

### Métricas generales

- Recibidas
- Atendidas
- Abandonadas
- Transferidas
- Perdidas
- Contestadas en SLA

### AHT

- Promedio
- Máximo
- Mínimo
- Mediana
- Moda
- Desviación estándar
- Percentil 90
- Percentil 95

### Valor operativo

Sirve como vista global del volumen y la calidad del servicio telefónico.

---

## 3.6 Reporte por cola

### Propósito

Analizar el comportamiento operativo de cada cola de forma independiente.

### Volumen

- Recibidas
  - Total
  - Rellamadores
  - Clientes únicos
- Atendidas
  - Total
  - Media
  - Máximo
  - Mínimo
  - Mediana
  - Moda
  - Desviación estándar
  - Percentil 90
  - Percentil 95
- Abandonadas
  - Total
  - %
  - Media
  - Máximo
  - Mínimo
  - Mediana
  - Moda
  - Desviación estándar

### Tiempo de espera

- Promedio
- Máximo
- Mínimo
- ASA

### Nivel de servicio

- % atendido dentro del SLA
- Variación respecto a la meta

### Valor operativo

Es esencial para equilibrar carga, comprender picos y decidir ajustes de capacidad.

---

## 3.7 Reporte de adherencia

### Propósito

Comparar el comportamiento real del agente frente al horario y reglas de trabajo programadas.

### KPIs

- Adherencia %
- Minutos adheridos
- Minutos no adheridos
- Eventos de incumplimiento
- Inicio tardío
- Almuerzo excedido
- Break excedido
- Fin anticipado

### Comparación recomendada

- Programado vs Real

### Valor operativo

Es uno de los reportes más críticos de WFM porque impacta directamente en cobertura, servicio y capacidad.

---

## 3.8 Reporte de cobertura

### Propósito

Medir si la capacidad operativa disponible cubre la demanda esperada.

### Métricas por intervalo

- Personal requerido
- Personal programado
- Personal conectado
- Personal disponible
- Déficit
- Excedente

### Visualización recomendada

- Heatmap por intervalo de 15 o 30 minutos
- Comparativo por día y por turno

### Valor operativo

Es clave para identificar brechas de capacidad y anticipar riesgos de servicio.

---

## 3.9 Reporte de ocupación

### Propósito

Entender cómo se utiliza el tiempo disponible del agente o del equipo.

### KPIs

- Tiempo disponible
- Tiempo ocupado
- Ocupación %
- Capacidad utilizada

### Valor operativo

Permite evaluar si el esfuerzo está bien distribuido o si hay exceso o falta de carga.

---

## 3.10 Reporte de cumplimiento de horario

### Propósito

Comparar el horario planificado contra el horario realmente ejecutado.

### Comparación

- Horario programado vs horario real

### Métricas

- Entrada
- Salida
- Tardanzas
- Horas trabajadas
- Horas planificadas
- Diferencia

### Valor operativo

Es una vista muy útil para supervisión, control de cumplimiento y gestión de capacidad.

---

## 3.11 Reporte de tendencias

### Propósito

Analizar la evolución de cualquier KPI en el tiempo.

### Periodicidad

- Diario
- Semanal
- Mensual
- Anual

### Comparación recomendada

- Periodo actual vs periodo anterior
- Variación porcentual
- Tendencia acumulada

### Valor operativo

Es esencial para detectar cambios sostenidos, estacionalidades y desviaciones de desempeño.

---

## 3.12 Reporte de ranking

### Propósito

Comparar agentes o equipos según desempeño operativo.

### Ranking por operador

- Mayor productividad
- Mejor adherencia
- Menor ausentismo
- Menor uso de auxiliares
- Mayor volumen atendido
- Mejor AHT

### Ranking por equipos

- Ranking entre equipos
- Comparativo de cobertura y servicio

### Valor operativo

Facilita la gestión de desempeño y la identificación de buenas prácticas o desviaciones.

---

## 3.13 Resumen ejecutivo

### Propósito

Brindar una vista rápida para jefaturas y dirección.

### Widgets recomendados

- Personal programado
- Personal conectado
- Cobertura
- Ausentismo
- Adherencia
- Productividad
- Nivel de servicio
- ASA
- AHT
- Abandono
- Volumen
- Tendencia de la semana
- Top 10 mejores operadores
- Top 10 incidencias
- Top 10 equipos

### Uso esperado

Este resumen debe estar orientado a decisiones de gestión rápida, priorización y seguimiento operativo.

---

## 4. Recomendaciones de ingeniería

### 4.1 Separar reportes por propósito

Conviene separar dos capas de reportes porque responden a preguntas distintas:

#### Rendimiento del operador

Métricas individuales orientadas a agentes:

- AHT
- Productividad
- Adherencia
- Auxiliares
- Volumen atendido

#### Rendimiento de la operación

Métricas agregadas orientadas a la gestión operativa:

- SLA
- ASA
- Cobertura
- Abandono
- Ocupación
- Capacidad
- Distribución de carga por colas

### 4.2 Arquitectura recomendada: Data Mart analítico

Para evitar consultas costosas sobre millones de registros del ACD, los reportes deben construirse sobre un Data Mart con tablas de hechos agregadas por intervalos de 15 o 30 minutos.

### 4.3 Tablas recomendadas

#### Hechos

- FactCallCenter
- FactAgentActivity
- FactScheduleCompliance
- FactAbsence
- FactCapacityCoverage
- FactQueuePerformance

#### Dimensiones

- DimTime
- DimEmployee
- DimTeam
- DimHierarchy
- DimQueue
- DimShift
- DimReason

### 4.4 Beneficios del modelo analítico

- Menor costo de consulta
- Mejor rendimiento en reportes históricos y operativos
- Consistencia entre reportes individuales, por equipo, por jefatura y global
- Mayor escalabilidad ante volúmenes altos de datos

### 4.5 Recomendación de agregación

- Agregar por intervalos de 15 o 30 minutos para reportes operativos en tiempo real o casi en tiempo real
- Mantener agregaciones diarias, semanales y mensuales para reportes históricos
- Precalcular métricas de alto consumo como SLA, ASA, adherencia y cobertura

### 4.6 Criterios de implementación

1. Definir un modelo de métricas estándar reutilizable por todos los reportes.
2. Centralizar la lógica de cálculo en un layer analítico común.
3. Garantizar consistencia de definiciones entre negocio, operaciones y tecnología.
4. Diseñar una capa de drill-down que permita ir desde global a individual sin cambiar el modelo.
5. Incluir una estrategia de caché y de actualización incremental para mantener tiempos de respuesta bajos.

---

## 5. Propuesta de prioridad de implementación

### Fase 1: Fundamentos operativos

- Asistencia y ausentismo
- Adherencia
- Cobertura
- Cumplimiento de horario

### Fase 2: Operación de contacto

- Atención de llamadas
- Reporte por cola
- Ocupación
- Productividad

### Fase 3: Gestión ejecutiva

- Tendencias
- Ranking
- Resumen ejecutivo
- Uso y abuso de auxiliares

---

## 6. Conclusión

Este catálogo permite cubrir de forma estructurada y escalable las necesidades de WFM, desde la supervisión diaria hasta la gestión ejecutiva. La clave está en diseñar los reportes sobre un modelo analítico común, con agregaciones por intervalo y dimensiones reutilizables, para que la operación, la supervisión y la dirección compartan la misma fuente de verdad.
