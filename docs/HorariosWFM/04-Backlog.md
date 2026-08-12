---
tipo: backlog
proyecto: "{{Nombre del Proyecto}}"
estado: activo
fecha: {{date}}
tags:
  - proyecto
  - backlog
---

# 04 — Backlog

## 1. Resumen del Estado
- **Última actualización**: {{date}}
- **Total ítems abiertos**: 
- **En progreso**: 
- **Bloqueados**: 

## 2. Épicas

### Épica 1 — 
**Objetivo**:  
**Requisitos relacionados**: [[02-Requisitos#RF-01]]  
**Estado**: No iniciada / En progreso / Completada

#### Features / Historias
| ID     | Título                          | Prioridad | Estado       | Estimación | Notas / Criterios de aceptación                  |
|--------|---------------------------------|-----------|--------------|------------|--------------------------------------------------|
| BL-001 |                                 | Alta      | Por hacer    |            |                                                  |
| BL-002 |                                 |           |              |            |                                                  |

---

### Épica 2 — 
**Objetivo**:  
**Requisitos relacionados**:  
**Estado**: 

#### Features / Historias
| ID     | Título                          | Prioridad | Estado       | Estimación | Notas / Criterios de aceptación                  |
|--------|---------------------------------|-----------|--------------|------------|--------------------------------------------------|
| BL-010 |                                 |           |              |            |                                                  |

---

## 3. Backlog Priorizado (Vista plana)

| ID     | Título                              | Épica     | Prioridad | Estado        | Estimación | Dependencias |
|--------|-------------------------------------|-----------|-----------|---------------|------------|--------------|
| BL-001 |                                     |           | Alta      | Por hacer     |            |              |
| BL-002 |                                     |           |           |               |            |              |
| BL-003 |                                     |           |           |               |            |              |

**Estados posibles**: `Por hacer` · `En progreso` · `En revisión` · `Bloqueado` · `Hecho`

## 4. Ítems Bloqueados
| ID     | Motivo del bloqueo                  | Desde      | Acción requerida                     | Responsable |
|--------|-------------------------------------|------------|--------------------------------------|-------------|
|        |                                     |            |                                      |             |

## 5. Definición de Hecho (DoD)
Un ítem se considera **Hecho** cuando:
- [ ] Cumple los criterios de aceptación
- [ ] Código revisado (si aplica)
- [ ] Pruebas básicas pasadas
- [ ] Documentación mínima actualizada
- [ ] Desplegado en el entorno correspondiente (si aplica)

## 6. Deuda Técnica
| ID     | Descripción                         | Impacto | Esfuerzo estimado | Prioridad |
|--------|-------------------------------------|---------|-------------------|-----------|
| DT-01  |                                     |         |                   |           |
| DT-02  |                                     |         |                   |           |

## 7. Ideas / Icebox
<!-- Cosas que podrían hacerse pero no están priorizadas todavía -->
- 
- 

## 8. Historial de Cambios Relevantes
| Fecha       | Cambio                              | Autor     |
|-------------|-------------------------------------|-----------|
| {{date}}    | Backlog inicial                     |           |

---

**Relacionado**
- [[02-Requisitos]]
- [[03-Arquitectura]]
- [[05-Decisiones]]
- [[Gestion/Estado]]
- [[00-Index]]