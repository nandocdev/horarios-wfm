---
tipo: riesgos
proyecto: "{{Nombre del Proyecto}}"
estado: activo
fecha: {{date}}
tags:
  - proyecto
  - riesgos
---

# 06 — Riesgos

## 1. Resumen
- **Última revisión**: {{date}}
- **Riesgos abiertos**: 
- **Riesgos críticos (Alto impacto + Alta probabilidad)**: 
- **Riesgos materializados**: 

## 2. Matriz de Riesgos

| ID     | Riesgo                              | Categoría      | Probabilidad | Impacto | Exposición | Estado      | Responsable | Mitigación principal                  |
|--------|-------------------------------------|----------------|--------------|---------|------------|-------------|-------------|---------------------------------------|
| R-001  |                                     | Técnico        | Alta         | Alto    | Crítica    | Abierto     |             |                                       |
| R-002  |                                     | Comercial      | Media        | Alto    | Alta       | Abierto     |             |                                       |
| R-003  |                                     | Operativo      | Baja         | Medio   | Baja       | Monitoreo   |             |                                       |

**Escala**:
- **Probabilidad**: Baja · Media · Alta
- **Impacto**: Bajo · Medio · Alto · Crítico
- **Exposición**: Calculada (Probabilidad × Impacto) → Baja / Media / Alta / Crítica
- **Estado**: Abierto · En mitigación · Monitoreo · Cerrado · Materializado

## 3. Detalle de Riesgos

### R-001 — 
**Categoría**: Técnico / Comercial / Operativo / Externo / Equipo  
**Probabilidad**: Alta  
**Impacto**: Alto  
**Exposición**: Crítica  
**Estado**: Abierto  
**Responsable**:  
**Fecha identificación**: {{date}}

**Descripción**  
<!-- Qué puede pasar exactamente -->

**Causas raíz posibles**
- 
- 

**Impacto potencial**
- Sobre el alcance:
- Sobre el tiempo:
- Sobre el costo:
- Sobre la calidad:

**Estrategia**: Evitar · Mitigar · Transferir · Aceptar

**Plan de mitigación**
- [ ] 
- [ ] 

**Plan de contingencia** (si se materializa)
- 

**Indicadores de alerta temprana**
- 

**Seguimiento**

| Fecha       | Nota                                      | Autor     |
|-------------|-------------------------------------------|-----------|
| {{date}}    | Riesgo identificado                       |           |

---

### R-002 — 
**Categoría**:  
**Probabilidad**:  
**Impacto**:  
**Exposición**:  
**Estado**:  
**Responsable**:  

**Descripción**

**Plan de mitigación**
- [ ] 

**Plan de contingencia**

---

## 4. Riesgos Materializados
| ID     | Qué ocurrió                             | Fecha        | Impacto real          | Acciones tomadas                     | Estado actual |
|--------|-----------------------------------------|--------------|-----------------------|--------------------------------------|---------------|
|        |                                         |              |                       |                                      |               |

## 5. Riesgos Cerrados / Descartados
| ID     | Riesgo                                  | Motivo de cierre                      | Fecha cierre |
|--------|-----------------------------------------|---------------------------------------|--------------|
|        |                                         |                                       |              |

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
- [[Gestion/Estado]]
- [[00-Index]]