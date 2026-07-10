# Arquitectura de Software — Sistema de Evaluación de Llamadas

## 1. Stack Tecnológico

| Capa | Tecnología | Versión | Justificación |
|---|---|---|---|
| Frontend | Bootstrap 5 + DataTables | 5.1.3 / 1.13 | Ya incluido en el proyecto; evita dependencias nuevas |
| Backend | PHP sin framework | 8.x | Migración progresiva desde código legacy; se introducirá un router liviano + estructura MVC |
| Base de datos | MySQL / MariaDB | 8.x / 10.x | Ya en uso |
| Assets | jQuery, JS vanilla | — | Ya incluido |

## 2. Arquitectura Propuesta: MVC por Capas

Se reemplazará el modelo actual (PHP + HTML inline) por una estructura separada en capas, manteniendo compatibilidad hacia atrás durante la migración.

```
📁 calidad/
├── index.php                  # Front controller (router único)
├── config/
│   ├── database.php           # Conexión PDO (reemplaza conexion.php)
│   └── app.php                # Constantes globales
├── public/                    # Document root (opcional con virtual host)
│   ├── css/
│   ├── js/
│   ├── img/
│   └── datatable/
├── src/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── EvaluationController.php
│   │   ├── CriteriaController.php
│   │   ├── QueueController.php
│   │   ├── FeedbackController.php
│   │   └── ReportController.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Evaluation.php
│   │   ├── EvaluationScore.php
│   │   ├── Criteria.php
│   │   ├── CriteriaVersion.php
│   │   ├── Queue.php
│   │   ├── QueueCriteria.php
│   │   ├── RedFlag.php
│   │   └── Feedback.php
│   ├── Services/
│   │   ├── AuthService.php
│   │   ├── EvaluationService.php   # Lógica de cálculo de puntaje
│   │   ├── CriteriaVersioningService.php
│   │   └── ReportService.php
│   ├── Repositories/
│   │   ├── UserRepository.php
│   │   ├── EvaluationRepository.php
│   │   └── CriteriaRepository.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   └── RoleMiddleware.php
│   ├── Validators/
│   │   └── EvaluationValidator.php
│   └── Lib/
│       ├── Router.php
│       ├── Database.php            # Singleton PDO
│       ├── View.php                # Render de templates
│       └── Session.php
├── views/
│   ├── layouts/
│   │   └── main.php
│   ├── auth/
│   │   ├── login.php
│   │   └── change_password.php
│   ├── evaluation/
│   │   ├── form.php
│   │   └── detail.php
│   ├── criteria/
│   │   ├── list.php
│   │   └── form.php
│   ├── report/
│   │   └── history.php
│   └── partials/
│       ├── navbar.php
│       └── feedback_modal.php
├── migrations/
│   └── 001_initial_schema.sql
├── scripts/
│   └── migrate_legacy_data.php     # ETL de tablas viejas a nuevas
└── docs/
    ├── PRD.md
    ├── ARCHITECTURE.md
    ├── MIGRATION.md
    └── SECURITY.md
```

## 3. Flujo de Datos

### 3.1 Solicitud HTTP (Front Controller)

```
Navegador
  │  GET /evaluacion/nueva?queue=CM-Tr
  ▼
index.php (Front Controller)
  │
  ├── Router.php → resuelve ruta
  │     └── EvaluationController::nueva($queue)
  │
  ├── AuthMiddleware.php → verifica sesión
  ├── RoleMiddleware.php → verifica rol 'evaluador'
  │
  ├── EvaluationService.php → obtiene criterios activos
  │     └── CriteriaRepository::getActiveByQueue($queueId)
  │
  ├── View.php → renderiza views/evaluation/form.php
  │     └── pasa $criterios, $evaluadores, $coordinadores, etc.
  │
  └── Respuesta HTML al navegador
```

### 3.2 Guardado de Evaluación

```
POST /evaluacion/guardar
  │
  ├── EvaluationController::guardar()
  │     ├── EvaluationValidator::validate($_POST)
  │     ├── EvaluationService::calcularPuntaje($scores)
  │     │     └── suma puntajes_obtenidos
  │     ├── Evaluation::create($data)
  │     ├── EvaluationScore::bulkInsert($evaluationId, $scoresConVersiones)
  │     └── EvaluationRedFlag::bulkInsert($evaluationId, $redFlags)
  │
  └── Redirect → /evaluacion/confirmacion
```

### 3.3 Versionamiento de Criterios

```
Admin modifica criterio "Saludo inicial"
  │
  ├── CriteriaController::actualizar($id, $nuevosDatos)
  │     ├── CriteriaVersioningService::crearNuevaVersion($criteriaId, $datos)
  │     │     ├── Lee versión vigente actual (valid_to IS NULL)
  │     │     ├── Cierra su vigencia: SET valid_to = CURDATE()
  │     │     ├── Crea nueva versión con version + 1, valid_from = CURDATE()
  │     │     └── Retorna ID de la nueva criteria_versions
  │     │
  │     └── queue_criteria se actualiza opcionalmente para apuntar a la nueva versión
  │
  └── Redirect → /criterios
```

## 4. Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────┐
│                    Navegador                             │
└────────────────────┬────────────────────────────────────┘
                     │ HTTP
                     ▼
┌─────────────────────────────────────────────────────────┐
│              Front Controller (index.php)                │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │  Router  │  │   Auth   │  │   Role   │  │   View  │ │
│  │          │  │Middleware│  │Middleware│  │  Engine  │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                  Controllers                             │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────────┐ │
│  │   Auth   │ │Evaluation│ │ Criteria │ │   Report   │ │
│  └──────────┘ └──────────┘ └──────────┘ └────────────┘ │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                   Services                               │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐ │
│  │ AuthService  │ │EvalService   │ │CriteriaVersioning│ │
│  └──────────────┘ └──────────────┘ └──────────────────┘ │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                 Repositories / Models                    │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────────┐ │
│  │   User   │ │Evaluation│ │ Criteria │ │   Queue    │ │
│  └──────────┘ └──────────┘ └──────────┘ └────────────┘ │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│                   Database (MySQL)                       │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────────┐ │
│  │  users   │ │evaluations│ │criteria  │ │  queues    │ │
│  │  + roles │ │+ scores  │ │versions  │ │ + queue_cr │ │
│  └──────────┘ └──────────┘ └──────────┘ └────────────┘ │
└─────────────────────────────────────────────────────────┘
```

## 5. Estrategia de Migración (Legacy → Nueva Arquitectura)

Se migrará por módulos, manteniendo ambos sistemas funcionando en paralelo:

| Fase | Módulo | Archivos legacy | Nuevos archivos | Depende de |
|---|---|---|---|---|
| 1 | Infraestructura | `conexion.php` | `config/database.php`, `src/Lib/Database.php`, `src/Lib/Router.php` | — |
| 2 | Autenticación | `Principal.php`, `recibe.php`, `chgpsw.php` | `AuthController`, `AuthService`, `views/auth/` | Fase 1 |
| 3 | Catálogos | `Lista.php` | `UserController`, `QueueController`, Models, Repositories | Fase 1 |
| 4 | Criterios | (tablas `*_eval`) | `CriteriaController`, `CriteriaVersioningService`, `views/criteria/` | Fase 3 |
| 5 | Evaluación | `*_24-25.php`, `valpost.php` | `EvaluationController`, `EvaluationService`, `views/evaluation/` | Fase 4 |
| 6 | Feedback | `feedback.php`, `valfeed.php` | `FeedbackController` | Fase 5 |
| 7 | Reportes | `vistagen.php`, `tables.js` | `ReportController`, server-side DataTables | Fase 5 |

## 6. Decisiones Técnicas

| # | Decisión | Alternativa | Razón |
|---|---|---|---|
| 1 | PHP sin framework vs Laravel/Symfony | Laravel | El equipo actual no tiene experiencia con frameworks; una sobrecarga cognitiva alta pondría en riesgo la adopción. Se introduce un router liviano propio. |
| 2 | PDO vs MySQLi | MySQLi (legacy) | PDO permite prepared statements portables y es más seguro. Se migrará gradualmente. |
| 3 | Vistas PHP (sin template engine) | Twig / Blade | Simplifica la migración desde el código inline actual. Si se necesita, se puede agregar después. |
| 4 | Migraciones SQL manuales | Phinx / Laravel Migrations | Sin framework, las migraciones se versionan como archivos .sql con numbering. |
| 5 | DataTables server-side | Carga client-side completa | Con >30K registros, server-side es necesario para rendimiento. |

## 7. Pendientes Técnicos (Deuda Abordada)

- [ ] Reemplazar `echo "<html>..."` por templates PHP separados
- [ ] Migrar queries literales a prepared statements
- [ ] Extraer Lista.php a tablas users + user_roles
- [ ] Unificar conexiones en un singleton PDO
- [ ] Agregar manejo de errores centralizado (try/catch + error page)
- [ ] Implementar CSRF tokens en formularios
- [ ] Agregar logging centralizado (archivo rotativo)
