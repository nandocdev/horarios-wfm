---
tipo: decisiones
proyecto: "HorariosWFM"
estado: activo
fecha: 2026-08-12
tags:
  - proyecto
  - adr
  - decisiones
---

# 05 — Decisiones (ADRs)

## Cómo usar este archivo
- Cada decisión importante tiene un ID estable (`ADR-001`, `ADR-002`…).
- Estados posibles: `Propuesta` · `Aceptada` · `Deprecada` · `Superada`
- Sé explícito en el **contexto** y en las **consecuencias**. Una decisión sin trade-offs documentados es casi inútil.
- Las decisiones arquitectónicas relevantes también se enlazan desde [[03-Arquitectura]].

---

## ADR-001 — Adopción de Monolito Modular
**Estado**: Aceptada  
**Fecha**: 2026-08-12  
**Decisores**: Arquitecto de Software, Tech Lead  

### Contexto
El sistema `HorariosWFM` maneja 15 dominios funcionales (Horarios, Operaciones, Cisco, QA, Comunicaciones). Se necesitaba una arquitectura que promoviera el desacoplamiento sin la complejidad de infraestructura que traen los microservicios.

### Opciones consideradas
1. **Microservicios** — 
   - Pros: Escalabilidad independiente, despliegues aislados.
   - Contras: Sobrecarga operativa severa (Service mesh, orquestación), transacciones distribuidas complejas, equipo pequeño de operaciones IT en la CSS.
2. **Monolito Modular** — 
   - Pros: Una sola base de datos, un solo despliegue, comunicación rápida en memoria. Módulos aislados por estructura de carpetas y eventos.
   - Contras: Requiere estricta disciplina de equipo para no romper los límites del módulo.

### Decisión
Se eligió el **Monolito Modular** (Laravel 13). La comunicación inter-módulo se hace exclusivamente vía **Shared Events, Contracts y DTOs**. Se prohíbe importar modelos (`App\Modules\X\Models\...`) directamente desde otro módulo.

### Consecuencias
**Positivas**
- Despliegue simple (`composer install` y systemd).
- Refactorización segura.

**Negativas / Costos**
- Mayor fricción inicial en el desarrollo (crear DTOs y Contracts en lugar de consultas directas).

**Riesgos introducidos**
- Si la disciplina decae, el código se transformará en un Big Ball of Mud (Espagueti).

---

## ADR-002 — Identidad mediante ULID
**Estado**: Aceptada  
**Fecha**: 2026-08-12  
**Decisores**: Arquitecto de Software, DBA  

### Contexto
El sistema requiere llaves primarias impredecibles (seguridad por oscuridad en URLs/APIs), aptas para sistemas distribuidos y que puedan ser generadas por el cliente o antes de la persistencia (Livewire Forms).

### Opciones consideradas
1. **Auto-Incremental ID** — 
   - Pros: Máximo rendimiento, indexación óptima.
   - Contras: Expone volumen de negocio (ej: saber cuántas ausencias hay) e IDs adivinables (Insecure Direct Object Reference).
2. **UUID v4** — 
   - Pros: Estándar y globalmente único.
   - Contras: Aleatoriedad pura causa fragmentación severa en los índices B-Tree, penalizando rendimiento de INSERTs en escala.
3. **ULID (Universally Unique Lexicographically Sortable Identifier)** — 
   - Pros: Aleatorio pero ordenable por tiempo. Combina los beneficios del B-Tree y la imprevisibilidad.

### Decisión
Se eligió **ULID** mediante el uso del trait `HasUlids` en un `BaseModel` abstracto heredado por todos los modelos del sistema.

### Consecuencias
**Positivas**
- Indexación eficiente en PostgreSQL 16 similar al incremento secuencial.
- URLs amigables y seguras.

**Riesgos introducidos**
- Ligeramente más consumo de almacenamiento en disco comparado con Integers.

---

## ADR-003 — Polling Asíncrono para Cisco Finesse
**Estado**: Aceptada  
**Fecha**: 2026-08-12  
**Decisores**: Equipo Backend, Redes / Telefonía Cisco  

### Contexto
Se requiere conocer los estados de los agentes (Conectado, En Pausa, En Llamada) en tiempo real para el cálculo de Adherencia en OperationsModule. Cisco Finesse on-premise no expone Webhooks confiables hacia servidores externos.

### Opciones consideradas
1. **Polling tradicional vía Cron** — 
   - Pros: Simple.
   - Contras: La granularidad mínima del cron en Linux es de 1 minuto. Inaceptable para tiempo real.
2. **Job Auto-despachante (Self-Dispatching) en Horizon** — 
   - Pros: Un Job que, tras terminar, se empuja a sí mismo de vuelta a la cola con un delay (ej: 5s). Escala infinitamente.
   - Contras: Consume un worker permanentemente. Riesgo de "Dead cycle" si lanza excepción y muere, rompiendo la cadena diaria.

### Decisión
Implementar un **Job Auto-despachante (`CiscoSync`) cada 5 segundos** durante la jornada laboral. 

### Consecuencias
**Positivas**
- Datos casi en tiempo real (retraso máx de 5s).
- Horizon maneja las anomalías.

**Riesgos introducidos**
- Se documenta explícitamente la necesidad de implementar *Backoff Exponencial* y *Circuit Breaker* en el cliente HTTP. Si Cisco cae, el job intentaba seguir atacando el servidor. Se corrigió agregando mitigaciones preventivas.

---

## ADR-004 — Bypass Global para Super-Admin
**Estado**: Aceptada  
**Fecha**: 2026-08-12  
**Decisores**: Backend  

### Contexto
Existen más de 130 permisos granulares registrados con Spatie Permission. Gestionar los permisos del usuario administrador agregándolos manualmente en la BD es propenso a errores (olvidar añadir un permiso nuevo rompe flujos del Admin).

### Decisión
Uso de `Gate::before()` en el `AppServiceProvider`. Si el usuario tiene el rol `admin` (nivel jerárquico 99), siempre retorna `true`.

### Consecuencias
**Positivas**
- Garantía de que un Super-Admin jamás quede bloqueado.

**Negativas / Costos**
- Cualquier vulnerabilidad que escale privilegios a `admin` otorga control destructivo total sobre el sistema. Se mitiga requiriendo 2FA estricto (TOTP).

---

## ADR-005 — Uso de Tablas UNLOGGED para Tiempo Real
**Estado**: Aceptada  
**Fecha**: 2026-08-12  
**Decisores**: DBA  

### Contexto
La tabla `agent_realtime_states` recibe cientos de `updateOrInsert` cada 5 segundos. Escribir esta volatilidad en el Write-Ahead Log (WAL) de PostgreSQL degrada el disco I/O innecesariamente.

### Decisión
Convertir `agent_realtime_states` en una tabla `UNLOGGED`.

### Consecuencias
**Positivas**
- Mejora de rendimiento de escritura del 300% al evitar el WAL.

**Negativas / Costos**
- Si el servidor de BD se reinicia inesperadamente o se apaga, la tabla se vacía. Al ser un caché en vivo del Call Center, es aceptable: el sistema la re-poblará a los 5 segundos en el siguiente polling.

---

**Relacionado**
- [[02-Requisitos]]
- [[03-Arquitectura]]
- [[06-Riesgos]]
- [[00-Index]]