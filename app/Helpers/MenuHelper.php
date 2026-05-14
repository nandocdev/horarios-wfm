<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Modules\AuditModule\Models\AuditLog;
use App\Modules\WfmModule\Models\AbsenceReasonCode;
use App\Modules\WfmModule\Models\ActivityType;
use App\Modules\WfmModule\Models\AgentState;
use App\Modules\WfmModule\Models\Schedule;
use App\Modules\WfmModule\Models\ScheduledActivityDefinition;
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
                        'label' => __('Mis Solicitudes'),
                        'route' => null, // TODO: route('schedules.my-requests')
                        'pattern' => 'schedules/my-requests*',
                        'permission' => null,
                        'icon' => 'document-text',
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
                        'label' => __('Vista del Equipo'),
                        'route' => 'schedules.my-team',
                        'pattern' => 'schedules/my-team*',
                        'permission' => 'schedules.view_team',
                        'icon' => 'users',
                    ],
                    [
                        'label' => __('Solicitudes del Equipo'),
                        'route' => 'schedules.manager-approvals',
                        'pattern' => 'schedules/manager-approvals*',
                        'permission' => 'wfm.leaves.manage',
                        'icon' => 'check-badge',
                        'badge' => $counts['pending_leaves'] ?? 0,
                    ],
                    [
                        'label' => __('Aprobación de Cambios'),
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
                        'pattern' => 'schedules/scheduled-activities/manage*',
                        'permission' => 'wfm.intraday.manage',
                        'icon' => 'adjustments-vertical',
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
                        'label' => __('Monitoreo Realtime'),
                        'route' => 'operations.realtime',
                        'pattern' => 'operations/realtime*',
                        'permission' => 'wfm.realtime.view',
                        'icon' => 'chart-pie',
                    ],
                    [
                        'label' => __('Disponibilidad'),
                        'route' => null, // TODO: Crear ruta
                        'pattern' => 'schedules/availability*',
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
                        'label' => __('Asistencia y Puntualidad'),
                        'route' => 'operations.team-performance',
                        'params' => ['view' => 'attendance'],
                        'pattern' => 'operations/team-performance*',
                        'permission' => 'reports.attendance',
                        'icon' => 'clipboard-document-check',
                    ],
                    [
                        'label' => __('Inventario de Staffing'),
                        'route' => 'personnel.staffing-summary',
                        'pattern' => 'personnel/reports/staffing*',
                        'permission' => 'reports.staffing',
                        'icon' => 'user-group',
                    ],
                    [
                        'label' => __('Resumen de Solicitudes'),
                        'route' => 'schedules.request-summary',
                        'pattern' => 'schedules/reports/requests*',
                        'permission' => 'reports.requests',
                        'icon' => 'envelope-open',
                    ],
                    [
                        'label' => __('Auditoría de Cambios'),
                        'route' => 'audit.index',
                        'pattern' => 'admin/audit*',
                        'permission' => 'audit.view',
                        'icon' => 'finger-print',
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
                        'route' => 'schedules.shifts',
                        'pattern' => 'schedules/shifts*',
                        'permission' => 'wfm.catalogs.shifts',
                        'icon' => 'swatch',
                    ],
                    [
                        'label' => __('Empleados'),
                        'route' => 'employees.index',
                        'pattern' => 'employees*',
                        'gate' => ['viewAny', \App\Modules\PersonnelModule\Models\Employee::class],
                        'icon' => 'user-group',
                    ],
                    [
                        'label' => __('Estructura Organizacional'),
                        'route' => 'organization.departments.index',
                        'pattern' => 'organization/*',
                        'permission' => 'departments.viewAny',
                        'icon' => 'building-office',
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
            'operations.team-performance',
            'wfm.realtime.view',
            'realtime.view'
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
