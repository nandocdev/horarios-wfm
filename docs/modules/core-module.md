# Manual de Usuario: CoreModule

## 📌 Introducción
El **CoreModule** es el corazón administrativo de HorariosWFM. Su propósito es gestionar los cimientos técnicos del sistema, incluyendo la identidad de los usuarios, el control de acceso basado en roles y el mantenimiento global de la plataforma.

---

## 👤 Gestión de Usuarios
Este apartado permite administrar quién tiene acceso a la plataforma.

### 1. Listado de Usuarios
- **Acceso:** Navega a `Administración > Usuarios`.
- **Funcionalidades:**
    - Visualizar todos los usuarios registrados.
    - Filtrar usuarios por nombre, correo electrónico o estado (Activo/Inactivo).
    - Ver los roles asignados a cada usuario de un vistazo.

### 2. Crear un Nuevo Usuario
- Haz clic en el botón **"Nuevo Usuario"**.
- Completa la información básica: nombre completo, correo institucional y contraseña inicial.
- **Importante:** Al crear un usuario, puedes forzar el cambio de contraseña en su primer inicio de sesión para mayor seguridad.

### 3. Editar Usuarios
- Desde el listado, haz clic en el icono de edición (lápiz) de un usuario.
- Puedes actualizar su información de perfil, cambiar su estado (activar/desactivar acceso) y gestionar sus roles.

---

## 🔐 Roles y Permisos
El sistema utiliza un modelo de control de acceso granular para asegurar que cada empleado vea solo lo que necesita.

### 1. Gestión de Roles
- **Acceso:** Navega a `Administración > Roles`.
- **Concepto:** Un rol es un conjunto de permisos (ej: `Administrador`, `Supervisor`, `Operador`).
- **Uso:** En esta vista puedes visualizar los roles existentes y la cantidad de permisos asignados a cada uno.

---

## 🛠️ Mantenimiento del Sistema
Herramientas para la salud técnica de la plataforma.

### 1. Modo Mantenimiento
- **Acceso:** `Administración > Sistema > Mantenimiento`.
- Permite poner la aplicación en modo de "Bajo mantenimiento", informando a los usuarios que se están realizando actualizaciones mientras los administradores pueden seguir trabajando.

---

## 🔔 Notificaciones Globales
El CoreModule incluye el sistema de base para las notificaciones que aparecen en la campana de la barra superior.
- **Leídas/No leídas:** Permite a cualquier usuario gestionar sus alertas de sistema, noticias o flujos de trabajo pendientes.

---

## ⚠️ Seguridad y Buenas Prácticas
1. **Contraseñas Fuertes:** El sistema exige parámetros de complejidad (mayúsculas, números y símbolos).
2. **Estado Activo:** Si un empleado deja la institución, se recomienda **desactivar** su usuario en lugar de eliminarlo para mantener la integridad de los registros históricos y auditorías.
3. **Mínimo Privilegio:** Asigna solo los roles estrictamente necesarios para la función laboral del usuario.
