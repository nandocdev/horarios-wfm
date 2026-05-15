# Manual de Usuario: WfmModule (WorkForce Management)

## 📌 Introducción
El **WfmModule** es el motor de planificación y optimización de HorariosWFM. Su objetivo es asegurar que la cantidad correcta de personal esté en el lugar correcto, en el momento adecuado, con el horario óptimo para cumplir con el nivel de servicio.

---

## 👨‍💻 Vista del Operador (Autogestión)
Herramientas diseñadas para el empoderamiento del agente.

### 1. Mi Horario y Mi Día
- **Mi Horario:** Vista mensual/semanal de los turnos asignados.
- **Mi Día:** Vista detallada de la jornada actual, incluyendo horas de almuerzo, descansos y actividades programadas especiales.

### 2. Gestión de Solicitudes
- **Intercambio de Turnos:** Permite solicitar un cambio de jornada con un compañero.
- **Permisos y Licencias:** Formulario para solicitar ausencias programadas (vacaciones, permisos administrativos, etc.).
- **Historial:** Seguimiento en tiempo real del estado de cada solicitud (Pendiente, Aprobada, Rechazada).

---

## 🛠️ Vista de WFM y Coordinación
Herramientas de planificación y control.

### 1. Planificación Semanal
- **Acceso:** `Schedules > Planificación`.
- Permite definir los turnos de toda la semana para equipos completos o individuos específicos.
- **Publicación:** Los horarios pueden guardarse como borrador y publicarse masivamente cuando estén listos.

### 2. Actividades Intradía
- **Acceso:** `Schedules > Actividades Intradía`.
- Permite programar actividades fuera de línea que no son turnos base: coaching, reuniones de equipo, capacitaciones o mantenimientos.
- **Validación de Traslapes:** El sistema alerta si intentas programar dos actividades simultáneas para el mismo empleado.

### 3. Gestión de Excepciones
Permite registrar ausencias no planificadas o situaciones excepcionales que afecten el horario original del agente.

---

## 📚 Catálogos y Configuración
Personalización de las reglas de negocio.
- **Turnos (Shifts):** Define las plantillas de horario (ej: 8:00 AM - 17:00 PM).
- **Tipos de Actividad:** Define qué cuenta como tiempo productivo o no (ej: Lunch vs Meeting).
- **Estados de Agente:** Sincroniza los códigos de estado con los que los agentes se marcan en Cisco.
- **Aprobaciones:** Configuración de quiénes tienen autoridad para validar solicitudes.

---

## ⚠️ Guía de Planificación Eficiente
1. **Antelación:** Se recomienda publicar los horarios con al menos una semana de anticipación para permitir que los agentes gestionen sus intercambios.
2. **Productividad:** Al crear tipos de actividad, marca correctamente el flag de "Productivo" para que los KPIs del Dashboard de Operaciones sean precisos.
3. **Notas:** Siempre utiliza el campo "Notas" en las actividades intradía para dejar trazabilidad del motivo de la sesión (ej: "Feedback de Calidad Marzo").
