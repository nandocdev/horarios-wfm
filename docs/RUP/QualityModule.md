# Especificación Técnica Detallada: QualityModule (Módulo de Aseguramiento de Calidad)

> Documento RUP Centrado en Arquitectura
> **Módulo:** QualityModule
> **Ruta:** `app/Modules/QualityModule`
> **Estado:** Fase de Incepción / Scaffolding (Diseño Propuesto)

## 1. Resumen Ejecutivo y Propósito del Módulo

El **QualityModule** es el pilar de Aseguramiento de Calidad (Quality Assurance - QA) de la operación telefónica. Aunque el módulo se encuentra actualmente en su fase inicial (cascarón vacío), su propósito arquitectónico es claro: **auditar, medir y retroalimentar las interacciones de los agentes telefónicos con los clientes**.

Este módulo permitirá a los Analistas de Calidad escuchar grabaciones provistas por el `ConnectModule`, evaluarlas contra rúbricas de calificación predefinidas (`EvaluationForm`), y generar una calificación (`Score`) que impactará directamente en los bonos de desempeño calculados por el `OperationsModule`.

---

## 2. Casos de Uso Detallados (Propuestos)

A continuación, los flujos principales de gestión de calidad que el módulo debe soportar:

### CU-QA-01: Creación de Formularios de Evaluación (Rúbricas)

- **Actor:** Supervisor de Calidad.
- **Descripción:** Definir las plantillas con las que se auditarán las llamadas.
- **Flujo Principal:**
  1. El actor ingresa a "Gestión de Formularios".
  2. Crea un nuevo `EvaluationForm` (Ej. "Ventas Tarjetas de Crédito").
  3. Añade `EvaluationCriteria` agrupados por bloques (Ej. "Saludo", con peso 10%, "Rebate de Objeciones", con peso 50%, "Errores Críticos / Fatal Errors").
  4. Publica el formulario para que los Analistas lo utilicen.

### CU-QA-02: Auditoría y Calificación de Llamada

- **Actor:** Analista de Calidad (QA).
- **Descripción:** Escuchar una llamada y emitir un veredicto.
- **Flujo Principal:**
  1. El Analista selecciona un `CallRecord` (provisto por ConnectModule).
  2. Inicia una nueva `AgentEvaluation`.
  3. Escucha la grabación y va marcando *Cumple / No Cumple* en los criterios del formulario.
  4. El sistema calcula el puntaje final automáticamente.
  5. Se emite un `EvaluationCompletedEvent` que notifica al Agente y a su Supervisor.

### CU-QA-03: Apelación de Calificación (Disputa)

- **Actor:** Agente Telefónico / Supervisor Operativo.
- **Descripción:** Impugnar una mala calificación si el agente considera que el QA fue injusto.
- **Flujo Principal:**
  1. El agente recibe su nota de 75/100 y ve que le penalizaron por no ofrecer una garantía.
  2. El agente pulsa el botón "Apelar", creando un `EvaluationDispute` argumentando que el cliente lo interrumpió y cortó rápido.
  3. Un Supervisor de Calidad revisa la disputa, escucha la llamada, y dictamina a favor (`Score` ajustado a 85/100) o en contra (Se mantiene la nota).

---

## 3. Requerimientos Funcionales (RF)

- **RF-QA-01 (Manejo de Errores Fatales):** Si un evaluador marca un criterio etiquetado como "Error Crítico" (Ej. Fraude, Insultar al cliente), el sistema debe anular matemáticamente toda la calificación, dejando el `Score` en 0 de forma automática, sin importar los otros ítems.
- **RF-QA-02 (Cálculo Dinámico de Pesos):** El módulo debe garantizar que la suma de los pesos relativos de los criterios de evaluación dentro de un formulario siempre sea igual al 100% al momento de guardarlo.
- **RF-QA-03 (Muestreo Aleatorio):** El sistema debe proveer una herramienta (Action) que genere una lista aleatoria de llamadas (`CallRecords`) sugeridas para evaluación, basándose en la cuota mensual requerida por agente (ej. 4 llamadas al mes por persona).

---

## 4. Requerimientos No Funcionales (RNF)

- **RNF-QA-01 (Trazabilidad y No Repudio):** Una evaluación cerrada no puede ser alterada silenciosamente. Cualquier modificación *a posteriori* de un puntaje debe generar un log auditable y requerir el permiso especial de "Coordinador de Calidad".
- **RNF-QA-02 (Integración Multimedia):** La interfaz de evaluación (Livewire Component) debe poseer un reproductor de audio integrado (HTML5 Audio) que consuma el stream de voz (presuntamente alojado en S3 vía `FilesystemModule`), permitiendo estampar marcas de tiempo (*timestamps*) como justificación en los comentarios.

---

## 5. Modelos de Datos Detallados (Propuestos)

| Atributo | Tipo / Cast | Descripción y Lógica de Negocio |
| :--- | :--- | :--- |
| **Entidad: `EvaluationForm`**| | **Plantilla Maestra** |
| `name` | `string` | Nombre de la plantilla. |
| `is_active` | `boolean` | Para descontinuar formularios viejos sin borrar el histórico. |
| **Entidad: `EvaluationCriteria`**| | **Ítems a Evaluar** |
| `form_id` | `integer` (FK)| Relación a la plantilla. |
| `description` | `string` | "El agente utiliza el saludo corporativo". |
| `weight` | `decimal` | Peso porcentual del ítem (ej. 15.00). |
| `is_fatal_error` | `boolean` | Si es true y se falla, anula toda la nota. |
| **Entidad: `AgentEvaluation`**| | **Evaluación Ejecutada** |
| `user_id` | `integer` (FK)| Agente evaluado. |
| `evaluator_id` | `integer` (FK)| Analista de QA. |
| `call_record_uuid`| `uuid` (FK)| Identificador de la llamada en ConnectModule. |
| `form_id` | `integer` (FK)| Qué plantilla se usó. |
| `final_score` | `decimal` | Nota final (0 a 100). |
| **Entidad: `EvaluationDispute`**| | **Proceso de Apelación** |
| `evaluation_id` | `integer` (FK)| La nota que se impugna. |
| `disputed_by` | `integer` (FK)| Supervisor o Agente que la inicia. |
| `resolution` | `enum` | Pendiente, Aceptada, Rechazada. |

---

## 6. Roles y Permisos (Policies)

- **`AgentEvaluationPolicy`:**
  - `view`: El agente solo ve las suyas. El supervisor operativo ve las de su equipo.
  - `create`: Solo el rol "Analista QA".
- **Privilegio Administrativo (`quality.forms.manage`):** Exclusivo para Coordinadores de Calidad para alterar los pesos y criterios del formulario, ya que impacta los bonos financieros de la compañía.

---

## 7. Eventos, Listeners y Notificaciones (Propuestos)

- `EvaluationCompleted`: Dispara un Listener que notifica al agente para que ingrese a firmar su *Feedback* y leer las oportunidades de mejora. Envía la nota al `OperationsModule` para engrosar el Scorecard del día.
- `DisputeRaised`: Avisa al equipo de QA que una evaluación ha sido impugnada y requiere re-escucha.
- `DisputeResolved`: Avisa al agente sobre el veredicto de su queja (modificación o ratificación de nota).

---

## 8. Servicios y Acciones (Proyectados)

- **`CreateEvaluationAction`:** Inicia una transacción, guarda el `AgentEvaluation`, guarda el detalle de los puntajes por criterio (`EvaluationItem`), calcula matemáticamente si aplica un error fatal, consolida la nota final y despacha el evento.
- **`ResolveDisputeAction`:** Permite alterar el `final_score` de una evaluación ya cerrada, exigiendo un comentario obligatorio de justificación y dejando un *trail* de auditoría.

---

## 9. Endpoints o Rutas Detalladas (Proyectados)

- **`GET /quality/my-evaluations`** -> Componente Livewire para que el agente firme digitalmente y acepte su nota.
- **`GET /admin/quality/evaluate/{call_uuid}`** -> Interfaz de trabajo del Analista. Mitad de pantalla reproductor de audio, mitad de pantalla rúbrica interactiva (Livewire).
- **`GET /admin/quality/forms/upsert`** -> Editor visual para armar la rúbrica y distribuir los porcentajes.

---

## 10. Dependencias con otros Módulos

El QualityModule actúa como un "Juez" que cruza información:

- **Dependencia Estricta (`ConnectModule`):** Requiere imperativamente tener acceso a los identificadores de llamada, duraciones y enlaces de grabación de audio.
- **Dependencia Horizontal (`OperationsModule`):** Envía periódicamente los promedios de calidad para que Operaciones calcule el bono general del agente.
- **Dependencia Upstream (`CoreModule` & `PersonnelModule`):** Usuarios, validación de equipos y políticas de acceso.

---

## 11. Estructura de Carpetas Actual

*El módulo actualmente se encuentra en etapa inicial (cascarón vacío). La estructura mostrada corresponde al estado presente, esperando la implementación de los diseños propuestos arriba.*

```tree
app/Modules/QualityModule
├── Http
│   ├── Controllers
│   └── Requests
└── Livewire
    └── Forms

6 directories, 0 files
```

---

*Documento técnico profundo generado bajo lineamientos de arquitectura iterativa RUP.*
