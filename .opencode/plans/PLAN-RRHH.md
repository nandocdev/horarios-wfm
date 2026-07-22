# Plan de Evolución — Módulo RRHH (PersonnelModule)

> **Documento de diseño de producto**
> Sistema WFM — Call Center de la Caja de Seguro Social de Panamá
> **Autor:** Product Owner
> **Versión:** 1.0 — Julio 2026
> **Propósito:** Evolucionar el PersonnelModule existente en un módulo de Recursos Humanos completo, aprovechando los recursos ya implementados.

---

## 1. Decisión Arquitectónica

**Evolucionar PersonnelModule**, no crear módulo nuevo. El módulo actual ya tiene:

- 9 modelos con migraciones
- 17 Actions implementadas
- 12 componentes Livewire
- 3 Policies con scoping jerárquico
- 10 DTOs
- 2 Repositorios (Shared Contracts)
- 11 tests

No se justifica refactorizar a un módulo nuevo. Se agregan las capacidades faltantes respetando la estructura existente.

---

## 2. Mapa de Tablas Esqueleto

| Tabla                  | Estado actual            | Propósito en el nuevo diseño                                                                                                                                                      |
| ---------------------- | ------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `funcionarios`         | Solo `id` + `timestamps` | Sincronización con sistemas externos de RRHH de la CSS (SIACAP/planilla). Tabla de respaldo para importación automática de datos maestros desde fuente oficial.                   |
| `cargos`               | Solo `id` + `timestamps` | Catálogo de cargos de la CSS con códigos oficiales, grupo salarial y tipo de servidor público. Complementa a `positions` (OrganizationModule) que son cargos operativos internos. |
| `relaciones_laborales` | Solo `id` + `timestamps` | Catálogo de tipos de relación laboral/contratación (Fijo, Temporal, Suplente, Contrato Administrativo, etc.).                                                                     |

---

## 3. Modelo de Datos — Nuevas Tablas

### 3.1 employee_documents

Documentos digitales asociados al empleado.

| Columna       | Tipo           | Notas                                                               |
| ------------- | -------------- | ------------------------------------------------------------------- |
| id            | ULID           | PK                                                                  |
| employee_id   | FK → employees |                                                                     |
| document_type | VARCHAR(50)    | `id_card`, `resume`, `contract`, `certificate`, `academic`, `other` |
| document_name | VARCHAR(255)   | Nombre descriptivo                                                  |
| file_path     | VARCHAR(500)   | Ruta del archivo (Spatie Media Library)                             |
| issued_by     | VARCHAR(255)   | Institución que lo emitió                                           |
| issued_at     | DATE           | Fecha de emisión                                                    |
| expires_at    | DATE           | Fecha de vencimiento (nullable)                                     |
| is_verified   | BOOLEAN        | DEFAULT false                                                       |
| notes         | TEXT           |                                                                     |
| timestamps    |                |                                                                     |
| softDeletes   |                |                                                                     |

### 3.2 contract_types (hereda de relaciones_laborales)

| Columna     | Tipo            | Notas              |
| ----------- | --------------- | ------------------ |
| id          | BIGINT UNSIGNED | PK                 |
| name        | VARCHAR(100)    |                    |
| code        | VARCHAR(20)     | Código oficial CSS |
| description | TEXT            |                    |
| is_active   | BOOLEAN         | DEFAULT true       |

### 3.3 employee_contracts

| Columna          | Tipo                    | Notas                         |
| ---------------- | ----------------------- | ----------------------------- |
| id               | BIGINT UNSIGNED         | PK                            |
| employee_id      | FK → employees          |                               |
| contract_type_id | FK → contract_types     |                               |
| start_date       | DATE                    |                               |
| end_date         | DATE                    | Nullable (indefinido)         |
| position_id      | FK → positions          | Cargo al momento del contrato |
| salary           | DECIMAL(12,2)           |                               |
| document_id      | FK → employee_documents | Nullable                      |
| is_active        | BOOLEAN                 | DEFAULT true                  |
| notes            | TEXT                    |                               |
| timestamps       |                         |                               |

### 3.4 salary_history

| Columna         | Tipo            | Notas                                                    |
| --------------- | --------------- | -------------------------------------------------------- |
| id              | BIGINT UNSIGNED | PK                                                       |
| employee_id     | FK → employees  |                                                          |
| previous_salary | DECIMAL(12,2)   | Nullable                                                 |
| new_salary      | DECIMAL(12,2)   |                                                          |
| change_reason   | VARCHAR(100)    | `hiring`, `merit`, `promotion`, `adjustment`, `demotion` |
| change_date     | DATE            |                                                          |
| authorized_by   | FK → users      |                                                          |
| notes           | TEXT            |                                                          |
| timestamps      |                 |                                                          |

### 3.5 employee_benefits

| Columna      | Tipo            | Notas                                                                 |
| ------------ | --------------- | --------------------------------------------------------------------- |
| id           | BIGINT UNSIGNED | PK                                                                    |
| employee_id  | FK → employees  |                                                                       |
| benefit_type | VARCHAR(50)     | `health_insurance`, `meal_voucher`, `transport`, `education`, `other` |
| benefit_name | VARCHAR(255)    |                                                                       |
| provider     | VARCHAR(255)    |                                                                       |
| start_date   | DATE            |                                                                       |
| end_date     | DATE            | Nullable                                                              |
| is_active    | BOOLEAN         | DEFAULT true                                                          |
| timestamps   |                 |                                                                       |

### 3.6 employee_career_movements (reemplaza employee_positions)

| Columna                | Tipo             | Notas                                                           |
| ---------------------- | ---------------- | --------------------------------------------------------------- |
| id                     | BIGINT UNSIGNED  | PK                                                              |
| employee_id            | FK → employees   |                                                                 |
| movement_type          | VARCHAR(50)      | `promotion`, `transfer`, `demotion`, `rotation`, `reassignment` |
| previous_position_id   | FK → positions   | Nullable                                                        |
| new_position_id        | FK → positions   |                                                                 |
| previous_department_id | FK → departments | Nullable                                                        |
| new_department_id      | FK → departments |                                                                 |
| previous_salary        | DECIMAL(12,2)    | Nullable                                                        |
| new_salary             | DECIMAL(12,2)    | Nullable                                                        |
| movement_date          | DATE             |                                                                 |
| authorized_by          | FK → users       |                                                                 |
| resolution_number      | VARCHAR(100)     | Nullable                                                        |
| notes                  | TEXT             |                                                                 |
| timestamps             |                  |                                                                 |

### 3.7 funcionarios (completada)

| Columna                  | Tipo            | Notas                                        |
| ------------------------ | --------------- | -------------------------------------------- |
| id                       | BIGINT UNSIGNED | PK                                           |
| employee_id              | FK → employees  | Nullable                                     |
| external_id              | VARCHAR(50)     | ID sistema externo                           |
| external_employee_number | VARCHAR(20)     | Núm. funcionario CSS                         |
| full_name                | VARCHAR(255)    |                                              |
| position_code            | VARCHAR(50)     |                                              |
| position_name            | VARCHAR(255)    |                                              |
| department_code          | VARCHAR(50)     |                                              |
| department_name          | VARCHAR(255)    |                                              |
| salary_grade             | VARCHAR(20)     | Grupo salarial                               |
| hire_date                | DATE            |                                              |
| status                   | VARCHAR(50)     | `active`, `inactive`, `retired`, `suspended` |
| raw_data                 | JSONB           |                                              |
| last_synced_at           | TIMESTAMP TZ    |                                              |
| timestamps               |                 |                                              |

### 3.8 cargos_css (hereda de cargos)

| Columna      | Tipo            | Notas                          |
| ------------ | --------------- | ------------------------------ |
| id           | BIGINT UNSIGNED | PK                             |
| code         | VARCHAR(50)     | Código oficial CSS             |
| name         | VARCHAR(255)    |                                |
| salary_grade | VARCHAR(20)     |                                |
| min_salary   | DECIMAL(12,2)   |                                |
| max_salary   | DECIMAL(12,2)   |                                |
| service_type | VARCHAR(50)     | `career`, `trust`, `temporary` |
| is_active    | BOOLEAN         |                                |
| timestamps   |                 |                                |

---

## 4. Columnas Nuevas en employees

| Columna                   | Tipo         | Notas                           |
| ------------------------- | ------------ | ------------------------------- |
| `contract_type`           | VARCHAR(50)  | NULLABLE — se migra de metadata |
| `work_schedule`           | VARCHAR(100) | NULLABLE                        |
| `education_level`         | VARCHAR(50)  | NULLABLE                        |
| `profession`              | VARCHAR(255) | NULLABLE                        |
| `emergency_contact_name`  | VARCHAR(255) | NULLABLE                        |
| `emergency_contact_phone` | VARCHAR(20)  | NULLABLE                        |
| `bank_name`               | VARCHAR(100) | NULLABLE                        |
| `bank_account_type`       | VARCHAR(20)  | NULLABLE                        |
| `bank_account_number`     | VARCHAR(50)  | NULLABLE — cifrado              |

---

## 5. Nuevas Actions (17)

### Gestión Documental
- `UploadEmployeeDocumentAction`
- `DeleteEmployeeDocumentAction`
- `VerifyEmployeeDocumentAction`

### Contratos
- `CreateEmployeeContractAction`
- `TerminateEmployeeContractAction`
- `RenewEmployeeContractAction`

### Salarial
- `RecordSalaryChangeAction`
- `BulkSalaryAdjustmentAction`

### Carrera
- `PromoteEmployeeAction`
- `TransferEmployeeAction`
- `RecordCareerMovementAction`

### Beneficios
- `AssignEmployeeBenefitAction`
- `RemoveEmployeeBenefitAction`

### Sincronización Externa
- `SyncFuncionariosFromExternalAction`
- `MatchFuncionariosToEmployeesAction`

### Procesos
- `OnboardEmployeeAction`
- `OffboardEmployeeAction`

### Modificaciones a Actions Existentes
- `CreateEmployeeAction` → crear contrato inicial + salario inicial automáticamente
- `UpdateEmployeeAction` → detectar cambios de salario/cargo y registrar histórico

---

## 6. Nuevos Componentes Livewire (7)

| Componente        | Tag Livewire                   | Propósito                          |
| ----------------- | ------------------------------ | ---------------------------------- |
| EmployeeDocuments | `employees.employee-documents` | Documentos del empleado con upload |
| EmployeeContracts | `employees.employee-contracts` | Línea de tiempo de contratos       |
| SalaryHistory     | `employees.salary-history`     | Historial salarial + gráfico       |
| CareerTimeline    | `employees.career-timeline`    | Línea de tiempo de carrera         |
| EmployeeBenefits  | `employees.employee-benefits`  | Beneficios activos                 |
| HrDashboard       | `personnel.hr-dashboard`       | Dashboard ejecutivo RRHH           |
| HrReports         | `personnel.hr-reports`         | Reportes de RRHH                   |

---

## 7. Políticas Nuevas (4)

- `ContractPolicy`
- `SalaryHistoryPolicy`
- `BenefitPolicy`
- `CareerMovementPolicy`

---

## 8. Rol Nuevo

| Rol    | Código | Nivel | Acceso                                                 |
| ------ | ------ | ----- | ------------------------------------------------------ |
| `rrhh` | RH     | 4.5   | Full RRHH (documentos, contratos, salarios, dashboard) |

---

## 9. Fases

| Fase                       | Semana | Entregables                                                       |
| -------------------------- | ------ | ----------------------------------------------------------------- |
| **1 — Fundación de Datos** | 1      | 9 migraciones, 8 modelos, seeder, actualizar Employee             |
| **2 — Lógica de Negocio**  | 2      | 17 Actions nuevas, 2 modificaciones, 8 DTOs                       |
| **3 — UI**                 | 3      | 7 Livewire, 7 blades, pestañas en show, rutas                     |
| **4 — Seguridad**          | 3-4    | 4 Policies nuevas, EmployeePolicy actualizada, rol rrhh, permisos |
| **5 — Tests**              | 4      | 7 tests nuevos, regresión en acciones existentes                  |

---

## 10. Resumen

| Tipo                 | Cantidad                |
| -------------------- | ----------------------- |
| Migraciones nuevas   | 9                       |
| Modelos nuevos       | 7                       |
| Actions nuevas       | 17                      |
| Actions modificadas  | 2                       |
| DTOs nuevos          | ~8                      |
| Livewire nuevos      | 7                       |
| Policies nuevas      | 4                       |
| Rol nuevo            | 1                       |
| Tests nuevos         | 7                       |
| **Estimación total** | **~2,500-3,000 líneas** |
