# Manual de Usuario: WorkflowsModule

## 📌 Introducción
El **WorkflowsModule** es el motor de lógica que gestiona la trazabilidad y las reglas de aprobación para las solicitudes de los empleados. Aunque gran parte de su funcionalidad es interna, es el responsable de asegurar que un permiso o intercambio de turno cumpla con todos los pasos jerárquicos antes de hacerse efectivo.

---

## 🚦 Flujos de Aprobación
El sistema gestiona principalmente dos flujos críticos:

### 1. Solicitudes de Permisos y Licencias (Leave Requests)
Cuando un empleado solicita tiempo libre, se dispara el siguiente flujo:
1. **Creación:** El empleado envía la solicitud con fecha, motivo y soporte (opcional).
2. **Revisión de Supervisor:** El jefe directo recibe una notificación para avalar o rechazar la solicitud basándose en la necesidad operativa.
3. **Validación de WFM:** El equipo de Workforce Management confirma que el impacto en el nivel de servicio es aceptable.
4. **Cierre:** La solicitud se marca como "Aprobada" y el horario del agente se actualiza automáticamente.

### 2. Intercambio de Turnos (Shift Swaps)
Este flujo involucra a dos pares antes de la revisión administrativa:
1. **Propuesta:** El empleado A propone un intercambio al empleado B.
2. **Aceptación del Par:** El empleado B debe aceptar la propuesta en su propio panel.
3. **Aprobación de Supervisión:** Una vez aceptado por ambos, el supervisor revisa que ambos perfiles sean aptos para el cambio (ej: que tengan las mismas habilidades/colas).
4. **Finalización:** El sistema intercambia los turnos en el calendario global.

---

## 📑 Trazabilidad y Auditoría
Cada paso del flujo queda registrado con:
- **Usuario que actuó.**
- **Fecha y Hora exacta.**
- **Comentarios de aprobación/rechazo.**
- **Estado de la transición.**

---

## ⚠️ Reglas de Oro del Flujo
1. **Responsabilidad:** Una solicitud aprobada es un compromiso legal y laboral. El supervisor es responsable del impacto operativo de su aval.
2. **Comentarios de Rechazo:** Siempre es obligatorio incluir un motivo claro en caso de rechazo para que el empleado pueda corregir y reenviar si aplica.
3. **Tiempos de Respuesta:** Se recomienda procesar las solicitudes en menos de 48 horas para evitar incertidumbre en la planificación del servicio.
