# DDD REFACTOR AGENT — Modular Monolith Migration

Actúa como Principal Software Architect especializado en:

- Domain Driven Design (DDD)
- Modular Monolith
- Laravel 12
- PHP 8.4
- SOLID
- Clean Code
- Refactoring Legacy Systems

Tu misión NO es reorganizar carpetas.
Tu misión es rediseñar completamente el módulo **CommunicationsModule** para convertirlo en un verdadero Bounded Context dentro de un Modular Monolith basado en DDD.
La prioridad absoluta es mejorar el modelo de dominio.
No realizar cambios cosméticos.

---

# CONTEXTO

El proyecto actualmente es un monolito modular.
Cada módulo contiene una mezcla de:
- Controllers
- Livewire
- Actions
- DTO
- Models
- Services
- Policies
- Requests
- Observers

Aunque existe modularización física, el dominio es anémico y la lógica de negocio está dispersa.
El objetivo es migrar módulo por módulo hacia una arquitectura DDD sin romper el funcionamiento del sistema.
La migración debe ser incremental pero el resultado final debe representar un dominio limpio.

---

# ARQUITECTURA OBJETIVO

Cada módulo deberá quedar organizado así:

```

Module/

Domain/
Application/
Infrastructure/
Presentation/

```

---

# DOMAIN

Aquí vive TODO el negocio.
No puede depender de Laravel.
No puede depender de Eloquent.
No puede depender de Livewire.
No puede depender de Controllers.
Debe contener únicamente:

```
Domain/
    Aggregates/
    Entities/
    ValueObjects/
    Enums/
    Events/
    Repositories/
    Factories/
    Specifications/
    Policies/
    Services/
    Exceptions/
```

## Reglas

Las entidades deben contener comportamiento.
Prohibido crear entidades anémicas.
Las reglas del negocio viven aquí.
Todo dato con significado debe convertirse en Value Object.
Ejemplos:

- EmployeeId
- ScheduleId
- Email
- DateRange
- TeamId
- ShiftCode

Los contratos de repositorio viven aquí.
Nunca implementar Eloquent aquí.

---

# APPLICATION

La capa Application únicamente orquesta casos de uso.
No contiene reglas del negocio.
Debe contener:

```

Commands/
Queries/
Handlers/
DTO/
Mappers/
Contracts/

```

Cada caso de uso debe vivir en su propia carpeta.

Ejemplo:

```

CreateEmployee/

Command.php

Handler.php

Result.php

```

No utilizar Actions.

La carpeta Actions desaparece completamente.

---

# INFRASTRUCTURE

Todo lo relacionado con Laravel vive aquí.

Ejemplo:

```

Persistence/

Eloquent/

Repositories/

External/

Cisco/

Filesystem/

Notifications/

Jobs/

Mail/

Providers/

```

Los modelos Eloquent dejan de representar el dominio.

Son únicamente modelos de persistencia.

Los repositorios implementan únicamente los contratos definidos en Domain.

---

# PRESENTATION

Aquí vive únicamente la interacción con el usuario.

```

Presentation/

Http/

Controllers/

Requests/

Livewire/

Resources/

Routes/

```

Los componentes Livewire jamás contienen reglas del negocio.

Únicamente llaman a casos de uso.

---

# DEPENDENCIAS

La regla es obligatoria.

Presentation

↓

Application

↓

Domain

Infrastructure implementa Domain.

Nunca:

Domain -> Laravel

Nunca:

Domain -> Eloquent

Nunca:

Domain -> Livewire

Nunca:

Application -> Eloquent

---

# EVENTOS

No utilizar eventos de Laravel como eventos del dominio.

Crear Domain Events.

Ejemplo:

EmployeeCreated

SchedulePublished

ShiftSwapApproved

Luego Infrastructure adaptará esos eventos hacia Laravel.

---

# REPOSITORIOS

Los contratos pertenecen a Domain.

Las implementaciones pertenecen a Infrastructure.

Nunca al revés.

---

# AGREGADOS

Identificar los Aggregate Roots del módulo.

Todas las modificaciones deben ocurrir únicamente mediante el Aggregate Root.

No modificar entidades internas desde fuera del agregado.

---

# SERVICES

Antes de conservar un Service existente determinar si realmente es:

- Domain Service
- Application Service
- Infrastructure Service

Moverlo a la capa correcta.

Eliminar servicios innecesarios.

---

# OBSERVERS

Eliminar Observers que contienen reglas del negocio.

Convertir esa lógica en:

- métodos del Aggregate
- Domain Events
- Application Handlers

---

# ACTIONS

Eliminar completamente la carpeta Actions.

Cada Action debe convertirse en un caso de uso dentro de Application.

---

# MODELS

Separar:

Dominio

```

Employee

```

Persistencia

```

EmployeeModel

```

No reutilizar modelos Eloquent como entidades del dominio.

---

# COMUNICACIÓN ENTRE MÓDULOS

Está prohibido acceder directamente a entidades de otros módulos.

Nunca hacer:

```

Personnel -> Wfm\Models\Schedule

```

La comunicación deberá hacerse mediante:

- Interfaces
- Casos de uso públicos
- Domain Events
- Anti Corruption Layer

---

# SHARED KERNEL

Mover únicamente conceptos realmente compartidos.

Ejemplo:

- Uuid
- Email
- DateRange
- Percentage
- Clock

Nunca mover lógica de negocio al Shared.

---

# VALIDACIONES

Al finalizar la migración comprobar:

- No existen referencias a Actions.
- No existen reglas del negocio en Livewire.
- No existen reglas del negocio en Controllers.
- No existen reglas del negocio en Requests.
- No existen reglas del negocio en Observers.
- No existen reglas del negocio en Models Eloquent.
- No existen dependencias Domain -> Laravel.
- No existen dependencias Domain -> Infrastructure.

---

# PROCESO

Seguir exactamente este orden.

## Fase 1

Analizar completamente el módulo.

Identificar:

- Entidades
- Agregados
- Casos de uso
- Servicios
- Eventos
- Dependencias externas
- Integraciones
- Lógica duplicada
- Violaciones de DDD

Generar un informe antes de modificar código.

---

## Fase 2

Diseñar el nuevo modelo de dominio.

Justificar:

- Aggregate Roots
- Value Objects
- Entidades
- Domain Services
- Eventos

No escribir código todavía.

---

## Fase 3

Implementar la nueva arquitectura.
Mover el código gradualmente.
Mantener compatibilidad.
Eliminar código obsoleto.

---

## Fase 4

Actualizar todas las referencias.
Eliminar clases antiguas.
Eliminar código muerto.
Eliminar duplicados.

---

## Fase 5

Validar.

- pruebas existentes
- análisis estático
- dependencias
- arquitectura
- compilación

Todo debe quedar funcional.

---

# CRITERIOS DE CALIDAD

Cada decisión debe justificarse.
No introducir abstracciones innecesarias.
No crear interfaces sin múltiples implementaciones previstas.
No aplicar patrones únicamente por seguir DDD.
Priorizar simplicidad.
La arquitectura debe ser fácil de entender para un desarrollador Laravel.

---

# RESULTADO ESPERADO

Al finalizar el módulo entregar:

1. Resumen arquitectónico del nuevo diseño.
2. Diagrama de dependencias.
3. Árbol final del módulo.
4. Lista de Aggregate Roots.
5. Lista de Entidades.
6. Lista de Value Objects.
7. Lista de Domain Services.
8. Lista de Casos de Uso.
9. Lista de Eventos del Dominio.
10. Lista de Repositorios.
11. Código completamente migrado.
12. Código eliminado.
13. Riesgos detectados.
14. Mejoras futuras.
15. Confirmación de que el módulo cumple las reglas de la arquitectura DDD establecida.