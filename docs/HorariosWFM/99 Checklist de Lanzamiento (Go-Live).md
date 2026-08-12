---
tipo: checklist
proyecto: "{{Nombre del Proyecto}}"
fecha: {{date}}
tags: [operaciones, lanzamiento, prod]
---

# 🚀 Checklist de Lanzamiento (Go-Live)

## 🛠 Infraestructura y Configuración
- [ ] Variables de entorno de producción configuradas.
- [ ] Certificados SSL activos y validados.
- [ ] Backups de base de datos automatizados y probados.
- [ ] Logs y monitoreo activos (Sentry, Cloudwatch, etc).

## 🔒 Seguridad
- [ ] Secrets rotados (no son los de staging).
- [ ] Firewall / Grupos de seguridad cerrados (solo puertos necesarios).
- [ ] Usuarios de DB con permisos mínimos (no root/admin).

## 🧪 Verificación Final (Smoke Tests)
- [ ] El flujo de Login funciona en prod.
- [ ] Los pagos/procesos críticos funcionan.
- [ ] Las integraciones con terceros están en modo "Live".

## 📦 Plan de Rollback
<!-- ¿Qué hacemos si todo sale mal? -->
1. Comando de reversión:
2. Persona de contacto técnica:

---
**Relacionado**: [[03-Arquitectura]] | [[Gestion/Plan]]
