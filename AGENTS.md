<!-- CODEGRAPH_START -->
## CodeGraph

In repositories indexed by CodeGraph (a `.codegraph/` directory exists at the repo root), reach for it BEFORE grep/find or reading files when you need to understand or locate code:

- **MCP tool** (when available): `codegraph_explore` answers most code questions in one call — the relevant symbols' verbatim source plus the call paths between them, including dynamic-dispatch hops grep can't follow. Name a file or symbol in the query to read its current line-numbered source. If it's listed but deferred, load it by name via tool search.
- **Shell** (always works): `codegraph explore "<symbol names or question>"` prints the same output.

If there is no `.codegraph/` directory, skip CodeGraph entirely — indexing is the user's decision.
<!-- CODEGRAPH_END -->

## Arquitectura y Modularización (Fase de Migración DDD)

El proyecto se encuentra en una transición hacia un **Modular Monolith orientado a Bounded Contexts**, aplicando **DDD Estratégico** y **DDD Táctico Selectivo**.

**REGLAS CRÍTICAS DE MIGRACIÓN (Strangler Fig Pattern):**

1. **NO ELIMINAR CÓDIGO EXISTENTE:** El código legado reside en `app/Modules/`. No debe ser borrado ni refactorizado destructivamente.
2. **NUEVO CÓDIGO EN `app/Src/`:** Toda la nueva arquitectura y los módulos refactorizados/segmentados deben construirse dentro del directorio `app/Src/`, operando en paralelo con `app/Modules/`.
3. **TRANSPARENCIA Y ENTREGA CONTINUA:** El usuario final no debe notar el cambio. La idea es ir migrando casos de uso (Endpoints, Livewire components) para que apunten progresivamente a los nuevos servicios en `app/Src/`, permitiendo desplegar a producción continuamente de forma segura.

### Nueva Estructura Obligatoria en `app/Src/`

Para cada nuevo módulo o Bounded Context migrado a `app/Src/`, se debe respetar estrictamente la siguiente estructura de 4 capas (Onion/Clean Architecture):

```text
app/Src/{NombreContexto}/
├── Domain/                   # Lógica pura (Sin dependencias del framework)
│   ├── Aggregates/
│   ├── Entities/             # Clases PHP puras, NO modelos Eloquent
│   ├── ValueObjects/
│   ├── Events/
│   ├── Repositories/         # Interfaces (Contratos)
│   ├── Services/             # Servicios de dominio puros
│   └── Specifications/
│
├── Application/              # Casos de Uso (Orquestación)
│   ├── Commands/
│   ├── Queries/
│   ├── Handlers/             # Reemplazan a los antiguos Actions
│   ├── DTOs/
│   └── Mappers/              # Transforman Entities a/desde persistencia
│
├── Infrastructure/           # Detalles técnicos e integraciones
│   ├── Persistence/          # Modelos Eloquent y migraciones aquí
│   ├── Integrations/         # Llamadas API (ej. Cisco)
│   ├── Notifications/
│   ├── Jobs/
│   └── Providers/
│
└── Presentation/             # Interfaces de entrada (UI/API)
    ├── Http/                 # Controladores web/API
    ├── Livewire/             # Componentes reactivos
    ├── Resources/            # Vistas Blade
    └── Routes/
```

### Segmentación de Bounded Contexts y Mapeo del Legado

Al migrar a `app/Src/`, aplicaremos la siguiente segmentación estratégica, consolidando y dividiendo los 14 módulos legacy en verdaderos Contextos de Negocio y un contexto transversal (`Platform`):

1. **`Identity`**: Autenticación, Usuarios, Roles y Permisos (Migra desde `CoreModule`).
2. **`Organization`**: Organigrama, Equipos, Departamentos, Puestos (Migra desde `PersonnelModule`).
3. **`HumanResources`**: Legajos médicos, dependientes, estado legal (Migra desde `PersonnelModule`).
4. **`TimeAndAttendance`**: Asistencia, ausencias, tardanzas e incidencias (Migra desde `OperationsModule`).
5. **`Analytics`**: Dashboards, Scorecards, TMO (Migra desde `OperationsModule`).
6. **`Wfm`**: Planificación y mallas horarias (Migra desde `WfmModule`).
7. **`Quality`**: QA, Evaluaciones y Disputas (Migra desde `QualityModule`).
8. **`Workflows`**: Máquina de estados de aprobaciones multinivel (Migra desde `WorkflowsModule`).
9. **`Helpdesk`**: Soporte interno IT/Facilities (Migra desde `HelpdeskModule`).
10. **`Connect`**: Capa Anticorrupción de Telefonía CTI (Migra desde `ConnectModule`).
11. **`Knowledge`**: Base de conocimientos y manuales (Consolida `KnowledgeModule` y `DocumentationModule`).
12. **`Platform`**: Utilidades transversales e Infraestructura global. Este contexto no es de negocio, sino técnico, y absorberá la lógica de:
    - Auditoría y Logs (`AuditModule`, `SupportModule`).
    - Almacenamiento S3/Local (`FilesystemModule`).
    - Notificaciones Globales SMS/Mail (`CommunicationsModule`).

**Mantra de Desarrollo en la Migración:**
Al crear nueva funcionalidad, constrúyela en `app/Src/` usando CQRS y Dominio puro. Si debes tocar lógica existente en `app/Modules/`, refactoriza ese caso de uso específico hacia `app/Src/` y haz que el controlador/Livewire antiguo apunte al nuevo `Handler` en `Application`, respetando el patrón estrangulador.
