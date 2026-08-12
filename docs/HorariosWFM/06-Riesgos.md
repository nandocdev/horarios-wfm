---
tipo: riesgos
proyecto: "HorariosWFM"
estado: activo
fecha: 2026-08-12
tags:
  - proyecto
  - riesgos
---

# 06 — Riesgos

## 1. Resumen
- **Última revisión**: 2026-08-12
- **Riesgos abiertos**: 4
- **Riesgos críticos (Alto impacto + Alta probabilidad)**: 2
- **Riesgos materializados**: 0

## 2. Matriz de Riesgos

| ID    | Riesgo                               | Categoría | Probabilidad | Impacto | Exposición | Estado        | Responsable | Mitigación principal                    |
| ----- | ------------------------------------ | --------- | ------------ | ------- | ---------- | ------------- | ----------- | --------------------------------------- |
| R-001 | Dead cycle en CiscoSync              | Técnico   | Alta         | Crítico | Crítica    | En mitigación | Equipo Dev  | Health check y backoff exponencial      |
| R-002 | Sin Circuit Breaker en Cisco HTTP    | Técnico   | Alta         | Alto    | Alta       | Abierto       | Equipo Dev  | Implementar CircuitBreakerMiddleware    |
| R-003 | Sobrecarga DB por falta de caché CTI | Técnico   | Alta         | Medio   | Media      | Abierto       | DBA         | Caché Redis TTL 1h para mapeos          |
| R-004 | Fuga de datos sensibles (médicos/$$) | Seguridad | Baja         | Crítico | Media      | Monitoreo     | DevOps      | Cifrado en reposo y políticas estrictas |

**Escala**:
- **Probabilidad**: Baja · Media · Alta
- **Impacto**: Bajo · Medio · Alto · Crítico
- **Exposición**: Calculada (Probabilidad × Impacto) → Baja / Media / Alta / Crítica
- **Estado**: Abierto · En mitigación · Monitoreo · Cerrado · Materializado

## 3. Detalle de Riesgos

### R-001 — Dead cycle: CiscoSync no se re-despacha tras fallo
**Categoría**: Técnico  
**Probabilidad**: Alta  
**Impacto**: Crítico  
**Exposición**: Crítica  
**Estado**: En mitigación  
**Responsable**: Equipo Dev  
**Fecha identificación**: 2026-08-12

**Descripción**  
El Job `CiscoSync` se auto-despacha cada 5s. Si Cisco Finesse falla intermitentemente y se agotan los 3 reintentos de Laravel Queue, el job lanza excepción y muere. El ciclo de sincronización en tiempo real se detiene permanentemente por el resto del día hasta el reinicio a las 5:00 AM del día siguiente.

**Causas raíz posibles**
- Micro-cortes en la VPN o red hacia el servidor Cisco on-premise.
- Reinicios programados o caídas del servicio Cisco Finesse.

**Impacto potencial**
- Sobre el alcance: Pérdida total de visibilidad en vivo (dashboards congelados).
- Sobre la calidad: Cálculo de adherencia incorrecto por falta de datos.

**Estrategia**: Mitigar

**Plan de mitigación**
- [ ] Implementar *Health check* previo en el job antes de iterar agentes.
- [ ] Implementar *Backoff Exponencial* (1s → 2s → 4s → ... → 30s) al re-despachar tras un fallo en lugar de usar reintentos de cola fijos.
- [ ] Notificación automática a Webex si falla 3 ciclos consecutivos.

**Plan de contingencia** (si se materializa)
- Intervención manual: Reiniciar el job `cisco:sync` desde la consola de Artisan. Mostrar un banner de "Stale Data" en el Dashboard.

---

### R-002 — Degradación de workers por falta de Circuit Breaker
**Categoría**: Técnico  
**Probabilidad**: Alta  
**Impacto**: Alto  
**Exposición**: Alta  
**Estado**: Abierto  
**Responsable**: Equipo Dev  

**Descripción**
El cliente HTTP de Cisco (`CiscoFinesseClient`) tiene un timeout fijo de 15s y no posee Circuit Breaker. Si el servidor se degrada y responde lento, los workers de Horizon quedarán bloqueados esperando, agotando los procesos disponibles y frenando otras colas críticas del sistema.

**Plan de mitigación**
- [ ] Configurar el `CircuitBreakerMiddleware` nativo del HTTP Client de Laravel con un umbral del 50% de fallos en 60s.
- [ ] Proveer *Timeouts diferenciados* (15s para estados, 30s-60s para carga masiva de usuarios).

**Plan de contingencia**
- Reiniciar procesos Horizon y aislar las llamadas CTI a un pool de workers distinto (`realtime-sync` dedicado).

---

### R-003 — Sobrecarga de base de datos por mapeo en vivo
**Categoría**: Técnico  
**Probabilidad**: Alta  
**Impacto**: Medio  
**Exposición**: Media  
**Estado**: Abierto  
**Responsable**: DBA  

**Descripción**
En cada ciclo de 5 segundos, el sistema hace un query a la tabla `employees` para cruzar `username` (external_id de Cisco) con `employee_id` local para más de 500 agentes simultáneos. Esto genera miles de queries innecesarios por minuto.

**Plan de mitigación**
- [ ] Mover el diccionario completo de identidades a Redis (`Cache::remember`) con TTL de 1 hora.

**Plan de contingencia**
- Escalar recursos de lectura en PostgreSQL o forzar un refresh del caché en caso de error.

---

## 4. Riesgos Materializados
| ID  | Qué ocurrió | Fecha | Impacto real | Acciones tomadas | Estado actual |
| --- | ----------- | ----- | ------------ | ---------------- | ------------- |
|     |             |       |              |                  |               |

## 5. Riesgos Cerrados / Descartados
| ID  | Riesgo | Motivo de cierre | Fecha cierre |
| --- | ------ | ---------------- | ------------ |
|     |        |                  |              |

## 6. Categorías de Riesgo (referencia)
- **Técnico**: arquitectura, rendimiento, deuda técnica, integraciones, seguridad
- **Comercial**: alcance mal definido, cambios de cliente, precios, cobros
- **Operativo**: infraestructura, despliegue, dependencias externas
- **Equipo**: disponibilidad, conocimiento, rotación
- **Externo**: regulaciones, terceros, mercado

## 7. Criterios de Revisión
- Revisar este archivo al menos cada 1-2 semanas o cuando cambie el contexto del proyecto.
- Cualquier riesgo de exposición **Alta** o **Crítica** debe tener responsable y plan de mitigación activo.

---

**Relacionado**
- [[01-Vision-y-Alcance]]
- [[03-Arquitectura]]
- [[05-Decisiones]]
- [[00-Index]]