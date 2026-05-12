# EmployeesModule

## 🎯 Propósito
El `EmployeesModule` es el pilar central de datos de la plataforma. Su función es gestionar el ciclo de vida completo del colaborador, desde su información personal y contractual hasta su posición dentro de la jerarquía organizacional. Sirve como la fuente de verdad única para todos los demás módulos.

---

## 🚀 Funcionalidades Principales

### 1. Gestión del Perfil del Empleado
- **Información 360°:** Centraliza datos personales, de contacto, demográficos, médicos (enfermedades, discapacidades) y familiares (dependientes).
- **Relación con Usuario:** Mantiene un vínculo estrecho con el modelo `User` de `CoreModule`, permitiendo que cada empleado tenga acceso al sistema con permisos específicos.
- **Historial de Posiciones:** Registra las diferentes posiciones que un empleado ha ocupado a lo largo de su carrera en la empresa.

### 2. Jerarquía y Estructura Operativa
- **Líneas de Reporte:** Implementa una estructura de árbol (Adjacency List) donde cada empleado puede tener un supervisor.
- **Consultas de Alto Rendimiento:** Utiliza **Common Table Expressions (CTE) recursivas** para navegar por la jerarquía (ej: obtener todos los subordinados directos e indirectos) de forma eficiente en PostgreSQL.
- **Asignación de Equipos:** Permite la gestión masiva de integrantes de equipos y la definición de roles de supervisión.

### 3. Operaciones de Datos Masivos
- **Importación Inteligente:** Procesamiento de archivos CSV/Excel en segundo plano mediante "chunks". Incluye validación previa y reporte de errores por fila.
- **Trazabilidad de Importación:** Cada carga masiva se registra en un "Batch" para auditar quién subió qué datos y cuáles fueron rechazados.
- **Exportación Flexible:** Generación de reportes dinámicos de la planilla con filtros por departamento, equipo y estado.

### 4. Servicio de Resolución de Identidad
- **Desacoplamiento vía Repositorios:** Implementa `EmployeeLookupRepositoryInterface` (ubicado en `Shared`) para que módulos externos como `ConnectModule` puedan traducir IDs externos (Cisco Login) a IDs internos de empleado sin acoplamiento directo.

---

## 🛠 Estructura Técnica

### Modelos Clave
- `Employee`: Entidad principal con lógica de jerarquía y relaciones fundacionales.
- `EmploymentStatus`: Define si un empleado está activo, de baja, en vacaciones, etc.
- `EmployeePosition`: Pivote para el historial de cargos.
- `EmployeeImportBatch`: Registro y control de procesos de carga masiva.

### Actions Destacadas
- `CreateEmployeeAction` / `UpdateEmployeeAction`: Gestionan la persistencia y la sincronización con el modelo `User`.
- `ProcessEmployeeImportChunkAction`: Lógica de validación y creación durante la importación masiva.
- `AssignEmployeesToTeamAction`: Orquesta el movimiento de personal entre equipos.

### Repositorios
- `EloquentEmployeeLookupRepository`: Implementación concreta para búsquedas rápidas y almacenamiento en caché de identidades.

---

## ⚠️ [RIESGOS]
1. **Privacidad de Datos (PII):** Almacena información sensible (salarios, datos médicos, direcciones). Es imperativo que las Policies (`EmployeePolicy`) sean restrictivas y sigan el principio de mínimo privilegio.
2. **Circularidad en la Jerarquía:** La lógica de supervisor/subordinado debe prevenir que un empleado se reporte a sí mismo o cree bucles infinitos.
3. **Consistencia con el Usuario:** Al eliminar o desactivar un empleado, se debe decidir qué ocurre con su cuenta de `User` para evitar accesos huérfanos o errores de integridad.
4. **Impacto de Cambios Masivos:** Actualizaciones masivas de salarios o departamentos disparan eventos y observers que podrían sobrecargar el sistema si no se manejan en colas.

---

## 📋 Ejemplo de Uso

### Obtener todos los subordinados de un gerente
```php
$manager = Employee::find(1);
$allSubordinateIds = $manager->getAllSubordinateIds(); // Usa CTE Recursiva
```

### Buscar un empleado por su login de Cisco (vía Shared Contract)
```php
use App\Shared\Contracts\Employees\EmployeeLookupRepositoryInterface;

$repository = app(EmployeeLookupRepositoryInterface::class);
$employeeId = $repository->findByLoginId('fcastillo');
```
