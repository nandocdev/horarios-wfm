# WorkflowsModule

## 🎯 Propósito
El `WorkflowsModule` es el motor de estado para los procesos de aprobación multietapa del sistema. Actúa como un repositorio centralizado de "intenciones" y "decisiones", permitiendo que solicitudes complejas (como intercambios de turno o permisos) sean rastreadas y aprobadas de forma transparente antes de impactar los datos operativos reales.

---

## 🚀 Funcionalidades Principales

### 1. Gestión de Solicitudes de Permiso (Leave Requests)
- **Registro de Intención:** Almacena el tipo de permiso, el rango de tiempo solicitado y el motivo.
- **Trazabilidad de Decisiones:** Mantiene un historial de aprobaciones (`LeaveRequestApproval`), permitiendo que múltiples niveles (Supervisor → Gerente) participen en la decisión.

### 2. Gestión de Intercambios de Turno (Shift Swaps)
- **Solicitudes Peer-to-Peer:** Registra la solicitud de un colaborador a otro para intercambiar jornadas.
- **Snapshots de Seguridad:** Captura el estado de las asignaciones de ambos colaboradores al momento de la solicitud (`requester_assignment_snapshot`), asegurando que la aprobación se base en datos consistentes.
- **Flujo de Aprobación Doble:** Requiere tanto la aceptación del destinatario como la validación final del supervisor o WFM.

### 3. Orquestación de Estados
- **Máquina de Estados:** Controla el ciclo de vida de cada solicitud (`pending`, `approved`, `rejected`, `completed`).
- **Desacoplamiento Operativo:** El módulo no ejecuta los cambios en los horarios directamente; en su lugar, emite eventos o es consultado por `WfmModule` para aplicar los cambios una vez alcanzado el estado terminal "Aprobado".

---

## 🛠 Estructura Técnica

### Modelos Clave
- `LeaveRequest`: Datos de la solicitud de ausencia.
- `ShiftSwapRequest`: Datos del intercambio solicitado entre dos empleados.
- `LeaveRequestApproval` / `ShiftSwapApproval`: Registro de quién, cuándo y por qué aprobó o rechazó una solicitud.

---

## ⚠️ [RIESGOS]
1. **Desfase de Datos:** Existe el riesgo de que los datos del horario cambien mientras una solicitud de intercambio está pendiente. El uso de `snapshots` mitiga esto, pero requiere validación al momento de la ejecución final.
2. **Complejidad de la Cadena de Aprobación:** Si no se definen reglas claras de quién puede aprobar qué, las solicitudes pueden quedar en "limbo" administrativo.
3. **Integridad Referencial:** Al ser un módulo que orquesta intenciones sobre otros módulos (`Schedule`, `Employees`), la eliminación de registros en los módulos origen debe ser manejada con cuidado para no romper el historial de flujos.

---

## 📋 Ejemplo de Uso

### Crear una solicitud de intercambio (desde WfmModule)
```php
$request = ShiftSwapRequest::create([
    'requester_id' => $me->id,
    'recipient_id' => $partner->id,
    'requested_date' => $date,
    'status' => 'pending',
    'requester_assignment_snapshot' => $myCurrentShift->toArray(),
]);
```
