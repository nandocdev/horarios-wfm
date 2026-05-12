# 🚀 WFM — Call Center CSS
## Sistema de Gestión y Optimización del Talento Humano

> **Transformando la operatividad del Call Center de la Caja de Seguro Social de Panamá a través de la tecnología.**

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![Modular Monolith](https://img.shields.io/badge/Architecture-Modular_Monolith-blue)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)

---

## 🌟 La Visión
El sistema **WFM (Workforce Management)** nace para profesionalizar la gestión de turnos y el rendimiento operativo. Lo que antes era un laberinto de hojas de cálculo y correos electrónicos, hoy es un ecosistema digital integrado que garantiza transparencia, eficiencia y equidad para cada operador y coordinador del Call Center.

### 🎯 Objetivos Clave
*   **Precisión Operativa:** Planificación semanal automatizada libre de errores humanos.
*   **Visibilidad en Tiempo Real:** Monitoreo activo de adherencia y estados de los agentes.
*   **Empoderamiento del Colaborador:** Autogestión de permisos, vacaciones y cambios de turno.
*   **Trazabilidad Total:** Auditoría inmutable de cada decisión administrativa.

---

## 🏗️ Arquitectura de Vanguardia: El Monolito Modular
A diferencia de los sistemas tradicionales, WFM está construido bajo un enfoque de **Monolito Modular**. Esto nos da la robustez de una aplicación centralizada con la agilidad y el desacoplamiento de los microservicios.

### Nuestros Dominios Principales

| Módulo | Descripción | Propósito |
| :--- | :--- | :--- |
| **🛡️ Personnel** | Núcleo de Capital Humano | Centraliza la jerarquía organizacional, posiciones y datos del personal. |
| **🛰️ Connect** | Integración Cisco UCCX | Telemetría en tiempo real y sincronización de estados y desempeño histórico. |
| **📅 WFM** | Motor de Planificación | Gestión de horarios base, actividades intradía y excepciones programadas. |
| **⚙️ Operations** | Dashboard de Desempeño | Cálculo avanzado de métricas (Productividad vs Utilización) y monitoreo de adherencia. |
| **🤝 Workflows** | Flujos de Aprobación | Procesamiento inteligente de solicitudes de permisos y cambios de turno. |

---

## 📊 Métricas que Importan
Nuestro sistema no solo cuenta llamadas; analiza el comportamiento para mejorar la toma de decisiones:

*   **Productividad:** Eficiencia del agente mientras está conectado.
*   **Utilización WFM:** Cumplimiento real frente a la jornada pagada y programada.
*   **Adherencia:** Comparación instantánea entre el estado real (Cisco) y el planificado (WFM).

---

## 🛠️ Stack Tecnológico
*   **Backend:** PHP 8.5+ con Laravel 13.
*   **Frontend:** Livewire (Componentes reactivos sin salir de PHP) y Flux UI.
*   **Base de Datos:** PostgreSQL 16 con tipos de datos avanzados (Rangos de tiempo).
*   **Infraestructura:** Preparado para despliegue en entornos institucionales de alta seguridad.

---

## 🚀 Inicio Rápido para Desarrolladores

Si eres parte del equipo de ingeniería, sigue estos pasos para desplegar tu entorno local:

```bash
# 1. Clonar y preparar dependencias
git clone https://github.com/nandocdev/horarios-wfm.git
composer install
npm install && npm run build

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate

# 3. Base de datos y Semillas
php artisan migrate --seed
```

---

## 🔒 Seguridad y Privacidad
El sistema implementa un modelo de **RBAC (Role-Based Access Control)** estricto, asegurando que la información sensible del personal solo sea visible para los niveles jerárquicos autorizados (Jefaturas y Directores).

---

## 📄 Licencia
Software propietario desarrollado para la **Caja de Seguro Social de Panamá**.
Todos los derechos reservados. 2026.
