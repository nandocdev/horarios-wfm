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
                    ['label' => __('Mi Horario'), 'route' => 'schedules.my-schedule', 'pattern' => 'schedules/my-schedule*', 'icon' => 'calendar'],
                    ['label' => __('Mi Jornada'), 'route' => 'schedules.my-day', 'pattern' => 'schedules/my-day*', 'icon' => 'clock'],
                    ['label' => __('Solicitar Permiso'), 'route' => 'schedules.leave-request', 'pattern' => 'schedules/leave-request*', 'icon' => 'document-plus'],
                    ['label' => __('Solicitar Cambio de Turno'), 'route' => 'schedules.swap-request', 'pattern' => 'schedules/swap-request*', 'icon' => 'arrows-right-left'],
                    ['label' => __('Historial de Permisos'), 'route' => 'schedules.leave-history', 'pattern' => 'schedules/leave-history*', 'icon' => 'clipboard-document-list'],
                    ['label' => __('Historial de Cambios'), 'route' => 'schedules.swap-history', 'pattern' => 'schedules/swap-history*', 'icon' => 'inbox'],
                ],
            ],

            // 👥 Mi Equipo
            [
                'label' => __('Mi Equipo'),
                'icon' => 'users',
                'permission' => 'schedules.view_team',
                'submenu' => [
                    ['label' => __('Dashboard del Equipo'), 'route' => 'schedules.team-dashboard', 'pattern' => 'schedules/team-dashboard*', 'permission' => 'schedules.view_team', 'icon' => 'presentation-chart-bar'],
                    ['label' => __('Miembros'), 'route' => 'schedules.my-team', 'pattern' => 'schedules/my-team*', 'permission' => 'schedules.view_team', 'icon' => 'user-group'],
                    ['label' => __('Aprobar Permisos'), 'route' => 'schedules.manager-approvals', 'pattern' => 'schedules/manager-approvals*', 'permission' => 'schedules.approve_requests', 'icon' => 'check-badge'],
                    ['label' => __('Resumen de Solicitudes'), 'route' => 'schedules.request-summary', 'pattern' => 'schedules/reports/requests*', 'permission' => 'schedules.view_team', 'icon' => 'clipboard-document'],
                    ['label' => __('Excepciones'), 'route' => 'schedules.exceptions', 'pattern' => 'schedules/exceptions*', 'permission' => ['schedules.view_team', 'wfm.exceptions.manage'], 'icon' => 'exclamation-triangle'],
                ],
            ],

            // 📋 Planificación
            [
                'label' => __('Planificación'),
                'icon' => 'calendar-days',
                'permission' => 'schedules.view_all',
                'submenu' => [
                    ['label' => __('Planificación Semanal'), 'route' => 'schedules.planning', 'pattern' => 'schedules/planning*', 'permission' => 'schedules.manage', 'icon' => 'calendar-date-range'],
                    ['label' => __('Forecast'), 'route' => 'operations.forecast', 'pattern' => 'operations/forecast*', 'icon' => 'chart-bar'],
                    ['label' => __('Dotación'), 'route' => 'operations.staffing', 'pattern' => 'operations/staffing*', 'icon' => 'users'],
                    ['label' => __('Capacidad'), 'route' => 'operations.capacity', 'pattern' => 'operations/capacity*', 'icon' => 'chart-bar'],
                    ['label' => __('Merma'), 'route' => 'operations.shrinkage', 'pattern' => 'operations/shrinkage*', 'icon' => 'clock'],
                    ['label' => __('Escenarios'), 'route' => 'operations.scenarios', 'pattern' => 'operations/scenarios*', 'icon' => 'arrow-right-circle'],
                    ['label' => __('Turnos Base'), 'route' => 'schedules.shifts', 'pattern' => 'schedules/shifts*', 'permission' => 'schedules.manage', 'icon' => 'clock'],
                    ['label' => __('Actividades Intradía'), 'route' => 'schedules.intraday-activities.manage', 'pattern' => 'schedules/intraday-activities*', 'permission' => 'wfm.intraday.manage', 'icon' => 'list-bullet'],
                    ['label' => __('Actividades Programadas'), 'route' => 'schedules.scheduled-activities', 'pattern' => 'schedules/scheduled-activities*', 'permission' => 'wfm.catalogs.scheduled_defs', 'icon' => 'queue-list'],
                    ['label' => __('Aprobar Cambios'), 'route' => 'schedules.wfm-approvals', 'pattern' => 'schedules/wfm-approvals*', 'permission' => 'wfm.swaps.manage', 'icon' => 'check-badge'],
                    ['label' => __('Catálogos'), 'route' => 'schedules.activity-types', 'pattern' => 'schedules/activity-types|schedules/absence-reasons|schedules/agent-states*', 'permission' => 'wfm.catalogs.activities', 'icon' => 'tag'],
                    ['label' => __('Configuración Operativa'), 'route' => 'schedules.operational-settings', 'pattern' => 'schedules/operational-settings*', 'permission' => 'wfm.settings.manage', 'icon' => 'cog'],
                ],
            ],

            // 🔄 Operación
            [
                'label' => __('Operación'),
                'icon' => 'chart-bar-square',
                'permission' => 'operations.view',
                'submenu' => [
                    ['label' => __('Torre de Control'), 'route' => 'operations.dashboard', 'pattern' => 'operations/dashboard|operations/control-tower*', 'icon' => 'presentation-chart-line'],
                    ['label' => __('Tiempo Real'), 'route' => 'operations.realtime', 'pattern' => 'operations/realtime*', 'permission' => 'wfm.realtime.view', 'icon' => 'cpu-chip'],
                    ['label' => __('Disponibilidad'), 'route' => 'operations.availability', 'pattern' => 'operations/availability*', 'icon' => 'clock'],
                    ['label' => __('Estado de Colas'), 'route' => 'operations.queues', 'pattern' => 'operations/queues|operations/queue-performance*', 'icon' => 'phone'],
                    ['label' => __('Intervalos'), 'route' => 'operations.intervals', 'pattern' => 'operations/intervals*', 'icon' => 'clock'],
                    ['label' => __('Llamadas en Vivo'), 'route' => 'operations.calls', 'pattern' => 'operations/calls*', 'icon' => 'phone-arrow-down-left'],
                ],
            ],

            // 📊 Analítica
            [
                'label' => __('Analítica'),
                'icon' => 'chart-bar',
                'permission' => 'analytics.view',
                'submenu' => [
                    ['label' => __('KPIs'), 'route' => 'operations.kpis', 'pattern' => 'operations/kpis*', 'icon' => 'chart-bar'],
                    ['label' => __('Tendencias'), 'route' => 'operations.trends', 'pattern' => 'operations/trends*', 'icon' => 'arrow-trending-up'],
                    ['label' => __('Skills'), 'route' => 'operations.skills', 'pattern' => 'operations/skills*', 'icon' => 'academic-cap'],
                    ['label' => __('Comparativos'), 'route' => 'operations.comparison', 'pattern' => 'operations/comparison*', 'icon' => 'arrows-right-left'],
                    ['label' => __('Explorador de Datos'), 'route' => 'operations.explorer', 'pattern' => 'operations/explorer*', 'icon' => 'magnifying-glass-circle'],
                    [
                        'label' => __('Desempeño'),
                        'icon' => 'presentation-chart-line',
                        'submenu' => [
                            ['label' => __('Scorecard'), 'route' => 'operations.performance', 'pattern' => 'operations/performance*', 'icon' => 'chart-bar'],
                            ['label' => __('Dashboard de Agente'), 'route' => 'operations.agent-performance', 'pattern' => 'operations/agent-performance*', 'icon' => 'presentation-chart-bar'],
                            ['label' => __('Resumen por Equipo'), 'route' => 'operations.team-performance', 'pattern' => 'operations/team-performance*', 'permission' => 'schedules.view_team', 'icon' => 'user-group'],
                            ['label' => __('Productividad'), 'route' => 'operations.advanced-analytics', 'pattern' => 'operations/advanced-analytics*', 'icon' => 'chart-pie'],
                        ],
                    ],
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
                ],
            ],

            // 📞 Centro de Contacto
            [
                'label' => __('Centro de Contacto'),
                'icon' => 'phone',
                'permission' => 'call_records.viewAny',
                'submenu' => [
                    ['label' => __('Mi Panel'), 'route' => 'contact-center.agent-dashboard', 'pattern' => 'contact-center/my-dashboard*', 'icon' => 'user-circle'],
                    ['label' => __('Panel General'), 'route' => 'contact-center.general-dashboard', 'pattern' => 'contact-center/general-dashboard*', 'icon' => 'presentation-chart-line'],
                    ['label' => __('Llamadas'), 'route' => 'contact-center.calls.index', 'pattern' => 'contact-center/calls*', 'permission' => 'call_records.viewAny', 'icon' => 'phone-arrow-down-left'],
                ],
            ],

            // 📢 Comunicaciones
            [
                'label' => __('Comunicaciones'),
                'icon' => 'megaphone',
                'permission' => ['news.create', 'polls.manage', 'shoutouts.manage', 'communications.manage', 'communications.moderate'],
                'submenu' => [
                    ['label' => __('Noticias'), 'route' => 'communications.news.index', 'pattern' => 'admin/communications/news*', 'permission' => 'news.create', 'icon' => 'document-text'],
                    ['label' => __('Encuestas'), 'route' => 'communications.polls.index', 'pattern' => 'admin/communications/polls*', 'permission' => 'polls.manage', 'icon' => 'chart-bar'],
                    ['label' => __('Reconocimientos'), 'route' => 'communications.shoutouts.index', 'pattern' => 'admin/communications/shoutouts*', 'permission' => 'shoutouts.manage', 'icon' => 'hand-thumb-up'],
                    ['label' => __('Categorías'), 'route' => 'communications.admin.categories.index', 'pattern' => 'admin/communications/categories*', 'permission' => 'communications.manage', 'icon' => 'tag'],
                    ['label' => __('Etiquetas'), 'route' => 'communications.admin.tags.index', 'pattern' => 'admin/communications/tags*', 'permission' => 'communications.manage', 'icon' => 'tag'],
                    ['label' => __('Moderación de Contenido'), 'route' => 'communications.moderation.index', 'pattern' => 'admin/communications/moderation*', 'permission' => 'communications.moderate', 'icon' => 'shield-exclamation'],
                ],
            ],

            // 🎫 Soporte
            [
                'label' => __('Soporte'),
                'icon' => 'lifebuoy',
                'submenu' => [
                    ['label' => __('Mis Tickets'), 'route' => 'helpdesk.my-tickets', 'pattern' => 'helpdesk/my-tickets*', 'permission' => 'helpdesk.view', 'icon' => 'ticket'],
                    ['label' => __('Bandeja de Soporte'), 'route' => 'helpdesk.manage', 'pattern' => 'helpdesk/manage*', 'permission' => 'helpdesk.manage', 'icon' => 'inbox'],
                    ['label' => __('Base de Conocimiento'), 'route' => 'knowledge.index', 'pattern' => 'knowledge*', 'permission' => 'knowledge.viewAny', 'icon' => 'book-open'],
                ],
            ],

            // 📊 Reportes
            [
                'label' => __('Reportes'),
                'icon' => 'document-chart-bar',
                'permission' => 'menu.reports',
                'submenu' => [
                    ['label' => __('Reportes'), 'route' => 'reports.index', 'pattern' => 'reportes*', 'icon' => 'document-text'],
                    ['label' => __('Reporte Diario'), 'route' => 'operations.daily-report', 'pattern' => 'operations/reporte-diario*', 'permission' => 'operations.view', 'icon' => 'clipboard-document-list'],
                    ['label' => __('Marco de Reportes'), 'route' => 'operations.reports', 'pattern' => 'operations/reports*', 'permission' => 'operations.view', 'icon' => 'document-text'],
                    ['label' => __('Reportes de Personal'), 'route' => 'personnel.staffing-summary', 'pattern' => 'personnel/reports*', 'permission' => 'reports.staffing', 'icon' => 'presentation-chart-line'],
                ],
            ],

            // 📚 Documentación
            [
                'label' => __('Documentación'),
                'icon' => 'book-open-text',
                'submenu' => [
                    ['label' => __('Artículos'), 'route' => 'documentation.index', 'pattern' => 'docs*', 'icon' => 'document-text'],
                    ['label' => __('Administrar Artículos'), 'route' => 'documentation.admin.articles', 'pattern' => 'admin/documentation*', 'permission' => 'articles.manage', 'icon' => 'pencil-square'],
                ],
            ],

            // 🗃 Archivos
            [
                'label' => __('Archivos'),
                'icon' => 'folder',
                'submenu' => [
                    ['label' => __('Explorador de Archivos'), 'route' => 'filesystem.index', 'pattern' => 'filesystem', 'icon' => 'folder-open'],
                    ['label' => __('Centro de Descargas'), 'route' => 'filesystem.download-center', 'pattern' => 'descargas*', 'icon' => 'folder-arrow-down'],
                ],
            ],

            // ⚙️ Administración
            [
                'label' => __('Administración'),
                'icon' => 'cog-6-tooth',
                'permission' => ['employees.view', 'directorates.viewAny', 'teams.viewAny', 'users.view', 'roles.view', 'quality.criteria.view', 'quality.queues.manage', 'call_queues.manage', 'channels.manage', 'case_subtypes.manage', 'admin.system', 'audit.view'],
                'submenu' => [
                    [
                        'label' => __('Personas y Organización'),
                        'icon' => 'building-office',
                        'submenu' => [
                            ['label' => __('Listar Empleados'), 'route' => 'employees.index', 'pattern' => 'employees', 'permission' => 'employees.view', 'icon' => 'user-group'],
                            ['label' => __('Crear Empleado'), 'route' => 'employees.create', 'pattern' => 'employees/create*', 'permission' => 'employees.create', 'icon' => 'user-plus'],
                            ['label' => __('Importar Empleados'), 'route' => 'employees.import', 'pattern' => 'employees/import*', 'permission' => 'employees.import', 'icon' => 'arrow-up-tray'],
                            ['label' => __('Direcciones'), 'route' => 'organization.directorates.index', 'pattern' => 'organization/directorates*', 'permission' => 'directorates.viewAny', 'icon' => 'building-library'],
                            ['label' => __('Departamentos'), 'route' => 'organization.departments.index', 'pattern' => 'organization/departments*', 'permission' => 'departments.viewAny', 'icon' => 'building-office-2'],
                            ['label' => __('Cargos'), 'route' => 'organization.positions.index', 'pattern' => 'organization/positions*', 'permission' => 'positions.viewAny', 'icon' => 'briefcase'],
                            ['label' => __('Equipos'), 'route' => 'organization.teams.index', 'pattern' => 'organization/teams*', 'permission' => 'teams.viewAny', 'icon' => 'users'],
                            ['label' => __('Ubicaciones'), 'route' => 'location.index', 'pattern' => 'location*', 'permission' => 'directorates.viewAny', 'icon' => 'map-pin'],
                            ['label' => __('Usuarios'), 'route' => 'users.index', 'pattern' => 'admin/users*', 'permission' => 'users.view', 'icon' => 'user-circle'],
                            ['label' => __('Roles y Permisos'), 'route' => 'roles.index', 'pattern' => 'admin/roles*', 'permission' => 'roles.view', 'icon' => 'shield-check'],
                        ],
                    ],
                    [
                        'label' => __('Catálogos y Sistema'),
                        'icon' => 'wrench-screwdriver',
                        'submenu' => [
                            ['label' => __('Criterios de Evaluación'), 'route' => 'quality.criteria.index', 'pattern' => 'quality/criterios', 'permission' => 'quality.criteria.view', 'icon' => 'clipboard-document'],
                            ['label' => __('Criterios por Cola'), 'route' => 'quality.queues.criteria', 'pattern' => 'quality/colas/criterios*', 'permission' => 'quality.criteria.view', 'icon' => 'list-bullet'],
                            ['label' => __('Colas de Evaluación'), 'route' => 'quality.queues.index', 'pattern' => 'quality/colas', 'permission' => 'quality.queues.manage', 'icon' => 'queue-list'],
                            ['label' => __('Colas del Contact Center'), 'route' => 'contact-center.admin.queues.index', 'pattern' => 'contact-center/catalogs/queues*', 'permission' => 'call_queues.manage', 'icon' => 'queue-list'],
                            ['label' => __('Canales'), 'route' => 'contact-center.admin.channels.index', 'pattern' => 'contact-center/catalogs/channels*', 'permission' => 'channels.manage', 'icon' => 'signal'],
                            ['label' => __('Subtipos de Caso'), 'route' => 'contact-center.admin.subtypes.index', 'pattern' => 'contact-center/catalogs/subtypes*', 'permission' => 'case_subtypes.manage', 'icon' => 'tag'],
                            ['label' => __('Cuotas de Almacenamiento'), 'route' => 'filesystem.quotas', 'pattern' => 'filesystem/quotas*', 'permission' => 'admin.system', 'icon' => 'server'],
                            ['label' => __('Mantenimiento del Sistema'), 'route' => 'admin.system.maintenance', 'pattern' => 'admin/system/maintenance*', 'permission' => 'admin.system', 'icon' => 'wrench-screwdriver'],
                            ['label' => __('Auditoría'), 'route' => 'audit.index', 'pattern' => 'admin/audit*', 'permission' => 'audit.view', 'icon' => 'clipboard-document-list'],
                            ['label' => __('Notificaciones del Sistema'), 'route' => 'admin.notifications', 'pattern' => 'admin/notifications*', 'permission' => 'admin.system', 'icon' => 'bell'],
                        ],
                    ],
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
                if (! self::userCan($user, $item['permission'])) {
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

    /**
     * Comprueba si el usuario posee el permiso. Acepta un único permiso
     * (string) o un array de permisos en el que basta con tener uno (OR).
     */
    private static function userCan($user, string|array $permissions): bool
    {
        foreach ((array) $permissions as $permission) {
            try {
                if ($user->hasPermissionTo($permission)) {
                    return true;
                }
            } catch (PermissionDoesNotExist) {
                continue;
            }
        }

        return false;
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
