# CoreModule

## 🎯 Propósito
El `CoreModule` es la infraestructura base sobre la cual se construye toda la plataforma. Gestiona la identidad, la seguridad, la autorización y las configuraciones globales del sistema, permitiendo que el resto de los módulos operen en un entorno protegido y estandarizado.

---

## 🚀 Funcionalidades Principales

### 1. Gestión de Identidad (IAM)
- **Cuentas de Usuario:** Administra el modelo `User`, que es el punto de entrada para la autenticación. Cada usuario está vinculado a un perfil en `EmployeesModule`.
- **Autenticación (Fortify):** Implementa la lógica de inicio de sesión, recuperación de contraseñas y seguridad de la cuenta utilizando Laravel Fortify.
- **Ciclo de Vida:** Actions para la creación, actualización y suspensión de acceso de usuarios.

### 2. Autorización y RBAC
- **Roles y Permisos:** Utiliza el sistema de Spatie para definir roles (ej: Admin, Supervisor, Agente) y permisos granulares (ej: `schedule.edit`, `audit.view`).
- **Sincronización:** Acciones específicas para actualizar la matriz de permisos de cada rol de forma dinámica.

### 3. Configuraciones Globales
- **App Settings:** Proporciona un sistema de almacenamiento clave-valor para parámetros del sistema que no deben estar hardcodeados (ej: límites de tiempo, URLs de APIs externas).

### 4. Infraestructura de UI
- **Mensajería (Toasts):** Componente global para mostrar notificaciones de éxito, error o advertencia al usuario.
- **Mantenimiento:** Herramientas administrativas para la limpieza de caché, optimización de base de datos y estados del sistema.

---

## 🛠 Estructura Técnica

### Modelos Clave
- `User`: Entidad central de autenticación.
- `Role` / `Permission`: Definiciones de seguridad (Spatie).
- `AppSetting`: Parámetros de configuración persistentes.

### Actions Destacadas
- `CreateUserAction`: Crea la cuenta de acceso y asigna roles iniciales.
- `SyncRolePermissionsAction`: Gestiona la asignación masiva de permisos a roles.
- `Fortify/` (Directorio): Contiene las implementaciones de los contratos de Fortify para el manejo de credenciales.

### UI (Livewire)
- `Users/ListUsers`: Panel de administración de cuentas.
- `Roles/ManageRoles`: Interfaz para la gestión de la matriz de seguridad.
- `Toast`: Componente de feedback visual.

---

## ⚠️ [RIESGOS]
1. **Seguridad de Cuentas:** Al ser el módulo que maneja contraseñas y sesiones, es el blanco principal de ataques. Se debe asegurar el uso de MFA (Multi-Factor Authentication) en roles críticos.
2. **Impacto en el Login:** Cualquier error en las Actions de este módulo puede dejar a todos los usuarios fuera del sistema. Los cambios aquí requieren pruebas exhaustivas de regresión.
3. **Consistencia de Permisos:** Un cambio en los permisos de un rol afecta inmediatamente a todos los usuarios asociados. Se recomienda auditar los cambios en la matriz de permisos.

---

## 📋 Ejemplo de Uso

### Verificar si un usuario puede realizar una acción
```php
if ($user->can('schedule.edit')) {
    // ...
}
```

### Obtener una configuración global
```php
$setting = AppSetting::get('uccx_api_url', 'https://default.url');
```
