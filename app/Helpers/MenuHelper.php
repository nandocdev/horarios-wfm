<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class MenuHelper
{
    protected static $currentUser = null;

    public static function getSidebarItems($user = null, array $counts = []): Collection
    {
        self::$currentUser = $user ?: AuthFacade::user();

        $items = self::buildItems();

        return collect(self::filterByPermission(self::markActive($items)));
    }

    // ─────────────────────────────────────────────────────────
    // Construcción del árbol
    // ─────────────────────────────────────────────────────────

    private static function buildItems(): array
    {
        return [

            // 📊 Dashboard
            [
                'label' => __('Dashboard'),
                'icon' => 'home',
                'route' => 'dashboard',
                'pattern' => 'dashboard*',
            ],

            // 🗓 Mi Trabajo
            [
                'label' => __('Mi Trabajo'),
                'icon' => 'user',
                'submenu' => [
                    ['label' => __('Mi Horario'), 'route' => 'schedules.my-schedule', 'pattern' => '/schedules/my-schedule*', 'icon' => 'calendar'],
                    ['label' => __('Mi Jornada'), 'route' => 'schedules.my-day', 'pattern' => 'schedules/my-day*', 'icon' => 'clock'],
                    ['label' => __('Solicitar Permiso'), 'route' => 'schedules.leave-request', 'pattern' => 'schedules/leave-request*', 'icon' => 'document-plus'],
                    ['label' => __('Solicitar Cambio de Turno'), 'route' => 'schedules.swap-request', 'pattern' => 'schedules/swap-request*', 'icon' => 'arrows-right-left'],
                    ['label' => __('Mis Solicitudes'), 'route' => 'schedules.swap-history', 'pattern' => 'schedules/swap-history*', 'icon' => 'inbox'],
                    ['label' => __('Notificaciones'), 'route' => 'notifications.index', 'pattern' => 'notifications*', 'icon' => 'bell'],
                ],
            ],

            // 👥 Mi Equipo
            [
                'label' => __('Mi Equipo'),
                'icon' => 'users',
                'permission' => 'schedules.view_team',
                'submenu' => [
                    ['label' => __('Mi Equipo'), 'route' => 'schedules.my-team', 'pattern' => 'schedules/my-team*', 'permission' => 'schedules.view_team', 'icon' => 'user-group'],
                    ['label' => __('Aprobar Permisos'), 'route' => 'schedules.manager-approvals', 'pattern' => 'schedules/manager-approvals*', 'permission' => 'schedules.approve_requests', 'icon' => 'check-badge'],
                    ['label' => __('Resumen de Solicitudes'), 'route' => 'schedules.request-summary', 'pattern' => 'schedules/request-summary*', 'permission' => 'schedules.view_team', 'icon' => 'clipboard-document'],
                ],
            ],

            // 📋 Planificación
            [
                'label' => __('Planificación'),
                'icon' => 'calendar-days',
                'permission' => 'schedules.view_all',
                'submenu' => [
                    ['label' => __('Planificación Semanal'), 'route' => 'schedules.planning', 'pattern' => 'schedules/planning*', 'permission' => 'schedules.manage', 'icon' => 'calendar-date-range'],
                    ['label' => __('Turnos Base'), 'route' => 'schedules.shifts', 'pattern' => 'schedules/shifts*', 'permission' => 'schedules.manage', 'icon' => 'clock'],
                    ['label' => __('Actividades Intradía'), 'route' => 'schedules.intraday-activities.manage', 'pattern' => 'schedules/intraday-activities*', 'permission' => 'wfm.intraday.manage', 'icon' => 'list-bullet'],
                    ['label' => __('Actividades Programadas'), 'route' => 'schedules.scheduled-activities', 'pattern' => 'schedules/scheduled-activities*', 'permission' => 'wfm.catalogs.scheduled_defs', 'icon' => 'queue-list'],
                    ['label' => __('Excepciones de Horario'), 'route' => 'schedules.exceptions', 'pattern' => 'schedules/exceptions*', 'permission' => 'wfm.exceptions.manage', 'icon' => 'exclamation-triangle'],
                    ['label' => __('Tipos de Actividad'), 'route' => 'schedules.activity-types', 'pattern' => 'schedules/activity-types*', 'permission' => 'wfm.catalogs.activities', 'icon' => 'tag'],
                    ['label' => __('Motivos de Ausencia'), 'route' => 'schedules.absence-reasons', 'pattern' => 'schedules/absence-reasons*', 'permission' => 'wfm.catalogs.absences', 'icon' => 'folder-minus'],
                    ['label' => __('Estados de Agente'), 'route' => 'schedules.agent-states', 'pattern' => 'schedules/agent-states*', 'permission' => 'wfm.catalogs.agent_states', 'icon' => 'signal'],
                    ['label' => __('Aprobar Cambios de Turno'), 'route' => 'schedules.wfm-approvals', 'pattern' => 'schedules/wfm-approvals*', 'permission' => 'wfm.swaps.manage', 'icon' => 'check-badge'],
                ],
            ],

            // 🔄 Operaciones
            [
                'label' => __('Operaciones'),
                'icon' => 'chart-bar-square',
                'permission' => 'operations.view',
                'submenu' => [
                    ['label' => __('Monitoreo en Tiempo Real'), 'route' => 'operations.realtime', 'pattern' => 'operations/realtime*', 'permission' => 'wfm.realtime.view', 'icon' => 'cpu-chip'],
                    ['label' => __('Reporte Diario'), 'route' => 'operations.daily-report', 'pattern' => 'operations/reporte-diario*', 'icon' => 'clipboard-document-list'],
                    ['label' => __('Disponibilidad Intradía'), 'route' => 'operations.availability', 'pattern' => 'operations/availability*', 'icon' => 'clock'],
                    ['label' => __('Desempeño por Cola'), 'route' => 'operations.queue-performance', 'pattern' => 'operations/queue-performance*', 'icon' => 'phone'],
                    ['label' => __('Scorecard de Desempeño'), 'route' => 'operations.performance', 'pattern' => 'operations/performance*', 'icon' => 'chart-bar'],
                    ['label' => __('Dashboard de Agente'), 'route' => 'operations.agent-performance', 'pattern' => 'operations/agent-performance*', 'icon' => 'presentation-chart-bar'],
                    ['label' => __('Dashboard de Productividad'), 'route' => 'operations.advanced-analytics', 'pattern' => 'operations/advanced-analytics*', 'icon' => 'chart-pie'],
                    ['label' => __('Resumen por Equipo'), 'route' => 'operations.team-performance', 'pattern' => 'operations/team-performance*', 'permission' => 'schedules.view_team', 'icon' => 'user-group'],
                    ['label' => __('Marco de Reportes'), 'route' => 'operations.reports', 'pattern' => 'operations/reports*', 'icon' => 'document-text'],
                ],
            ],

            // ⭐ Calidad
            [
                'label' => __('Calidad'),
                'icon' => 'clipboard-document-check',
                'permission' => 'quality.evaluations.view',
                'submenu' => [
                    ['label' => __('Evaluaciones'), 'route' => 'quality.evaluations.index', 'pattern' => 'quality/evaluaciones*', 'icon' => 'list-bullet'],
                    ['label' => __('Nueva Evaluación'), 'route' => 'quality.evaluations.create', 'pattern' => 'quality/evaluaciones/crear*', 'permission' => 'quality.evaluations.create', 'icon' => 'plus-circle'],
                    ['label' => __('Criterios'), 'route' => 'quality.criteria.index', 'pattern' => 'quality/criterios*', 'permission' => 'quality.criteria.view', 'icon' => 'clipboard-document'],
                    ['label' => __('Criterios por Cola'), 'route' => 'quality.queues.criteria', 'pattern' => 'quality/colas/criterios*', 'permission' => 'quality.criteria.view', 'icon' => 'list-bullet'],
                    ['label' => __('Colas'), 'route' => 'quality.queues.index', 'pattern' => 'quality/colas*', 'permission' => 'quality.queues.manage', 'icon' => 'queue-list'],
                ],
            ],

            // 📞 Centro de Contacto
            [
                'label' => __('Centro de Contacto'),
                'icon' => 'phone',
                'permission' => 'call_records.viewAny',
                'submenu' => [
                    ['label' => __('Llamadas'), 'route' => 'contact-center.calls.index', 'pattern' => 'contact-center/calls*', 'permission' => 'call_records.viewAny', 'icon' => 'phone-arrow-down-left'],
                    ['label' => __('Colas'), 'route' => 'contact-center.admin.queues.index', 'pattern' => 'contact-center/catalogs/queues*', 'permission' => 'call_queues.manage', 'icon' => 'queue-list'],
                    ['label' => __('Canales'), 'route' => 'contact-center.admin.channels.index', 'pattern' => 'contact-center/catalogs/channels*', 'permission' => 'channels.manage', 'icon' => 'signal'],
                    ['label' => __('Subtipos de Caso'), 'route' => 'contact-center.admin.subtypes.index', 'pattern' => 'contact-center/catalogs/subtypes*', 'permission' => 'case_subtypes.manage', 'icon' => 'tag'],
                ],
            ],

            // 📢 Comunicaciones
            [
                'label' => __('Comunicaciones'),
                'icon' => 'megaphone',
                'submenu' => [
                    ['label' => __('Inicio'), 'route' => 'home', 'pattern' => '/', 'icon' => 'newspaper'],
                    ['label' => __('Noticias'), 'route' => 'communications.news.index', 'pattern' => 'admin/communications/news*', 'permission' => 'news.create', 'icon' => 'document-text'],
                    ['label' => __('Encuestas'), 'route' => 'communications.polls.index', 'pattern' => 'admin/communications/polls*', 'permission' => 'polls.manage', 'icon' => 'chart-bar'],
                    ['label' => __('Reconocimientos'), 'route' => 'communications.shoutouts.index', 'pattern' => 'admin/communications/shoutouts*', 'permission' => 'shoutouts.manage', 'icon' => 'hand-thumb-up'],
                ],
            ],

            // 🎫 Soporte
            [
                'label' => __('Soporte'),
                'icon' => 'lifebuoy',
                'permission' => 'helpdesk.view',
                'submenu' => [
                    ['label' => __('Mis Tickets'), 'route' => 'helpdesk.my-tickets', 'pattern' => 'helpdesk/my-tickets*', 'permission' => 'helpdesk.view', 'icon' => 'ticket'],
                    ['label' => __('Bandeja de Soporte'), 'route' => 'helpdesk.manage', 'pattern' => 'helpdesk/manage*', 'permission' => 'helpdesk.manage', 'icon' => 'inbox'],
                    ['label' => __('Base de Conocimiento'), 'route' => 'knowledge.index', 'pattern' => 'knowledge*', 'permission' => 'knowledge.viewAny', 'icon' => 'book-open'],
                ],
            ],

            // 📚 Documentación
            [
                'label' => __('Documentación'),
                'icon' => 'book-open-text',
                'submenu' => [
                    ['label' => __('Artículos'), 'route' => 'documentation.index', 'pattern' => 'docs*', 'icon' => 'document-text'],
                    ['label' => __('Administrar Artículos'), 'route' => 'documentation.admin.articles', 'pattern' => 'admin/documentation*', 'icon' => 'pencil-square'],
                ],
            ],

            // 🗃 Archivos
            [
                'label' => __('Archivos'),
                'icon' => 'folder',
                'submenu' => [
                    ['label' => __('Explorador de Archivos'), 'route' => 'filesystem.index', 'pattern' => 'filesystem*', 'icon' => 'folder-open'],
                    ['label' => __('Centro de Descargas'), 'route' => 'filesystem.download-center', 'pattern' => 'descargas*', 'icon' => 'folder-arrow-down'],
                    ['label' => __('Cuotas de Almacenamiento'), 'route' => 'filesystem.quotas', 'pattern' => 'filesystem/quotas*', 'icon' => 'server'],
                ],
            ],

            // ⚙️ Administración
            [
                'label' => __('Administración'),
                'icon' => 'cog-6-tooth',
                'submenu' => [
                    [
                        'label' => __('Empleados'),
                        'permission' => 'employees.view',
                        'icon' => 'identification',
                        'submenu' => [
                            ['label' => __('Listar Empleados'), 'route' => 'employees.index', 'pattern' => 'employees*', 'permission' => 'employees.view', 'icon' => 'user-group'],
                            ['label' => __('Crear Empleado'), 'route' => 'employees.create', 'pattern' => 'employees/create*', 'permission' => 'employees.create', 'icon' => 'user-plus'],
                            ['label' => __('Importar Empleados'), 'route' => 'employees.import', 'pattern' => 'employees/import*', 'permission' => 'employees.import', 'icon' => 'arrow-up-tray'],
                        ],
                    ],
                    [
                        'label' => __('Organigrama'),
                        'permission' => 'directorates.viewAny',
                        'icon' => 'building-office',
                        'submenu' => [
                            ['label' => __('Direcciones'), 'route' => 'organization.directorates.index', 'pattern' => 'organization/directorates*', 'permission' => 'directorates.viewAny', 'icon' => 'building-library'],
                            ['label' => __('Departamentos'), 'route' => 'organization.departments.index', 'pattern' => 'organization/departments*', 'permission' => 'departments.viewAny', 'icon' => 'building-office-2'],
                            ['label' => __('Cargos'), 'route' => 'organization.positions.index', 'pattern' => 'organization/positions*', 'permission' => 'positions.viewAny', 'icon' => 'briefcase'],
                        ],
                    ],
                    ['label' => __('Equipos'), 'route' => 'organization.teams.index', 'pattern' => 'organization/teams*', 'permission' => 'teams.viewAny', 'icon' => 'users'],
                    ['label' => __('Ubicaciones'), 'route' => 'location.index', 'pattern' => 'location*', 'icon' => 'map-pin'],
                    ['label' => __('Usuarios'), 'route' => 'users.index', 'pattern' => 'admin/users*', 'permission' => 'users.view', 'icon' => 'user-circle'],
                    ['label' => __('Roles y Permisos'), 'route' => 'roles.index', 'pattern' => 'admin/roles*', 'permission' => 'roles.view', 'icon' => 'shield-check'],
                    ['label' => __('Configuración Operativa'), 'route' => 'schedules.operational-settings', 'pattern' => 'schedules/operational-settings*', 'permission' => 'wfm.settings.manage', 'icon' => 'cog'],
                    ['label' => __('Auditoría'), 'route' => 'audit.index', 'pattern' => 'admin/audit*', 'permission' => 'audit.view', 'icon' => 'clipboard-document-list'],
                    ['label' => __('Categorías y Etiquetas'), 'route' => 'communications.admin.categories.index', 'pattern' => 'admin/communications/categories*', 'permission' => 'communications.manage', 'icon' => 'tag'],
                    ['label' => __('Moderación de Contenido'), 'route' => 'communications.moderation.index', 'pattern' => 'admin/communications/moderation*', 'permission' => 'communications.moderate', 'icon' => 'shield-exclamation'],
                    ['label' => __('Reportes de Personal'), 'route' => 'personnel.staffing-summary', 'pattern' => 'personnel/reports*', 'permission' => 'reports.staffing', 'icon' => 'presentation-chart-line'],
                    ['label' => __('Mantenimiento del Sistema'), 'route' => 'admin.system.maintenance', 'pattern' => 'admin/system/maintenance*', 'icon' => 'wrench-screwdriver'],
                ],
            ],
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Marcar elemento activo según la ruta actual
    // ─────────────────────────────────────────────────────────

    private static function markActive(array $items): array
    {
        $currentRoute = RequestFacade::route()?->getName();
        $currentPath = RequestFacade::path();

        foreach ($items as &$item) {
            $isActive = false;

            if (isset($item['route']) && $item['route'] === $currentRoute) {
                $isActive = true;
            }

            if (isset($item['pattern'])) {
                $patterns = explode('|', $item['pattern']);
                foreach ($patterns as $pattern) {
                    if (Str::is($pattern, $currentPath) || str_starts_with($currentPath, rtrim($pattern, '*'))) {
                        $isActive = true;
                        break;
                    }
                }
            }

            if (isset($item['submenu'])) {
                $item['submenu'] = self::markActive($item['submenu']);
                if (! $isActive) {
                    foreach ($item['submenu'] as $sub) {
                        if ($sub['is_active'] ?? false) {
                            $isActive = true;
                            break;
                        }
                    }
                }
            }

            $item['is_active'] = $isActive;
        }

        return $items;
    }

    // ─────────────────────────────────────────────────────────
    // Filtrar por permisos del usuario
    // ─────────────────────────────────────────────────────────

    private static function filterByPermission(array $items): array
    {
        $user = self::$currentUser;

        if (! $user) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) use ($user) {
            if (isset($item['permission'])) {
                try {
                    $can = $user->hasPermissionTo($item['permission']);
                } catch (PermissionDoesNotExist) {
                    $can = false;
                }
                if (! $can) {
                    return null;
                }
            }

            if (isset($item['submenu'])) {
                $item['submenu'] = self::filterByPermission($item['submenu']);
                if (empty($item['submenu'])) {
                    return null;
                }
            }

            return $item;
        }, $items)));
    }

    public static function getFooterItems($user = null): array
    {
        return [
            [
                'label' => __('Configuración'),
                'icon' => 'cog',
                'route' => 'profile.edit',
                'is_active' => request()->routeIs('profile.edit'),
            ],
            [
                'label' => __('Cerrar Sesión'),
                'icon' => 'arrow-right-start-on-rectangle',
                'route' => 'logout',
                'is_active' => false,
            ],
        ];
    }
}
