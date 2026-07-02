# Especificación Técnica Detallada: DocumentationModule (Módulo de Documentación y Base de Conocimiento)

> Documento RUP Centrado en Arquitectura
> **Módulo:** DocumentationModule
> **Ruta:** `app/Modules/DocumentationModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **DocumentationModule** tiene como propósito centralizar el conocimiento estático y las políticas procedimentales de la organización. Funciona como una **Base de Conocimiento (Knowledge Base) o Wiki corporativa**, permitiendo al departamento de Recursos Humanos, IT y Operaciones publicar manuales, guías, preguntas frecuentes (FAQs) y protocolos operativos estándar (SOPs).

A diferencia del `CommunicationsModule` (enfocado en el *engagement* social e información efímera o noticias del día), el `DocumentationModule` está diseñado para la retención a largo plazo, la indexación rápida (búsqueda de texto completo) y la estructuración jerárquica de la información oficial (Políticas, Manuales de Usuario, Reglamentos).

---

## 2. Casos de Uso Detallados

A continuación, los flujos principales soportados por el módulo:

### CU-DOC-01: Gestión de Artículos de Conocimiento (CRUD)

- **Actor:** Administrador / Redactor de Contenido (HR, IT, Calidad).
- **Descripción:** Crear, actualizar y archivar documentos oficiales de la empresa.
- **Flujo Principal:**
  1. El actor ingresa al panel de administración y redacta un artículo nuevo.
  2. Escribe el contenido usando Markdown o un editor WYSIWYG avanzado (soportando tablas, imágenes e incrustaciones).
  3. Define metadatos críticos: Título, estado de publicación (`draft`, `published`), y palabras clave para mejorar la indexación.
  4. El sistema guarda el registro en la tabla `articles`.

### CU-DOC-02: Búsqueda y Consulta de Artículos

- **Actor:** Empleado (Cualquier usuario autenticado).
- **Descripción:** Acceso rápido a guías y procedimientos en el día a día.
- **Flujo Principal:**
  1. El empleado accede al portal público del módulo (Base de Conocimiento).
  2. Ingresa un término en la barra de búsqueda (ej. "Política de Vacaciones").
  3. El sistema ejecuta una búsqueda (idealmente indexada, como Laravel Scout o consultas `LIKE` optimizadas).
  4. Retorna los resultados. El empleado lee el artículo estructurado para resolver su duda operativa.

---

## 3. Requerimientos Funcionales (RF)

- **RF-DOC-01 (Editor de Texto Enriquecido):** La redacción de artículos debe soportar un formato enriquecido (Markdown preferiblemente, o HTML sanitizado) que permita a los autores estructurar los manuales con encabezados, negritas, listas y enlaces internos.
- **RF-DOC-02 (Control de Estados de Publicación):** Todo artículo debe poseer un estado explícito (`is_published` o `status = draft/published`) para evitar que borradores inacabados sean visibles para los empleados regulares.
- **RF-DOC-03 (Trazabilidad de Autoría):** El sistema debe registrar de forma inmutable quién fue el usuario creador original del documento (`author_id`), aunque futuras ediciones puedan ser hechas por otros administradores.
- **RF-DOC-04 (Motor de Búsqueda Rápida):** Dado que el volumen de manuales puede crecer, la vista principal (`ArticleIndex`) debe incluir un buscador eficiente para localizar documentos por título o contenido.

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-DOC-01 (Seguridad - XSS):** Debido a que se renderiza contenido HTML enriquecido en el portal público de empleados, la renderización de la vista (Blade/Livewire) debe asegurar que todo el contenido sea sanitizado o escapado correctamente para prevenir inyecciones XSS. Si se usa Markdown, debe convertirse a HTML en el servidor de forma segura.
- **RNF-DOC-02 (Rendimiento en Lectura):** Al ser información estática de alta recurrencia, la lectura de artículos debería estar optimizada, delegando las imágenes pesadas a CDNs o al sistema de caché cuando sea posible.
- **RNF-DOC-03 (Cero Complejidad Asíncrona):** A diferencia de otros módulos, este módulo es sincrónico, orientado a la lectura. No debe sobrecargar las colas de procesamiento (`Queues`) sin necesidad.

---

## 5. Modelos de Datos Detallados

La estructura de datos es minimalista pero robusta para la gestión de documentos estáticos:

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `Article`** | | **Manuales, FAQs y Políticas** |
| `id` | `bigint` (PK)| Identificador único. |
| `title` | `string` | Título del artículo. Sujeto a índices de búsqueda. |
| `slug` | `string` | Identificador amigable para URLs (ej. `politica-de-vacaciones`). |
| `content` | `text` | El cuerpo íntegro del documento (Markdown o HTML). |
| `status` / `is_published`| `string` / `boolean`| Control de visibilidad. `draft` o `published`. |
| `author_id` | `integer` (FK) | Relación obligatoria hacia `CoreModule\User` (creador). |

---

## 6. Roles y Permisos (Policies)

- **`ArticlePolicy`**
  - `viewAny`, `view`: Permitido globalmente a cualquier usuario autenticado en el sistema (`auth()->check()`). El conocimiento debe ser accesible.
  - `create`, `update`, `delete`: Estrictamente reservado para roles administrativos (ej. `documentation.manage`). Un empleado base no puede alterar los procedimientos de la empresa.

---

## 7. Eventos, Listeners y Notificaciones

Dado el propósito de "Base de Conocimiento estática", la propagación de eventos es mínima comparada con módulos dinámicos:

- `ArticlePublished` (Sugerido): Podría emitirse cuando un artículo importante cambia su estado a publicado. Esto permitiría que el `CommunicationsModule` capte el evento y genere un `News` automático avisando a todos: "Se ha actualizado la Política de Vestimenta".

---

## 8. Servicios y Acciones Detallados (Actions)

Debido al diseño actual (observable en el árbol de directorios), el módulo ha delegado fuertemente el comportamiento en componentes Livewire (controladores ricos). Sin embargo, bajo principios DDD, las mutaciones deberían eventualmente aislarse:

- **Proyección de Refactor (Actions Futuros):**
  - `CreateArticleAction`: Validaría unicidad de slugs y sanitización del HTML antes de persistir.
  - `UpdateArticleAction`: Guardaría el historial de revisiones.

Actualmente, esta lógica reside directamente dentro de `Livewire\Admin\ManageArticles`.

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web)

La UI está completamente orientada a Livewire para proveer fluidez (SPA):

- **Área Pública (Empleados):**
  - `GET /docs` -> Componente `Livewire\Public\ArticleIndex`: Muestra la biblioteca de documentos, barra de búsqueda y filtros rápidos.
  - `GET /docs/{slug}` -> Componente `Livewire\Public\ArticleDetail`: Renderiza el cuerpo completo del documento.
- **Área Administrativa (Editores):**
  - `GET /admin/docs` -> Componente `Livewire\Admin\ManageArticles`: Data table interactiva tipo CRUD, permitiendo edición en línea o apertura de modales para modificar el contenido.

---

## 10. Dependencias con otros Módulos

- **Dependencia (Downstream) de `CoreModule`:** Ineludible para relacionar la autoría (`author_id`) y aplicar las Políticas de acceso sobre el `Article`.
- **Dependencia Transversal de `FilesystemModule` (Proyectada):** Si un manual de procedimientos requiere adjuntar formatos (ej. un Excel descargable de "Formato de Vacaciones"), el manejo del archivo físico debe delegarse al módulo de sistema de archivos.

---

## 11. Estructura de Carpetas

```tree
app/Modules/DocumentationModule
├── Livewire
│   ├── Admin
│   │   └── ManageArticles.php
│   └── Public
│       ├── ArticleDetail.php
│       └── ArticleIndex.php
├── Models
│   └── Article.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       └── livewire
│           ├── admin
│           │   └── manage-articles.blade.php
│           └── public
│               ├── article-detail.blade.php
│               └── article-index.blade.php
└── Routes
    └── web.php
```
