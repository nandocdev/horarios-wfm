# Especificación Técnica Detallada: FilesystemModule (Módulo de Sistema de Archivos y Almacenamiento)

> Documento RUP Centrado en Arquitectura
> **Módulo:** FilesystemModule
> **Ruta:** `app/Modules/FilesystemModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **FilesystemModule** es el orquestador centralizado de todos los archivos binarios (Documentos, Imágenes, PDFs, Exportaciones Excel) que ingresan y salen del Monolito. Funciona como un gestor documental corporativo, abstrayendo la complejidad de dónde se guardan realmente los archivos (Disco Local, Amazon S3, Google Cloud Storage) mediante el uso del Facade `Storage` de Laravel.

Su propósito es triple:

1. Proveer una jerarquía virtual de archivos y carpetas (`File`, `Folder`) independiente del sistema de almacenamiento físico.
2. Garantizar la seguridad mediante el control de acceso (Políticas y Enlaces Firmados) para evitar que archivos confidenciales (ej. liquidaciones de nómina) sean públicos en internet.
3. Controlar el consumo de recursos mediante cuotas de almacenamiento por usuario (`StorageQuota`).

---

## 2. Casos de Uso Detallados

A continuación, los flujos principales de gestión documental:

### CU-FS-01: Subida de Archivos con Control de Cuota

- **Actor:** Empleado / Sistema (Upstream).
- **Descripción:** Almacenamiento físico y registro lógico de un binario.
- **Flujo Principal:**
  1. El actor selecciona un archivo desde el *FileBrowser* o un módulo upstream envía un objeto `UploadedFile`.
  2. El `UploadFileAction` se invoca. Primero, llama a `GetUserQuotaAction` para verificar si el tamaño del archivo excederá la cuota disponible del usuario.
  3. Si es válido, delega a `Storage::disk('s3')->put()` la subida física del binario.
  4. Persiste el modelo `File` con los metadatos (mime type, tamaño en bytes, `folder_id` padre).
  5. Descuenta el espacio consumido en `StorageQuota`.

### CU-FS-02: Navegación de Directorios Virtuales

- **Actor:** Empleado.
- **Descripción:** Navegar por un árbol de carpetas similar a Google Drive.
- **Flujo Principal:**
  1. El empleado ingresa a "Mis Archivos".
  2. El componente Livewire `FileBrowser` consulta recursivamente el modelo `Folder` filtrando por `owner_id = auth()->id()`.
  3. Al hacer clic en una carpeta, Livewire actualiza el estado (`currentFolderId`) y re-renderiza el grid con los archivos y subcarpetas hijas de forma instantánea.

### CU-FS-03: Compartición de Archivos Segura (File Sharing)

- **Actor:** Propietario del Archivo.
- **Descripción:** Otorgar acceso a un documento confidencial a otros miembros de la empresa.
- **Flujo Principal:**
  1. El propietario hace clic en "Compartir" sobre un `File`.
  2. El `ShareItemAction` crea un registro en la tabla pivote `FileShare`, asociando el archivo con el `user_id` del compañero o con un `department_id` (para compartir masivamente).
  3. Cuando el receptor intenta descargar el archivo, el controlador valida la existencia de este `FileShare` antes de emitir la descarga física (Streaming).

### CU-FS-04: Exportaciones Asíncronas (Download Center)

- **Actor:** Administrador / Supervisor.
- **Descripción:** Generación y recolección de reportes pesados.
- **Flujo Principal:**
  1. Un módulo ajeno (ej. `ConnectModule` para llamadas) despacha un Job que tarda 5 minutos en generar un Excel.
  2. Al terminar, el Job deposita el archivo usando `UploadFileAction` y notifica al usuario.
  3. El usuario ingresa al `DownloadCenter` (Livewire) donde visualiza sus exportaciones recientes y puede descargarlas mediante URLs firmadas (`URL::temporarySignedRoute()`).

---

## 3. Requerimientos Funcionales (RF)

- **RF-FS-01 (Abstracción de Discos):** Todo archivo debe registrar en base de datos en qué disco (`disk_name` ej. `local`, `s3`) fue guardado para facilitar migraciones futuras o esquemas híbridos de almacenamiento.
- **RF-FS-02 (Estructura de Árbol Virtual):** El modelo `Folder` debe implementar el patrón de diseño *Adjacency List* o *Nested Set* (`parent_id` referenciando a su misma tabla) para soportar anidación infinita de directorios.
- **RF-FS-03 (Descargas Privadas Streamed):** Los archivos nunca deben ser servidos directamente desde el disco público de Nginx/Apache. Toda descarga debe pasar por una ruta protegida de Laravel que verifique permisos y retorne el archivo usando `Storage::download()` o *StreamedResponse* para no saturar la RAM del servidor.
- **RF-FS-04 (Gestión de Cuotas):** El modelo `StorageQuota` debe rastrear en tiempo real los bytes consumidos (`used_bytes`) vs el límite máximo (`max_bytes`) configurado para cada usuario.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-FS-01 (Seguridad - Evitación de LFI):** El sistema debe prevenir ataques de *Local File Inclusion* o *Path Traversal*. Los nombres de archivo subidos deben ser re-generados (ej. usando UUIDs o un hash seguro: `$file->hashName()`) antes de tocar el disco físico, almacenando el "Nombre Original" únicamente como un metadato en base de datos.
- **RNF-FS-02 (Rendimiento - Cargas Pesadas):** La plataforma de carga (*upload*) en frontend (Livewire o JS puro) debe soportar carga particionada (Chunked Uploads) para archivos que superen los 20MB, mitigando los límites de `post_max_size` de PHP.
- **RNF-FS-03 (Saneamiento de Huérfanos):** Si el `DeleteFileSystemItemAction` se ejecuta, el sistema debe primero intentar borrar el binario físico en el disco (`Storage::delete()`) y, **solo si tiene éxito**, eliminar el registro de la base de datos, evitando que se acumule "basura" fantasma en el disco duro o en S3.

---

## 5. Modelos de Datos Detallados

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `File`** | | **Representación lógica del binario** |
| `id` | `uuid` (PK)| Preferible UUID para enmascarar descargas públicas. |
| `folder_id` | `integer` (FK)| Carpeta contenedora (nulo = raíz). |
| `owner_id` | `integer` (FK)| `User` dueño del archivo (impacta su cuota). |
| `original_name` | `string` | Nombre que veía el usuario en su PC (Ej. `balance.pdf`). |
| `disk` / `path` | `string` | Ubicación física (Ej. `s3`, `company/2026/uuid.pdf`). |
| `mime_type` / `size`| `string` / `bigint`| Metadatos obligatorios para forzar los headers de descarga. |
| **Entidad: `Folder`** | | **Directorio Virtual** |
| `name` | `string` | Nombre de la carpeta. |
| `parent_id` | `integer` (FK)| ID de la misma tabla (Auto-relación). |
| `owner_id` | `integer` (FK)| Creador de la carpeta. |
| **Entidad: `FileShare`** | | **Tabla Pivote (Permisos Grant)** |
| `file_id` / `folder_id`| `uuid` / `integer`| Qué se está compartiendo. |
| `shared_with_user_id`| `integer` (FK)| A quién se le da acceso explícito. |
| **Entidad: `StorageQuota`**| | **Límites de Almacenamiento** |
| `user_id` | `integer` (PK/FK)| Relación 1:1 con el empleado. |
| `used_bytes` / `max_bytes`| `bigint` | Contadores numéricos para validación matemática rápida. |

---

## 6. Roles y Permisos (Policies)

- **`FilePolicy` / `FolderPolicy`:**
  - `view`: Retorna `true` si el usuario es el `owner_id` **o** si existe un registro activo en `FileShare` a favor del usuario.
  - `update`, `delete`: Estrictamente reservados al `owner_id` original o a un Super Admin.
- **Gestión de Cuotas (`quota.manage`):** Permiso especial para que los administradores de TI puedan ampliar o reducir el `max_bytes` de un empleado específico.

---

## 7. Eventos, Listeners y Notificaciones

- `FileUploaded`: Emitido al completar un *upload* exitoso. Útil para que otros módulos auditen transacciones o generen *thumbnails* (miniaturas de imágenes) asíncronamente en caso de ser necesario.
- `FileShared`: Al ejecutarse el `ShareItemAction`, este evento se despacha para encolar un correo electrónico notificando al receptor: *"Juan te ha compartido el documento 'Balance.pdf'"*.

---

## 8. Servicios y Acciones Detallados (Actions)

### `UploadFileAction`

- **Ubicación:** `App\Modules\FilesystemModule\Actions\UploadFileAction`
- **Responsabilidad:** Flujo completo de carga segura.
- **Lógica:**
  1. Recibe un `$file` (Instancia `UploadedFile`), un `$ownerId` y opcionalmente un `$folderId`.
  2. Valida la cuota llamando a `GetUserQuotaAction`. Si `used + new > max`, lanza `QuotaExceededException`.
  3. Transfiere el archivo: `$path = $file->storePublicly('archivos', 's3')` (si es público) o `store()` si es privado.
  4. Crea el modelo Eloquent `File` con el `$path` devuelto.
  5. Incrementa el contador `used_bytes` de la tabla de cuota.

### `DeleteFileSystemItemAction`

- Capaz de recibir un `File` o un `Folder`. Si es un `Folder`, utiliza recursividad o las relaciones hijas de Eloquent para borrar en cascada todos los archivos contenidos, liberando la cuota masivamente antes de eliminar la jerarquía en la base de datos.

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web)

### Área Interactiva (SPA Livewire)

- **`GET /drive`** -> Componente `Livewire\FileBrowser`.
  - Muestra la vista "Explorador de Archivos". Usa `wire:click` para navegar entre carpetas, subir (mediante `<input type="file" wire:model="photo">` o integraciones AlpineJS / FilePond) y eliminar.
- **`GET /download-center`** -> Componente `Livewire\DownloadCenter`.
  - Bandeja central de exportaciones asíncronas de otros módulos.

### Controladores de Descarga Física (Http)

- **`GET /download/file/{uuid}`**
  - **Crítico:** Ruta protegida por middleware `auth`.
  - El controlador resuelve el modelo, verifica `Policy` y si pasa, ejecuta: `return Storage::disk($file->disk)->download($file->path, $file->original_name);`.

---

## 10. Dependencias con otros Módulos

El FilesystemModule es un proveedor de servicios transversal (Servicio de Infraestructura):

- **Dependencia Downstream Estricta (`CoreModule`):** Requiere imperativamente al `User` para resolver la titularidad (`owner_id`), las cuotas y los permisos de compartición.
- **Interacción Upstream (Es consumido por otros):**
  - `CommunicationsModule` (Para guardar adjuntos de Noticias y fotos de perfil de usuarios).
  - `ConnectModule` (Para depositar los exportables masivos de reportes).
  - Cualquier otro módulo que requiera generar PDFs o guardar documentos debe inyectar el `UploadFileAction` y no hablar nunca directamente con el disco.

---

## 11. Estructura de Carpetas

```tree
app/Modules/FilesystemModule
├── Actions
│   ├── DeleteFileSystemItemAction.php
│   ├── GetUserQuotaAction.php
│   ├── ShareItemAction.php
│   └── UploadFileAction.php
├── Database
│   └── Migrations
│       └── 2026_05_15_080356_create_storage_quotas_table.php
├── Livewire
│   ├── DownloadCenter.php
│   ├── FileBrowser.php
│   └── QuotaManager.php
├── Models
│   ├── File.php
│   ├── FileShare.php
│   ├── Folder.php
│   └── StorageQuota.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       ├── livewire
│       │   ├── download-center.blade.php
│       │   ├── file-browser.blade.php
│       │   └── quota-manager.blade.php
│       └── partials
│           └── tree-node.blade.php
└── Routes
    └── web.php
```
