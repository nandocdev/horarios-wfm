<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Support\Facades\Route as RouteFacade;

/**
 * Helper institucional para la gestión de la navegación (Sidebar y Navbar).
 * [MEJORA]: Filtra automáticamente los elementos basados en permisos de Spatie/Gates.
 */
class MenuHelper {
    /**
     * Usuario actual para verificación de permisos.
     */
    protected static $currentUser = null;

    /**
     * Retorna la colección de elementos del menú lateral.
     */
    public static function getSidebarItems($user = null, array $counts = []): Collection {
        self::$currentUser = $user ?: AuthFacade::user();

        return collect([
            [
                'label' => __('Dashboard'),
                'icon' => 'home',
                'route' => 'dashboard',
                'pattern' => 'dashboard',
                'permission' => null,
            ],

            [
                'label' => __('Blog'),
                'icon' => 'newspaper',
                'route' => 'home',
                'pattern' => '/',
                'permission' => null,
            ],

            [
                'label' => __('Descargas'),
                'icon' => 'folder-arrow-down',
                'route' => 'filesystem.download-center',
                'pattern' => 'descargas*',
                'permission' => null,
            ],

            // ===== 1. MI ESPACIO =====
            [
                'label' => __('Mi Espacio'),
                'icon' => 'user',
                'permission' => null,
                'submenu' => [
                    [
                        'label' => __('Mi Día'),
                        'route' => 'schedules.my-day',
                        'pattern' => 'schedules/my-day*',
                        'permission' => null,
                        'icon' => 'clock',
                    ],
                    [
                        'label' => __('Mi Horario'),
                        'route' => 'schedules.my-schedule',
                        'pattern' => 'schedules/my-schedule*',
                        'permission' => null,
                        'icon' => 'calendar',
                    ],
                    [
                        'label' => __('Mis Métricas'),
                        'route' => 'operations.performance',
                        'pattern' => 'operations/performance*',
                        'permission' => null,
                        'icon' => 'chart-bar',
                    ],
                    [
                        'label' => __('Historial de Cambios'),
                        'route' => 'schedules.swap-history',
                        'pattern' => 'schedules/swap-history*',
                        'permission' => null,
                        'icon' => 'arrows-right-left',
                    ],
                    [
                        'label' => __('Historial de Permisos'),
                        'route' => 'schedules.leave-history',
                        'pattern' => 'schedules/leave-history*',
                        'permission' => null,
                        'icon' => 'document-text',
                    ],
                    [
                        'label' => __('Archivos'),
                        'route' => 'filesystem.index',
                        'pattern' => 'filesystem*',
                        'permission' => null, // Acceso base para todos los logueados
                        'icon' => 'folder',
                    ],
                ],
            ],

            // ===== 2. EQUIPO =====
            [
                'label' => __('Equipo'),
                'icon' => 'user-group',
                'permission' => 'menu.team', // O permiso equivalente de supervisor
                'submenu' => [

                    [
                        'label' => __('Desempeño'),
                        'route' => 'operations.team-performance',
                        'params' => ['view' => 'compliance'],
                        'pattern' => 'operations/team-performance*',
                        'permission' => 'reports.compliance',
                        'icon' => 'chart-bar-square',
                    ],
                    [
                        'label' => __('Analítica Avanzada'),
                        'route' => 'operations.advanced-analytics',
                        'pattern' => 'operations/advanced-analytics*',
                        'permission' => 'menu.team',
                        'icon' => 'bolt',
                    ],
                    [
                        'label' => __('Vista del Equipo'),
                        'route' => 'schedules.my-team',
                        'pattern' => 'schedules/my-team*',
                        'permission' => 'schedules.view_team',
                        'icon' => 'users',
                    ],
                    [
                        'label' => __('Solicitudes'),
                        'route' => 'schedules.manager-approvals',
                        'pattern' => 'schedules/manager-approvals*',
                        'permission' => 'wfm.leaves.manage',
                        'icon' => 'check-badge',
                        'badge' => $counts['pending_leaves'] ?? 0,
                    ],
                    [
                        'label' => __('Aprobaciones'),
                        'route' => 'schedules.wfm-approvals',
                        'pattern' => 'schedules/wfm-approvals*',
                        'permission' => 'wfm.swaps.manage',
                        'icon' => 'arrows-right-left',
                        'badge' => $counts['pending_swaps'] ?? 0,
                    ],
                ],
            ],

            // ===== 3. PLANIFICACIÓN =====
            [
                'label' => __('Planificación'),
                'icon' => 'table-cells',
                'permission' => 'menu.planning',
                'submenu' => [
                    [
                        'label' => __('Planificación Semanal'),
                        'route' => 'schedules.planning',
                        'pattern' => 'schedules/planning*',
                        'permission' => 'wfm.planning.manage',
                        'icon' => 'calendar-days',
                    ],
                    [
                        'label' => __('Actividades Intradía'),
                        'route' => 'schedules.intraday-activities.manage',
                        'pattern' => 'schedules/intraday-activities/manage*',
                        'permission' => 'wfm.intraday.manage',
                        'icon' => 'adjustments-vertical',
                    ],
                    [
                        'label' => __('Definiciones de Actividad'),
                        'route' => 'schedules.scheduled-activities',
                        'pattern' => 'schedules/scheduled-activities*',
                        'permission' => 'wfm.catalogs.shifts',
                        'icon' => 'list-bullet',
                    ],
                    [
                        'label' => __('Excepciones Masivas'),
                        'route' => 'schedules.exceptions',
                        'pattern' => 'schedules/exceptions*',
                        'permission' => 'wfm.exceptions.manage',
                        'icon' => 'exclamation-triangle',
                    ],
                ],
            ],

            // ===== 4. OPERACIÓN =====
            [
                'label' => __('Operación'),
                'icon' => 'presentation-chart-line',
                'permission' => 'wfm.realtime.view',
                'submenu' => [
                    [
                        'label' => __('Realtime'),
                        'route' => 'operations.realtime',
                        'pattern' => 'operations/realtime*',
                        'permission' => 'wfm.realtime.view',
                        'icon' => 'chart-pie',
                    ],
                    [
                        'label' => __('Disponibilidad'),
                        'route' => 'operations.availability',
                        'pattern' => 'operations/availability*',
                        'permission' => 'wfm.availability.view',
                        'icon' => 'check-circle',
                    ],
                ],
            ],

            [
                'label' => __('Reportes'),
                'icon' => 'document-chart-bar',
                'permission' => 'menu.reports',
                'submenu' => [
                    [
                        'label' => __('Centro de Reportes'),
                        'route' => 'operations.reports',
                        'pattern' => 'operations/reports*',
                        'permission' => 'reports.reports',
                        'icon' => 'squares-2x2',
                        'badge' => 'HUB',
                    ],
                    // Capa 1: WFM Core
                    [
                        'label' => __('Adherencia y Cobertura'),
                        'route' => 'operations.team-performance',
                        'params' => ['view' => 'attendance'],
                        'pattern' => 'operations/team-performance*',
                        'permission' => 'reports.attendance',
                        'icon' => 'clock',
                    ],
                    // Capa 2: Productividad
                    [
                        'label' => __('Productividad Operativa'),
                        'route' => 'operations.advanced-analytics',
                        'pattern' => 'operations/advanced-analytics*',
                        'permission' => 'reports.scorecard',
                        'icon' => 'bolt',
                    ],
                    // Capa 3: Colas
                    [
                        'label' => __('Performance por Cola'),
                        'route' => 'operations.queue-performance',
                        'pattern' => 'operations/queue-performance*',
                        'permission' => 'reports.reports',
                        'icon' => 'phone',
                    ],
                    // Capa 4: Workflow
                    [
                        'label' => __('Gestión de Solicitudes'),
                        'route' => 'schedules.request-summary',
                        'pattern' => 'schedules/reports/requests*',
                        'permission' => 'reports.requests',
                        'icon' => 'envelope-open',
                    ],
                    // Capa 5: Ejecutivo
                    [
                        'label' => __('Executive Dashboard'),
                        'route' => 'operations.dashboard',
                        'pattern' => 'operations/dashboard*',
                        'permission' => 'reports.reports',
                        'icon' => 'presentation-chart-line',
                    ],
                    [
                        'label' => __('Inventario de Staffing'),
                        'route' => 'personnel.staffing-summary',
                        'pattern' => 'personnel/reports/staffing*',
                        'permission' => 'reports.staffing',
                        'icon' => 'user-group',
                    ],
                ],
            ],

            // ===== 6. CONFIGURACIÓN =====
            [
                'label' => __('Configuración'),
                'icon' => 'cog-6-tooth',
                'permission' => 'menu.admin',
                'submenu' => [
                    [
                        'label' => __('Documentación'),
                        'route' => 'documentation.admin.articles',
                        'pattern' => 'admin/documentation*',
                        'permission' => 'articles.manage',
                        'icon' => 'book-open',
                    ],
                    [
                        'label' => __('Usuarios'),
                        'route' => 'users.index',
                        'pattern' => 'admin/users*',
                        'permission' => 'users.view',
                        'icon' => 'users',
                    ],
                    [
                        'label' => __('Roles y Permisos'),
                        'route' => 'roles.index',
                        'pattern' => 'admin/roles*',
                        'permission' => 'roles.view',
                        'icon' => 'shield-check',
                    ],
                    [
                        'label' => __('Catálogos WFM'),
                        'icon' => 'swatch',
                        'permission' => 'wfm.catalogs.shifts',
                        'submenu' => [
                            [
                                'label' => __('Turnos'),
                                'route' => 'schedules.shifts',
                                'pattern' => 'schedules/shifts*',
                                'permission' => 'wfm.catalogs.shifts',
                                'icon' => 'clock',
                            ],
                            [
                                'label' => __('Tipos de Actividad'),
                                'route' => 'schedules.activity-types',
                                'pattern' => 'schedules/activity-types*',
                                'permission' => 'wfm.catalogs.shifts',
                                'icon' => 'tag',
                            ],
                            [
                                'label' => __('Motivos de Ausencia'),
                                'route' => 'schedules.absence-reasons',
                                'pattern' => 'schedules/absence-reasons*',
                                'permission' => 'wfm.catalogs.shifts',
                                'icon' => 'no-symbol',
                            ],
                            [
                                'label' => __('Estados de Agente'),
                                'route' => 'schedules.agent-states',
                                'pattern' => 'schedules/agent-states*',
                                'permission' => 'wfm.catalogs.shifts',
                                'icon' => 'user-circle',
                            ],
                        ],
                    ],
                    [
                        'label' => __('Empleados'),
                        'route' => 'employees.index',
                        'pattern' => 'employees*',
                        'gate' => ['viewAny', Employee::class],
                        'icon' => 'user-group',
                    ],
                    [
                        'label' => __('Estructura'),
                        'icon' => 'building-office',
                        'permission' => 'departments.viewAny',
                        'submenu' => [
                            [
                                'label' => __('Departamentos'),
                                'route' => 'organization.departments.index',
                                'pattern' => 'organization/departments*',
                                'icon' => 'building-office-2',
                            ],
                            [
                                'label' => __('Direcciones'),
                                'route' => 'organization.directorates.index',
                                'pattern' => 'organization/directorates*',
                                'icon' => 'map',
                            ],
                            [
                                'label' => __('Posiciones'),
                                'route' => 'organization.positions.index',
                                'pattern' => 'organization/positions*',
                                'icon' => 'briefcase',
                            ],
                            [
                                'label' => __('Equipos'),
                                'route' => 'organization.teams.index',
                                'pattern' => 'organization/teams*',
                                'icon' => 'user-group',
                            ],
                        ],
                    ],
                    [
                        'label' => __('Parámetros Operativos'),
                        'route' => 'schedules.operational-settings',
                        'pattern' => 'schedules/operational-settings*',
                        'permission' => 'wfm.settings.manage',
                        'icon' => 'bolt',
                    ],
                    [
                        'label' => __('Mantenimiento'),
                        'route' => 'admin.system.maintenance',
                        'pattern' => 'admin/system/maintenance*',
                        'permission' => 'admin.system',
                        'icon' => 'wrench-screwdriver',
                    ],
                    [
                        'label' => __('Archivos'),
                        'icon' => 'folder-open',
                        'permission' => 'admin.system',
                        'submenu' => [
                            [
                                'label' => __('Gestión de Cuotas'),
                                'route' => 'filesystem.quotas',
                                'pattern' => 'filesystem/quotas*',
                                'permission' => 'admin.system',
                                'icon' => 'circle-stack',
                            ],
                        ],
                    ],
                ],
            ],

            // Secciones extra / Core del negocio que no caben arriba:
            [
                'label' => __('Contact Center'),
                'icon' => 'phone',
                'permission' => 'menu.contact_center',
                'submenu' => [
                    [
                        'label' => __('Registros de llamadas'),
                        'route' => 'contact-center.calls.index',
                        'pattern' => 'contact-center/calls*',
                        'permission' => 'call_records.update',
                        'icon' => 'phone-arrow-down-left',
                    ],
                    [
                        'label' => __('Colas de atención'),
                        'route' => 'contact-center.admin.queues.index',
                        'pattern' => 'contact-center/catalogs/queues*',
                        'permission' => 'call_queues.manage',
                        'icon' => 'queue-list',
                    ],
                ],
            ],
            [
                'label' => __('Comunicaciones'),
                'icon' => 'chat-bubble-left-right',
                'permission' => 'menu.communications',
                'submenu' => [

                    [
                        'label' => __('Base de Conocimiento'),
                        'route' => 'knowledge.admin',
                        'pattern' => 'admin/knowledge*',
                        'permission' => 'knowledge.manage',
                        'icon' => 'academic-cap',
                    ],
                    [
                        'label' => __('Noticias'),
                        'route' => 'communications.news.index',
                        'pattern' => 'admin/communications/news*',
                        'permission' => 'news.viewAny',
                        'icon' => 'newspaper',
                    ],
                    [
                        'label' => __('Moderación'),
                        'route' => 'communications.moderation.index',
                        'pattern' => 'admin/communications/moderation*',
                        'permission' => 'news.moderate', // O el permiso que corresponda
                        'icon' => 'check-badge',
                    ],
                    [
                        'label' => __('Categorías'),
                        'route' => 'communications.admin.categories.index',
                        'pattern' => 'admin/communications/categories*',
                        'permission' => 'news.viewAny',
                        'icon' => 'tag',
                    ],
                    [
                        'label' => __('Etiquetas'),
                        'route' => 'communications.admin.tags.index',
                        'pattern' => 'admin/communications/tags*',
                        'permission' => 'news.viewAny',
                        'icon' => 'hashtag',
                    ],
                    [
                        'label' => __('Encuestas'),
                        'route' => 'communications.polls.index',
                        'pattern' => 'admin/communications/polls*',
                        'permission' => 'news.viewAny', // Ajustar permiso si es necesario
                        'icon' => 'list-bullet',
                    ],
                    [
                        'label' => __('Reconocimientos'),
                        'route' => 'communications.shoutouts.index',
                        'pattern' => 'admin/communications/shoutouts*',
                        'permission' => 'news.viewAny', // Ajustar permiso si es necesario
                        'icon' => 'heart',
                    ],
                ],
            ],
        ])->filter(fn($item) => self::canView($item))
            ->map(fn($item) => self::processActiveStates($item));
    }

    /**
     * Retorna los elementos del pie del sidebar (Soporte).
     */
    public static function getFooterItems($user = null): Collection {
        self::$currentUser = $user ?: AuthFacade::user();

        return collect([
            [
                'label' => __('Documentación'),
                'icon' => 'book-open',
                'route' => 'documentation.index',
                'pattern' => 'docs*',
                'permission' => null,
            ],
            [
                'label' => __('Base de Conocimiento'),
                'icon' => 'academic-cap',
                'route' => 'knowledge.index',
                'pattern' => 'knowledge*',
                'permission' => null,
            ],
            [
                'label' => __('Soporte'),
                'icon' => 'lifebuoy',
                'route' => 'helpdesk.my-tickets',
                'pattern' => 'helpdesk/my-tickets*',
                'permission' => 'helpdesk.view',
            ],
            [
                'label' => __('Bandeja Soporte'),
                'icon' => 'inbox-stack',
                'route' => 'helpdesk.manage',
                'pattern' => 'helpdesk/manage*',
                'permission' => 'helpdesk.manage',
            ],
        ])->filter(fn($item) => self::canView($item))
            ->map(fn($item) => self::processActiveStates($item));
    }

    /**
     * Verifica si el usuario actual tiene permiso para ver el elemento.
     */
    protected static function canView(array $item): bool {
        $user = self::$currentUser ?: AuthFacade::user();

        // 1. Si tiene submenú, verificar si al menos un hijo es visible
        if (isset($item['submenu']) && !empty($item['submenu'])) {
            $hasVisibleChild = false;
            foreach ($item['submenu'] as $subItem) {
                if (self::canView($subItem)) {
                    $hasVisibleChild = true;
                    break;
                }
            }

            // Si no hay hijos visibles, ocultar el padre aunque tenga permiso
            if (!$hasVisibleChild) {
                return false;
            }
        }

        // 2. Si no requiere permiso ni gate, es visible para autenticados
        if ((!isset($item['permission']) || empty($item['permission'])) && !isset($item['gate'])) {
            return (bool) $user;
        }

        // [NUEVO] Lógica delegada para Coordinadores (Posición ID 2, etc.)
        // Si el ítem pertenece a la sección de Equipo o Planificación Intradía, permitimos si tiene derechos de coordinador.
        $coordinatorPermissions = [
            'menu.team',
            'schedules.view_team',
            'wfm.leaves.manage',
            'reports.compliance',
            'wfm.realtime.view',
        ];

        if (isset($item['permission']) && in_array($item['permission'], $coordinatorPermissions)) {
            if ($user->employee?->hasCoordinatorRights()) {
                return true;
            }
        }

        // 3. Permiso simple (Spatie)
        if (isset($item['permission']) && !empty($item['permission'])) {
            return $user && $user->can($item['permission']);
        }

        // 4. Roles específicos (Spatie)
        if (isset($item['roles']) && !empty($item['roles'])) {
            return $user && $user->hasAnyRole((array) $item['roles']);
        }

        // 5. Gate específico
        if (isset($item['gate']) && is_array($item['gate']) && count($item['gate']) >= 1) {
            [$ability, $model] = $item['gate'] + [null, null];

            return $user && $user->can($ability, $model);
        }

        return false;
    }

    /**
     * Procesa recursivamente el estado activo basado en la ruta actual.
     */
    protected static function processActiveStates(array $item): array {
        // Si tiene submenú, procesar los hijos pero no filtrar nuevamente
        // ya que el filtrado se hace en el método getSidebarItems
        if (isset($item['submenu'])) {
            $item['submenu'] = collect($item['submenu'])
                ->map(fn($sub) => self::processActiveStates($sub))
                ->toArray();
        }

        $item['is_active'] = self::isActive($item);

        return $item;
    }

    /**
     * Determina si el elemento o alguno de sus hijos está activo.
     */
    protected static function isActive(array $item): bool {
        // Si tiene parámetros específicos, deben coincidir todos con la request actual
        if (isset($item['params'])) {
            foreach ($item['params'] as $key => $value) {
                if (request()->query($key) != $value) {
                    return false;
                }
            }
        }

        // Si tiene patrón específico
        if (isset($item['pattern']) && request()->is($item['pattern'])) {
            return true;
        }

        // Si la ruta coincide directamente
        if (isset($item['route']) && RouteFacade::currentRouteName() === $item['route']) {
            return true;
        }

        // Si algún hijo está activo
        if (isset($item['submenu'])) {
            return collect($item['submenu'])->contains(fn($sub) => self::isActive($sub));
        }

        return false;
    }
}
