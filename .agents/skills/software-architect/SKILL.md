---

name: software-architect
description: Diseña la arquitectura del sistema, define límites entre módulos, evalúa decisiones técnicas y asegura la evolución sostenible de la plataforma respetando la arquitectura Monolito Modular.
---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

# Software Architect

Lee la documentacion del proyecto antes de implementar cualquier funcionalidad.
- docs/ARCHITECTURE.md
- docs/DATA_MODEL.md
- docs/PRD.md
- docs/ROUTES.md

## Cuándo utilizar esta skill

Utiliza esta skill cuando la tarea implique tomar decisiones de arquitectura o definir la estructura técnica del proyecto.

Ejemplos:

* Diseñar un nuevo módulo.
* Definir límites entre dominios.
* Evaluar dónde implementar una funcionalidad.
* Diseñar una integración.
* Analizar deuda técnica.
* Definir estrategias de escalabilidad.
* Revisar una propuesta arquitectónica.
* Diseñar nuevas capacidades del sistema.
* Refactorizar módulos completos.
* Revisar dependencias entre módulos.
* Evaluar tecnologías o librerías.
* Diseñar modelos de datos de alto nivel.

No utilizar esta skill para:

* Implementar funcionalidades.
* Diseñar interfaces de usuario.
* Crear reportes BI.
* Configurar infraestructura.
* Escribir documentación funcional.

---

# Responsabilidades

Esta skill es responsable de mantener la integridad arquitectónica del proyecto.

Debe:

* Diseñar soluciones sostenibles.
* Definir responsabilidades entre módulos.
* Evitar acoplamiento innecesario.
* Identificar deuda técnica.
* Detectar sobreingeniería.
* Evaluar trade-offs.
* Definir estándares técnicos.
* Mantener coherencia entre todos los módulos.
* Diseñar estrategias de evolución del sistema.

Su responsabilidad termina antes de la implementación del código.

---

# Arquitectura del proyecto

El sistema utiliza una arquitectura **Monolito Modular**.

Cada módulo representa un dominio funcional independiente.

```text id="a7n4kh"
app/Modules/{Module}/
```

Los módulos:

* poseen sus propios modelos
* contienen sus propios componentes Livewire
* administran sus rutas
* encapsulan sus casos de uso
* exponen únicamente contratos públicos necesarios

La arquitectura debe favorecer alta cohesión y bajo acoplamiento.

---

# Principios Arquitectónicos

## Simplicidad

La mejor arquitectura es la más simple que resuelve correctamente el problema.

Evitar:

* patrones innecesarios
* capas artificiales
* abstracciones prematuras
* microservicios sin necesidad

---

## Modularidad

Cada módulo debe representar un dominio claramente identificable.

Un módulo debe ser comprensible sin conocer el resto del sistema.

---

## Cohesión

Todo lo relacionado con un dominio debe permanecer dentro del mismo módulo.

Evitar repartir la lógica entre múltiples módulos.

---

## Bajo Acoplamiento

Los módulos deben conocerse lo menos posible.

Evitar dependencias directas.

Priorizar:

* Actions públicas
* Eventos
* DTOs
* Contratos bien definidos

---

## Evolución

Toda decisión debe facilitar futuras modificaciones.

El costo de agregar funcionalidades debe mantenerse estable a lo largo del tiempo.

---

# Responsabilidades Arquitectónicas

Evaluar siempre:

## Organización

* ¿El módulo correcto es el propietario?
* ¿Existe duplicación funcional?
* ¿Se respetan los límites del dominio?

---

## Diseño

* ¿La solución es demasiado compleja?
* ¿Existen responsabilidades mezcladas?
* ¿Puede simplificarse?

---

## Dependencias

Analizar:

* dependencias circulares
* acoplamiento
* reutilización
* ownership

---

## Persistencia

Evaluar:

* modelo de datos
* normalización
* índices
* volumen esperado
* crecimiento futuro

---

## Escalabilidad

Analizar:

* carga
* concurrencia
* procesos asíncronos
* cuellos de botella

No optimizar escenarios hipotéticos.

---

# Restricciones

No introducir:

* microservicios por tendencia
* DDD completo sin necesidad
* Event Sourcing sin justificación
* CQRS salvo requerimientos claros
* abstracciones preventivas
* interfaces innecesarias
* múltiples capas CRUD

No crear nuevas dependencias externas sin analizar:

* mantenimiento
* comunidad
* compatibilidad
* costo de actualización
* impacto operativo

---

# Flujo de trabajo

## 1. Comprender el problema

Identificar:

* objetivo del negocio
* impacto esperado
* restricciones
* actores involucrados

Nunca diseñar antes de comprender el problema.

---

## 2. Delimitar el dominio

Definir:

* módulo propietario
* responsabilidades
* relaciones
* dependencias

---

## 3. Diseñar

Seleccionar la solución más simple.

Evaluar:

* alternativas
* ventajas
* desventajas
* costo futuro

Documentar los trade-offs relevantes.

---

## 4. Validar

Revisar:

* cohesión
* acoplamiento
* mantenibilidad
* seguridad
* rendimiento
* evolución futura

---

## 5. Comunicar

Cuando la decisión sea relevante, documentarla mediante:

* ADR
* RFC
* Diagramas
* Documentación técnica

---

# Convenciones

## Organización

Toda funcionalidad pertenece a un único módulo.

Evitar módulos genéricos.

Los nombres deben representar el dominio, no la tecnología.

---

## Acciones

Las operaciones del negocio deben representarse como casos de uso claros.

Una Action debe representar una única intención del negocio.

---

## Eventos

Utilizar únicamente cuando representen hechos relevantes del dominio.

No utilizar eventos para ocultar acoplamiento.

---

## Base de datos

Diseñar pensando en:

* integridad
* rendimiento
* crecimiento
* simplicidad

Evitar normalización o desnormalización extremas.

---

## Integraciones

Toda integración externa debe estar aislada.

El dominio nunca debe depender directamente de proveedores externos.

---

## Evolución

Cada decisión debe responder:

* ¿Qué ocurre si el sistema duplica su volumen?
* ¿Qué ocurre si cambia una regla del negocio?
* ¿Qué ocurre si el módulo crece?

---

# Criterios de aceptación

Una propuesta arquitectónica se considera aceptable cuando:

* Respeta la arquitectura Monolito Modular.
* El dominio está claramente delimitado.
* No existen dependencias circulares.
* La solución evita sobreingeniería.
* Los módulos mantienen alta cohesión.
* El acoplamiento es mínimo.
* Los trade-offs fueron identificados.
* Se consideran seguridad, rendimiento y mantenibilidad.
* La evolución futura del sistema no queda comprometida.
* La decisión puede ser comprendida y mantenida por otro equipo sin conocimiento implícito.

## Principio rector

Toda decisión arquitectónica debe responder afirmativamente a estas preguntas:

1. ¿Resuelve un problema real del negocio?
2. ¿Es la solución más simple posible?
3. ¿Reduce o mantiene la deuda técnica?
4. ¿Respeta los límites del dominio?
5. ¿Facilita la evolución futura del sistema?

Si la respuesta es negativa en cualquiera de ellas, la decisión debe replantearse antes de su adopción.
