# AuditModule

 Pendiente (no crítico)

1. Ruta legacy admin/audit sigue activa en config/modules.php (convivencia Strangler Fig). Cuando se quiera migrar por completo, se actualiza el redirect en web.php global o se reemplaza la ruta legacy.
2. Listener para Src DDD events está comentado en el provider (AuditSrcWeeklySchedulePublishedListener). Descomentar cuando el contexto Src/Wfm esté activo en producción.
3. Tests para app/Src/Platform — existen 66 tests para el módulo legacy. Faltan tests unitarios para los handlers/DTOs/entities nuevos.
Las rutas nuevas están en admin/platform/audit (sin conflicto con las legacy en admin/audit).ringing

## CommunicationsModule

 Bridge y Convivencia
Los módulos legacy (app/Modules/AuditModule/, app/Modules/CommunicationsModule/) siguen activos en config/modules.php. El Strangler Fig pattern permite que ambos coexistan.
Los eventos App\Shared\Events\* son escuchados TANTO por los listeners legacy como por los nuevos de Platform — sin pérdida de eventos.
AuditLogBridge ya es usado por el trait Auditable en lugar del legacy AuditLog::log().
Las rutas legacy (admin/audit, admin/communications) y las nuevas (admin/platform/audit, admin/platform/communications) operan en paralelo.

## ConnectModule

## CoreModule

## DocumentationModule

## FilesystemModule

## HelpdeskModule

## KnowledgeModule

## OperationsModule

## PersonnelModule

## QualityModule

## SupportModule

## WfmModule

## WorkflowsModule
