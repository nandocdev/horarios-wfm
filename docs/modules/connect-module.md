# Manual de Usuario: ConnectModule (Integración Cisco)

## 📌 Introducción
El **ConnectModule** es el puente tecnológico entre HorariosWFM y la plataforma de telefonía **Cisco Finesse**. Su función principal es extraer datos de llamadas, chats y estados de agentes para convertirlos en métricas operativas accionables.

---

## 📞 Registro de Llamadas (Call Records)
- **Concepto:** Cada interacción telefónica genera un registro en el sistema.
- **Funcionalidades:**
    - **Captura Automática:** Mediante la integración, el sistema detecta el inicio y fin de las llamadas.
    - **Tipificación Manual:** Los agentes pueden completar la información del ciudadano (cédula, motivo de consulta) para enriquecer la base de datos.
    - **Listado de Llamadas:** Historial completo para seguimiento de casos específicos.

---

## 📊 Dashboards de Contact Center
Análisis especializado en telefonía.
- **Dashboard del Agente:** Muestra al operador sus métricas personales del día (Llamadas atendidas, Tiempo promedio de conversación, Tiempo en Not Ready).
- **Dashboard General:** Vista de supervisión enfocada en colas de llamadas, niveles de servicio por canal y volumen de interacciones.

---

## 📚 Catálogos de Servicio
Define cómo se clasifican las interacciones:
- **Colas (Queues):** Listado de las colas de atención sincronizadas con Cisco.
- **Canales:** Identifica si la interacción fue por Voz, Chat o Correo.
- **Subtipos de Caso:** Clasificación detallada del motivo de la llamada para generar estadísticas de trámites.

---

## ⚡ Sincronización Técnica
- **Agentes y Equipos:** Permite importar la estructura de supervisión directamente desde Cisco Finesse para evitar doble carga de datos.
- **Procesos ETL:** Tareas automáticas que se ejecutan en segundo plano cada pocos minutos para actualizar los KPIs de rendimiento.

---

## ⚠️ Guía para la Integración
1. **Credenciales Cisco:** Asegúrate de que el agente use el mismo nombre de usuario en ambas plataformas para que el sistema pueda vincular sus métricas correctamente.
2. **Tipificación a Tiempo:** Se recomienda tipificar la llamada inmediatamente después de terminar (en el tiempo de ACW) para no perder la trazabilidad del ciudadano.
3. **Estado de la Conexión:** Si notas que el Dashboard no se actualiza, verifica el estado de los servicios de sincronización con el equipo de Workforce.
