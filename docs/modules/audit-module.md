# Manual de Usuario: AuditModule

## 📌 Introducción
El **AuditModule** es el "testigo" silencioso de HorariosWFM. Su propósito es capturar y registrar de forma inmutable cada acción relevante realizada por los usuarios sobre los datos críticos del sistema, garantizando la transparencia y permitiendo auditorías de cumplimiento.

---

## 🔍 Registro de Auditoría (Audit Logs)
- **Acceso:** `Administración > Auditoría`.
- **Qué registra:** 
    - Quién realizó la acción (Usuario).
    - Qué acción se realizó (Creación, Actualización, Eliminación).
    - Sobre qué objeto (ej: Empleado "Juan Pérez", Equipo "Cisco A").
    - Desde qué dirección IP.
    - Cuándo ocurrió (Fecha y Hora exacta).

### Filtros Avanzados
Para facilitar la investigación, puedes filtrar por:
- **Rango de Fechas.**
- **Tipo de Acción** (Created, Updated, Deleted).
- **Usuario específico.**
- **Búsqueda por ID de entidad.**

---

## 📤 Exportación de Datos
- **Acceso:** Botón de **Exportar** en la vista de Auditoría.
- **Formatos:** Soporta exportación a CSV (para análisis en Excel) o JSON (para auditorías técnicas).
- **Alcance:** La exportación respeta los filtros aplicados en pantalla, permitiendo descargar solo la información necesaria.

---

## ⚠️ Importancia de la Auditoría
1. **Seguridad:** Permite detectar patrones de acceso inusuales o cambios no autorizados.
2. **Cumplimiento:** Ayuda a la institución a cumplir con normativas de control interno y transparencia de datos.
3. **Recuperación de Información:** En caso de errores, la auditoría muestra los valores anteriores de los registros para facilitar su corrección.
