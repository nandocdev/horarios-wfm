# OrganizationModule

## 🎯 Propósito
El `OrganizationModule` define la arquitectura estructural de la compañía. Proporciona los contenedores lógicos (Direcciones, Departamentos) y operativos (Equipos) necesarios para clasificar a los empleados y aplicar reglas de negocio segmentadas (ej: horarios por equipo, KPIs por departamento).

---

## 🚀 Funcionalidades Principales

### 1. Estructura Jerárquica Empresarial
- **Niveles Organizacionales:** Gestión de Direcciones (`Directorate`) y Departamentos (`Department`).
- **Gestión de Cargos:** Definición de posiciones (`Position`) con sus respectivos niveles y descripciones.
- **Relaciones de Dependencia:** Los departamentos pertenecen a direcciones, estableciendo una cadena de mando administrativa.

### 2. Gestión de Equipos Operativos
- **Equipos Dinámicos:** Creación de equipos (`Team`) que pueden cruzar fronteras departamentales si es necesario.
- **Supervisión:** Cada equipo tiene asignado un `supervisor_id` (vinculado a `Employee`), lo que facilita la herencia de permisos en otros módulos (como aprobaciones de turnos).
- **Membresía:** Control de quién pertenece a qué equipo mediante la entidad `TeamMember`.

### 3. Sincronización con Cisco
- **Alineación con el Contact Center:** Acciones para sincronizar la estructura de equipos con los grupos definidos en Cisco UCCX/Finesse.
- **Automatización:** Actualización masiva de la pertenencia a equipos basada en datos externos de telefonía.

---

## 🛠 Estructura Técnica

### Modelos Clave
- `Directorate` / `Department`: Niveles superiores de la estructura.
- `Position`: Definición de cargos.
- `Team`: Unidad operativa base para el WFM.
- `TeamMember`: Registro de asignación de empleados a equipos.

### Actions Destacadas
- `AssignEmployeeToTeamAction`: Gestiona la incorporación de un colaborador a un equipo, manejando validaciones de estado.
- `SyncTeamsWithCiscoAction`: Orquesta la creación/actualización de equipos basada en la configuración del Contact Center.

---

## ⚠️ [RIESGOS]
1. **Desfase con RRHH:** Si la estructura en el WFM no se sincroniza con el sistema oficial de RRHH, puede haber inconsistencias en reportes legales o de nómina.
2. **Huérfanos Organizacionales:** Al eliminar un departamento o equipo, se debe asegurar que los empleados asociados sean reasignados o que el sistema maneje correctamente la nulidad de estas relaciones.
