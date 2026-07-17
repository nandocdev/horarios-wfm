---

name: ui-engineer
description: Diseña e implementa interfaces utilizando Laravel, Livewire, FluxUI y TailwindCSS, respetando la arquitectura Monolito Modular, priorizando usabilidad, consistencia, accesibilidad y rendimiento.
---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

# UI Engineer

Lee la documentacion del proyecto antes de implementar cualquier funcionalidad.
- docs/ARCHITECTURE.md
- docs/DATA_MODEL.md
- docs/PRD.md
- docs/ROUTES.md

## Cuándo utilizar esta skill

Utiliza esta skill cuando la tarea implique diseñar, construir o mejorar la interfaz de usuario del sistema.

Ejemplos:

* Crear componentes Livewire.
* Diseñar nuevas pantallas.
* Construir dashboards.
* Implementar formularios.
* Crear tablas de datos.
* Diseñar flujos de navegación.
* Mejorar la experiencia de usuario.
* Refactorizar interfaces existentes.
* Optimizar el rendimiento de Livewire.
* Crear componentes reutilizables.
* Implementar layouts.
* Mejorar accesibilidad.

No utilizar esta skill para:

* Implementar reglas de negocio.
* Diseñar la arquitectura del sistema.
* Crear consultas complejas.
* Diseñar modelos de datos.
* Implementar procesos batch o integraciones.

---

# Responsabilidades

Esta skill es responsable exclusivamente de la capa de presentación.

Debe:

* Construir interfaces consistentes.
* Mantener una excelente experiencia de usuario.
* Implementar componentes Livewire.
* Utilizar correctamente FluxUI.
* Diseñar dashboards operativos.
* Construir formularios claros.
* Optimizar el rendimiento de la interfaz.
* Garantizar accesibilidad y responsive design.
* Reutilizar componentes visuales.

Toda lógica del negocio debe delegarse a la capa de aplicación.

---

# Arquitectura del proyecto

Cada módulo es dueño de su propia interfaz.

```text
app/Modules/{Module}/
├── Livewire/
│   └── Forms/
│
└── Resources/
    └── Views/
        └── livewire/
```

Los componentes Livewire y sus vistas deben permanecer dentro del mismo módulo.

Nunca crear vistas fuera del módulo propietario.

---

# Responsabilidades por carpeta

## Livewire

Los componentes Livewire administran:

* estado de la interfaz
* interacción del usuario
* filtros
* paginación
* ordenamiento
* apertura de modales
* eventos de la interfaz
* validación visual

No implementan reglas de negocio.

---

## Livewire/Forms

Utilizar Form Objects para formularios complejos.

Contienen únicamente:

* propiedades
* reglas
* mensajes
* transformación de datos

Nunca ejecutar lógica de negocio desde un Form.

---

## Resources/Views/livewire

Las vistas Blade contienen únicamente:

* presentación
* componentes
* layouts
* slots
* estructura visual

Evitar:

* consultas
* lógica PHP
* cálculos
* reglas del negocio

---

# Flujo de interacción

Toda interacción debe seguir el siguiente flujo:

```text
Usuario
    ↓
Blade
    ↓
Livewire
    ↓
Action
    ↓
Model
```

Nunca acceder directamente desde Livewire hacia los Models para ejecutar lógica de negocio.

Las operaciones funcionales deben delegarse a una Action.

---

# Restricciones

No introducir:

* JavaScript innecesario.
* Librerías de UI adicionales.
* CSS personalizado cuando Tailwind resuelva el problema.
* Componentes duplicados.
* Lógica de negocio en Livewire.
* Consultas SQL dentro de componentes.
* Validaciones duplicadas.

Utilizar Alpine.js únicamente cuando Livewire no pueda resolver el comportamiento de forma eficiente.

FluxUI es el sistema oficial de componentes.

---

# Flujo de trabajo

## 1. Comprender el objetivo

Identificar:

* usuario objetivo
* tarea principal
* frecuencia de uso
* contexto operativo

Toda pantalla debe responder a una necesidad concreta.

---

## 2. Diseñar la experiencia

Definir:

* jerarquía visual
* flujo de navegación
* acciones principales
* estados
* responsive

Eliminar elementos innecesarios.

---

## 3. Implementar

Construir utilizando:

* Livewire
* FluxUI
* TailwindCSS

Mantener consistencia con el resto del sistema.

---

## 4. Optimizar

Revisar:

* renders
* requests Livewire
* carga inicial
* componentes repetidos
* accesibilidad

---

## 5. Validar

Verificar:

* experiencia de usuario
* consistencia visual
* responsive
* accesibilidad
* rendimiento

---

# Convenciones

## Diseño

Priorizar:

* simplicidad
* claridad
* consistencia
* velocidad de uso

Cada pantalla debe ser entendible sin capacitación extensa.

---

## Componentes

Antes de crear un nuevo componente verificar si existe uno reutilizable.

Extraer componentes comunes para:

* tablas
* filtros
* cards
* KPIs
* badges
* botones
* formularios
* modales

Evitar duplicación.

---

## FluxUI

FluxUI es el sistema oficial de componentes.

Prioridad:

1. Componentes FluxUI.
2. Componentes propios reutilizables.
3. HTML personalizado únicamente cuando sea necesario.

---

## TailwindCSS

Utilizar utilidades de Tailwind.

Evitar:

* CSS adicional
* estilos inline
* clases duplicadas

Mantener consistencia visual en todo el proyecto.

---

## Dashboards

Todo dashboard debe organizarse en tres niveles:

1. Indicadores principales.
2. Análisis.
3. Detalle.

La información crítica debe aparecer en la parte superior.

No saturar la pantalla con gráficos.

---

## Tablas

Toda tabla debe soportar, cuando aplique:

* búsqueda
* filtros
* ordenamiento
* paginación
* acciones rápidas
* estados vacíos
* indicadores de carga

Optimizar para grandes volúmenes de información.

---

## Formularios

Todo formulario debe:

* agrupar campos relacionados
* mostrar errores claros
* indicar campos obligatorios
* proporcionar retroalimentación inmediata cuando aporte valor
* minimizar la cantidad de pasos

---

## Estados

Toda interfaz debe contemplar:

* cargando
* vacío
* error
* éxito
* sin permisos
* sin resultados

Nunca dejar al usuario sin retroalimentación.

---

## Accesibilidad

Las interfaces deben cumplir buenas prácticas:

* navegación mediante teclado
* focus visible
* labels descriptivos
* contraste adecuado
* mensajes comprensibles

No depender exclusivamente del color para comunicar estados.

---

## Rendimiento

Optimizar:

* renderizado de componentes
* requests Livewire
* lazy loading
* paginación
* carga diferida
* placeholders

Evitar componentes excesivamente grandes.

---

# Criterios de aceptación

Una interfaz se considera finalizada cuando:

* Respeta la arquitectura Monolito Modular.
* El componente Livewire y su vista pertenecen al mismo módulo.
* No contiene lógica de negocio.
* Utiliza correctamente FluxUI y TailwindCSS.
* Mantiene consistencia visual con el resto del sistema.
* Es responsive y accesible.
* Los estados de carga, error y vacío están implementados.
* El rendimiento de Livewire es adecuado.
* Reutiliza componentes existentes cuando corresponde.
* La experiencia de usuario permite completar la tarea con el menor número posible de interacciones.

## Principio rector

Toda decisión de interfaz debe responder afirmativamente a estas preguntas:

1. ¿Facilita el trabajo del usuario?
2. ¿Es consistente con el resto del sistema?
3. ¿Minimiza la carga cognitiva?
4. ¿Respeta la separación entre presentación y lógica de negocio?
5. ¿Puede mantenerse y evolucionar sin duplicar componentes ni estilos?

Si alguna respuesta es negativa, la interfaz debe replantearse antes de implementarse.
