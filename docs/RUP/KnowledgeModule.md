# Especificación Técnica Detallada: KnowledgeModule (Módulo de Base de Conocimiento Operativo)

> Documento RUP Centrado en Arquitectura
> **Módulo:** KnowledgeModule
> **Ruta:** `app/Modules/KnowledgeModule`

## 1. Resumen Ejecutivo y Propósito del Módulo

El **KnowledgeModule** es una Base de Conocimiento (Knowledge Base - KB) especializada y orientada a la **operación telefónica**. A diferencia del `DocumentationModule` (que es estático y corporativo), este módulo está diseñado para soportar a los Agentes de Call Center (*Operators*) en tiempo real mientras atienden llamadas.

Su propósito principal es proveer *scripts*, argumentarios, procedimientos paso a paso y guías de resolución vinculadas directamente a la "Cola" (`Queue`) o Campaña que el agente está atendiendo. Destaca por incluir un sistema de control de versiones (`ArticleVersion`) para auditar cambios en los procedimientos operativos sin perder el historial.

---

## 2. Casos de Uso Detallados

A continuación, los flujos principales de gestión de conocimiento operativo:

### CU-KM-01: Consulta de Conocimiento en Tiempo Real (Operator View)

- **Actor:** Agente Telefónico (Operador).
- **Descripción:** Acceso ultra-rápido a la información necesaria para resolver la llamada actual.
- **Flujo Principal:**
  1. El Agente recibe una llamada de una Cola específica (ej. "Soporte Técnico Internet").
  2. El Agente ingresa a la vista operativa (`OperatorView`).
  3. El sistema filtra automáticamente los artículos (`Article`) vinculados a la `Queue` actual del agente, sugiriendo los guiones de atención pertinentes.
  4. El Agente utiliza la barra de búsqueda para encontrar respuestas rápidas mediante etiquetas (`Tag`) o palabras clave.

### CU-KM-02: Creación y Versionado de Procedimientos

- **Actor:** Supervisor / Analista de Calidad.
- **Descripción:** Redacción de nuevos manuales operativos con trazabilidad de cambios.
- **Flujo Principal:**
  1. El Analista accede al componente `UpsertArticle` para redactar una nueva guía.
  2. Llena el formulario (`ArticleForm`), asignándole una Categoría (`Category`) y mapeándolo a las Colas (`Queue`) donde aplica.
  3. Al enviar, se invoca el `CreateArticleAction`, el cual crea el `Article` principal y un registro inicial en `ArticleVersion` (Versión 1.0) con el contenido exacto.

### CU-KM-03: Actualización Crítica de un Argumentario

- **Actor:** Supervisor.
- **Flujo Principal:**
  1. Ocurre un cambio en las políticas de la empresa (ej. nueva tasa de interés).
  2. El Supervisor edita el artículo existente.
  3. El sistema invoca `UpdateArticleAction`. En lugar de sobrescribir y perder la historia, se actualiza el `Article` y se inserta un nuevo registro en `ArticleVersion` (Versión 2.0).
  4. Esto permite a Auditoría verificar qué versión del guion estaba leyendo un agente en una fecha pasada específica.

---

## 3. Requerimientos Funcionales (RF)

- **RF-KM-01 (Indexación por Cola):** Un artículo debe poder vincularse a una o múltiples `Queue`s (relación Many-to-Many). Esto garantiza que un agente de ventas no sea inundado con manuales técnicos de soporte.
- **RF-KM-02 (Inmutabilidad del Historial):** Todo cambio en el cuerpo (`body/content`) de un artículo debe desencadenar automáticamente la generación de un `ArticleVersion`. Los registros históricos de versiones nunca deben ser eliminados (*Append-only*).
- **RF-KM-03 (Búsqueda Full-Text):** La vista del Operador debe incorporar un motor de búsqueda de alta eficiencia (ej. usando `MATCH() AGAINST()` en MySQL/Postgres o integración con Laravel Scout/Meilisearch).
- **RF-KM-04 (Taxonomía Dual):** Los artículos deben organizarse jerárquicamente por `Category` (ej. "Hardware", "Facturación") y transversalmente por `Tag` (ej. `#urgente`, `#reembolso`).

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-KM-01 (Rendimiento Extremo en Lectura):** Al ser usado mientras el cliente está en el teléfono, la vista `OperatorView` debe cargar el contenido en menos de 200ms. Se exige una estrategia agresiva de Caché (Redis) para los artículos más consultados por cola.
- **RNF-KM-02 (Interfaz Cero-Distracciones):** El componente `OperatorView` debe carecer de menús administrativos pesados o llamadas a la red innecesarias; debe priorizar el espacio de lectura.
- **RNF-KM-03 (Seguridad de Modificación):** Prevenir que dos supervisores editen el mismo artículo al mismo tiempo (Control de Concurrencia Optimista), verificando que la versión que intentan guardar sea la última vigente.

---

## 5. Modelos de Datos Detallados

A continuación, la estructura relacional de los modelos de dominio:

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `Article`** | | **Cabecera del Procedimiento** |
| `id` | `bigint` (PK)| Identificador de la guía. |
| `title` | `string` | Nombre del procedimiento (ej. "Reinicio de Router"). |
| `category_id` | `integer` (FK)| Relación a `Category`. |
| `author_id` | `integer` (FK)| Usuario creador inicial (Relación a `CoreModule\User`). |
| `current_version_id`| `integer` (FK)| Puntero a la última versión válida en `ArticleVersion`. |
| `is_published` | `boolean` | `true` si es visible en `OperatorView`. |
| **Entidad: `ArticleVersion`**| | **Cuerpo y Auditoría (Historial)** |
| `article_id` | `integer` (FK)| El artículo al que pertenece esta versión. |
| `version_number` | `integer` | Contador autoincremental por artículo (1, 2, 3...). |
| `content` | `text` | El cuerpo de texto enriquecido exacto en ese momento. |
| `updated_by` | `integer` (FK)| Qué usuario (Supervisor) causó esta nueva versión. |
| **Entidad: `Queue` & `Tag`** | | **Filtros de Indexación** |
| `name` | `string` | Nombre de la cola (ej. "Ventas_Inbound") o etiqueta. |
| `article_queue` | `pivot` | Tabla pivote para asociar manuales a colas operativas. |

---

## 6. Roles y Permisos (Policies)

La segmentación de seguridad se basa en la dualidad Operador / Supervisor:

- **`ArticlePolicy`:**
  - `viewAny`, `view`: Autorizado para Agentes y Supervisores. (Limitado por la segmentación de `Queue` en el Frontend).
  - `create`, `update`, `delete`: Estrictamente restringido a Supervisores, Analistas de Calidad y Entrenadores (`knowledge.manage`). Los agentes rasos no editan el conocimiento oficial.

---

## 7. Eventos, Listeners y Notificaciones

- `ArticlePublished` / `ArticleUpdated`: Un evento crucial. Puede despachar un Listener que envíe una notificación urgente ("Se ha actualizado el proceso de Reembolsos") a todos los Agentes logueados actualmente en las colas afectadas por dicho artículo.
- **Auditoría (Core):** Las inserciones en `ArticleVersion` sirven de facto como un registro de auditoría local, aunque pueden sincronizarse con el `AuditModule` general del sistema.

---

## 8. Servicios y Acciones Detallados (Actions & DTOs)

Las operaciones de escritura son delicadas debido al versionado:

### `CreateArticleAction`

- **Responsabilidad:** Creación inicial en cascada.
- **Lógica:**
  1. Recibe un `ArticleDTO` validado.
  2. Inicia transacción (`DB::transaction`).
  3. Inserta el `Article`.
  4. Inserta el `ArticleVersion` inicial (version = 1) con el contenido.
  5. Sincroniza las tablas pivotes de `Queue` y `Tag`.
  6. Actualiza el `current_version_id` del `Article` principal.

### `UpdateArticleAction`

- **Responsabilidad:** Actualización segura y control de versiones.
- **Lógica:**
  1. Recibe el modelo existente y el `ArticleDTO`.
  2. Detecta si el contenido (`content`) cambió. Si no cambió (solo se editó el título o los tags), actualiza el `Article` directamente.
  3. Si el contenido cambió, inserta un **nuevo** `ArticleVersion` incrementando el número de versión anterior.
  4. Actualiza el puntero `current_version_id` en la tabla padre.

---

## 9. Endpoints o Rutas Detalladas (Livewire / Web)

Interfaz dividida en consumo rápido y edición profunda:

- **`GET /knowledge/operator`** -> Componente `Livewire\OperatorView`.
  - Dashboard de una sola página para el agente. Presenta menús colapsables por Categoría, filtrados por la Cola asignada al agente. Alta velocidad de respuesta.
- **`GET /admin/knowledge/articles`** -> Componente `Livewire\ManageArticles`.
  - Listado administrativo, tabla de datos con opción a ver historial de versiones.
- **`GET /admin/knowledge/articles/upsert/{id?}`** -> Componente `Livewire\UpsertArticle`.
  - Formulario unificado para Creación/Edición, inyectando `Livewire\Forms\ArticleForm`. Integra un editor WYSIWYG.

---

## 10. Dependencias con otros Módulos

El KnowledgeModule depende estratégicamente de otros para dar contexto al Agente:

- **Dependencia Downstream (`CoreModule`):** Uso de `User` para autores de versiones y esquemas de `Policies`.
- **Dependencia Horizontal Crítica (`ConnectModule`):** El `OperatorView` puede consumir el estado real del agente provisto por `ConnectModule` para inferir a qué `Queue` pertenece actualmente en el CTI de Cisco, y así filtrar los artículos automáticamente sin que el agente tenga que buscar manualmente.

---

## 11. Estructura de Carpetas

```tree
app/Modules/KnowledgeModule
├── Actions
│   ├── CreateArticleAction.php
│   └── UpdateArticleAction.php
├── DTOs
│   └── ArticleDTO.php
├── Livewire
│   ├── ArticleDetail.php
│   ├── Forms
│   │   └── ArticleForm.php
│   ├── ManageArticles.php
│   ├── OperatorView.php
│   └── UpsertArticle.php
├── Models
│   ├── Article.php
│   ├── ArticleVersion.php
│   ├── Category.php
│   ├── Queue.php
│   └── Tag.php
├── Policies
│   └── ArticlePolicy.php
├── Providers
│   └── ModuleServiceProvider.php
├── Resources
│   └── Views
│       └── livewire
│           ├── article-detail.blade.php
│           ├── manage-articles.blade.php
│           ├── operator-view.blade.php
│           └── upsert-article.blade.php
└── Routes
    └── web.php

```
