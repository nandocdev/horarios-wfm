---
name: laravel-developer
description: Implementa funcionalidades Laravel respetando la arquitectura Monolito Modular del proyecto, siguiendo las convenciones establecidas y priorizando simplicidad, mantenibilidad y código listo para producción.
---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

# Laravel Developer

## Contexto

Lee la documentacion del proyecto antes de implementar cualquier funcionalidad.
- docs/ARCHITECTURE.md
- docs/DATA_MODEL.md
- docs/PRD.md
- docs/ROUTES.md

## Cuándo utilizar esta skill

Utiliza esta skill cuando la tarea implique implementar o modificar funcionalidades dentro del proyecto Laravel.

Ejemplos:

* Crear un nuevo módulo.
* Implementar un caso de uso.
* Crear o modificar componentes Livewire.
* Crear Actions.
* Crear Models.
* Crear DTOs.
* Crear Policies.
* Crear Jobs.
* Crear Events y Listeners.
* Crear migraciones.
* Refactorizar funcionalidades existentes.
* Optimizar consultas Eloquent.
* Integrar nuevos módulos.
* Corregir errores funcionales.
* Implementar pruebas automatizadas.

No utilizar esta skill para:

* Diseño UX/UI.
* Arquitectura empresarial.
* Documentación funcional.
* DevOps.
* Análisis de datos o BI.

---

# Responsabilidades

Esta skill es responsable de implementar soluciones utilizando Laravel respetando la arquitectura del proyecto.

Debe:

* Implementar funcionalidades completas.
* Mantener la coherencia arquitectónica.
* Ubicar correctamente cada clase dentro del módulo correspondiente.
* Mantener bajo acoplamiento entre módulos.
* Aplicar las convenciones del proyecto.
* Generar código listo para producción.
* Detectar y señalar deuda técnica relevante.
* Priorizar simplicidad frente a patrones innecesarios.

---

# Arquitectura del proyecto

El proyecto utiliza una arquitectura **Monolito Modular**.

Toda implementación pertenece a un módulo.

```text
app/Modules/{Module}/
├── Actions/
├── Console/Commands/
├── Database/Migrations/
├── DTOs/
├── Emails/
├── Enums/
├── Events/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Jobs/
├── Listeners/
├── Livewire/
│   └── Forms/
├── Mail/
├── Models/
├── Notifications/
├── Observers/
├── Policies/
├── Providers/
├── Repositories/
├── Resources/
│   └── Views/
├── Routes/
└── Services/
```

Nunca crear código fuera del módulo responsable salvo infraestructura compartida definida por el proyecto.

---

# Responsabilidades por carpeta

## Actions

Representan casos de uso.

Cada Action:

* resuelve un único caso de uso
* expone un único método `execute()`
* orquesta la operación
* controla transacciones cuando sean necesarias

La lógica transaccional pertenece aquí.

---

## Models

Responsables únicamente de:

* persistencia
* relaciones
* scopes
* casts
* accessors
* mutators

No implementar lógica de negocio compleja.

---

## Livewire

Responsables de:

* interacción con el usuario
* estado de la pantalla
* validación visual
* navegación

Toda lógica del negocio debe delegarse a Actions.

---

## DTOs

Utilizar objetos tipados para transportar información entre capas.

Evitar arrays asociativos cuando el contrato sea conocido.

---

## Policies

Toda autorización debe implementarse mediante Policies o Gates.

Nunca validar permisos directamente dentro de los componentes Livewire.

---

## Jobs

Reservados para procesos asíncronos:

* correos
* exportaciones
* integraciones
* tareas pesadas

No mover lógica crítica únicamente a una cola si afecta la experiencia inmediata del usuario.

---

## Events

Representan hechos del dominio.

No utilizar eventos para reemplazar llamadas directas sin un beneficio claro de desacoplamiento.

---

## Services

Utilizar únicamente cuando exista lógica reutilizable entre múltiples Actions.

No crear Services CRUD.

---

## Repositories

No crear Repositories por defecto.

Solo justifican su existencia cuando:

* existen consultas complejas reutilizables
* múltiples orígenes de datos
* necesidades específicas de optimización

---

# Restricciones

No introducir:

* capas innecesarias
* patrones Enterprise sin justificación
* interfaces sin múltiples implementaciones reales
* abstracciones "por si acaso"
* Helpers globales
* lógica duplicada

No colocar reglas de negocio en:

* Blade
* Controllers
* Livewire
* Models
* Requests

No realizar consultas complejas dentro de las vistas.

No acceder directamente a clases internas de otro módulo.

Toda comunicación entre módulos debe realizarse mediante interfaces públicas claramente definidas (Actions, eventos o contratos compartidos cuando corresponda).

---

# Flujo de trabajo

Antes de implementar cualquier funcionalidad:

## 1. Analizar

Identificar:

* objetivo funcional
* módulo responsable
* impacto sobre otros módulos
* dependencias

---

## 2. Diseñar

Determinar:

* Action necesaria
* Models involucrados
* DTOs
* Policies
* Eventos
* Jobs

Crear únicamente los elementos necesarios.

---

## 3. Implementar

Desarrollar siguiendo la arquitectura oficial.

Mantener responsabilidades claramente separadas.

---

## 4. Validar

Revisar:

* consistencia
* seguridad
* rendimiento
* autorización
* validaciones
* consultas

Buscar especialmente:

* consultas N+1
* duplicación
* acoplamiento
* transacciones innecesarias

---

## 5. Documentar

Actualizar la documentación cuando la implementación modifique:

* arquitectura
* contratos
* módulos
* reglas de negocio
* APIs

---

# Convenciones

## Código

Priorizar:

* legibilidad
* simplicidad
* nombres explícitos
* métodos pequeños
* clases cohesivas

---

## Base de datos

Asumir PostgreSQL.

Aprovechar características nativas cuando simplifiquen la solución:

* índices
* JSONB
* CTE
* Window Functions
* Materialized Views

No resolver desde PHP lo que la base de datos puede resolver eficientemente.

---

## Consultas

Revisar siempre:

* eager loading
* índices
* cardinalidad
* costo de ejecución

Evitar N+1 Queries.

---

## Validación

Toda entrada debe validarse mediante:

* Form Requests
* Livewire Forms
* DTOs

Nunca confiar en datos provenientes del cliente.

---

## Seguridad

Toda funcionalidad debe considerar:

* autorización
* autenticación
* Mass Assignment
* CSRF
* XSS
* SQL Injection

---

## Testing

Priorizar:

1. Feature Tests
2. Integration Tests
3. Unit Tests

Las pruebas deben validar comportamiento observable, no detalles internos de implementación.

---

# Criterios de aceptación

Una implementación se considera finalizada cuando:

* Respeta la arquitectura Monolito Modular.
* Cada clase está ubicada en la carpeta correspondiente.
* No introduce sobreingeniería.
* Mantiene responsabilidades claramente separadas.
* La lógica de negocio reside en Actions.
* Las consultas son eficientes y evitan problemas de rendimiento.
* La autorización y validación están correctamente implementadas.
* No genera dependencias innecesarias entre módulos.
* El código es consistente con las convenciones del proyecto.
* La funcionalidad está preparada para producción.
* La documentación y las pruebas fueron actualizadas cuando corresponde.

## Principio rector

Ante cualquier decisión técnica, priorizar siempre:

1. Simplicidad.
2. Cohesión.
3. Bajo acoplamiento.
4. Legibilidad.
5. Mantenibilidad.

Si una solución requiere explicar demasiado su diseño, probablemente sea más compleja de lo necesario.
