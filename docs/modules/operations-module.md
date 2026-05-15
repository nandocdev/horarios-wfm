# Manual de Usuario: OperationsModule

## 📌 Introducción
El **OperationsModule** es el centro de control táctico de HorariosWFM. Está diseñado para que supervisores y gerentes de operaciones puedan monitorear el desempeño del Call Center en tiempo real, analizar métricas históricas y asegurar que el nivel de servicio se mantenga óptimo.

---

## 📊 Dashboard Principal
La vista panorámica del estado de la operación.
- **KPIs Hero:** Muestra indicadores críticos como Cobertura, Adherencia, Ocupación, Nivel de Servicio, Ausentismo y Shrinkage.
- **Deltas de Variación:** Compara el rendimiento actual con el período anterior para detectar tendencias positivas o negativas rápidamente.
- **Incidentes Recientes:** Listado de alertas operativas automáticas.

---

## ⚡ Monitoreo en Tiempo Real
- **Acceso:** `Operaciones > Monitoreo Realtime`.
- **Funcionalidades:**
    - **Muro de Agentes:** Visualiza a todos los agentes conectados, su estado actual (Ready, Talking, Not Ready) y el tiempo que llevan en dicho estado.
    - **Alertas de Adherencia:** Los agentes que no están cumpliendo con su actividad programada se resaltan visualmente para intervención inmediata.
    - **Filtrado por Equipo:** Permite enfocar la vista en un grupo específico de trabajo.

---

## 📈 Scorecards de Rendimiento
Análisis detallado de métricas de eficiencia.

### 1. Desempeño Individual
- **Acceso:** `Operaciones > Scorecards`.
- Permite evaluar a cada agente bajo métricas clave (AHT, ACW, Productividad).
- Útil para sesiones de feedback y planes de mejora.

### 2. Resumen de Desempeño de Equipo
- Vista consolidada de las métricas de un equipo completo. Permite comparar equipos entre sí o evaluar el cumplimiento de metas grupales.

---

## ⏳ Línea de Tiempo del Agente (Timeline)
- **Concepto:** Una representación visual cronológica de los estados por los que ha pasado un agente durante su jornada.
- **Uso:** Ideal para auditar discrepancias entre lo programado y lo real, o para entender el flujo de trabajo de un agente en un día específico.

---

## ⚠️ Guía de Gestión Operativa
1. **Atención a las Alertas:** El Dashboard está configurado para mostrar en rojo indicadores que caen por debajo del umbral institucional.
2. **Sincronización:** Si notas que un agente falta en la lista de monitoreo, verifica primero su sincronización en el módulo de personal (PersonnelModule).
3. **Optimización:** Usa los filtros de fecha en los scorecards para realizar análisis semanales o mensuales de evolución del servicio.
