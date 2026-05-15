# Manual de Usuario: PersonnelModule

## 📌 Introducción
El **PersonnelModule** es el pilar central de HorariosWFM. Su propósito es gestionar el capital humano de la institución, su estructura jerárquica y la organización de equipos de trabajo. Es el módulo donde vive el perfil de cada empleado y se define a quién reporta.

---

## 👥 Gestión de Empleados
El motor de información de los trabajadores.

### 1. Listado de Empleados
- **Acceso:** Navega a `Personal > Empleados`.
- **Funcionalidades:**
    - Visualizar la ficha técnica de cada empleado (Nombre, Cédula, Cargo, Equipo).
    - Filtros avanzados: búsqueda por texto, cargo, departamento o estado activo.
    - Exportación masiva a formatos CSV o Excel.

### 2. Crear y Editar Empleado
- **Formulario Completo:** Permite ingresar datos personales, de contacto, información laboral (fecha de ingreso, salario, cargo) y ubicación geográfica.
- **Jerarquía:** Al editar un empleado, puedes definir su **Jefe Directo** y el **Equipo** al que pertenece.

### 3. Importación Masiva
- **Acceso:** `Personal > Importar`.
- Permite cargar cientos de empleados simultáneamente mediante un archivo Excel/CSV, ideal para la carga inicial o actualizaciones masivas de nómina.

---

## 🏢 Estructura Organizacional
Define los niveles jerárquicos de la institución.

### 1. Direcciones, Departamentos y Cargos
- **Ubicación:** Navega a `Organización > [Nivel]`.
- Permite crear el árbol organizacional:
    - **Direcciones:** El nivel más alto.
    - **Departamentos:** Pertenecen a una Dirección.
    - **Cargos:** Pertenecen a un Departamento y definen la función laboral.

---

## 🛡️ Gestión de Equipos (Teams)
Organiza a los empleados en unidades operativas de trabajo.

### 1. Listado y Sincronización
- **Acceso:** `Organización > Equipos`.
- **Sincronización con Cisco:** Botón especial para traer automáticamente la estructura de equipos definida en Cisco Finesse.

### 2. Gestión de Miembros
- Desde la vista de un Equipo, puedes:
    - **Añadir Miembros:** Buscar empleados activos y vincularlos.
    - **Remover Miembros:** Desvincular empleados del equipo.
    - **Transferencias Masivas:** Mover múltiples empleados entre equipos de forma rápida.

---

## 📍 Ubicaciones (Geografía)
Gestión de Provincias, Distritos y Corregimientos para estandarizar las direcciones de los empleados.

---

## 📊 Reportes de Personal
- **Resumen de Staffing:** Vista consolidada para ver la distribución de empleados por departamentos y cargos, útil para identificar necesidades de contratación o redistribución de personal.

---

## ⚠️ Recomendaciones Operativas
1. **Unicidad:** Asegúrate de que el número de empleado (cédula o código interno) sea correcto, ya que es el identificador clave para integraciones externas.
2. **Supervisión:** Siempre asigna un supervisor a cada equipo para que los flujos de aprobación de vacaciones e intercambios de turnos funcionen correctamente.
3. **Estado Activo:** Mantén actualizado el estado de los empleados para que los dashboards operativos muestren datos reales de cobertura.
