<?php

declare(strict_types=1);

namespace App\Modules\QualityModule\Database\Seeders;

use App\Modules\CoreModule\Models\Role;
use App\Modules\QualityModule\Enums\QualityRole;
use App\Modules\QualityModule\Enums\QueueCode;
use App\Modules\QualityModule\Models\Criteria;
use App\Modules\QualityModule\Models\CriteriaVersion;
use App\Modules\QualityModule\Models\Queue;
use App\Modules\QualityModule\Models\QueueCriteria;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class QualityModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedQueues();
        $this->seedPermissions();
        $this->seedRoles();
        $this->seedCriteria();
    }

    private function seedQueues(): void
    {
        foreach (QueueCode::cases() as $code) {
            Queue::firstOrCreate(
                ['code' => $code->value],
                ['name' => $code->label(), 'is_active' => true]
            );
        }

        $this->command?->info(sprintf('✓ %d colas creadas', count(QueueCode::cases())));
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'quality.evaluations.view',
            'quality.evaluations.create',
            'quality.evaluations.delete',
            'quality.feedback.create',
            'quality.calibrations.create',
            'quality.criteria.view',
            'quality.criteria.create',
            'quality.criteria.update',
            'quality.queues.manage',
            'quality.dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->command?->info(sprintf('✓ %d permisos quality creados', count($permissions)));
    }

    private function seedRoles(): void
    {
        foreach (QualityRole::cases() as $qualityRole) {
            $role = Role::firstOrCreate(
                ['name' => $qualityRole->value, 'guard_name' => 'web']
            );

            $role->syncPermissions($qualityRole->permissions());
        }

        // Asignar permisos quality a roles existentes del sistema
        $rolePermissions = [
            'supervisor' => ['quality.evaluations.view'],
            'coordinator' => ['quality.evaluations.view', 'quality.evaluations.create', 'quality.feedback.create'],
            'chief' => ['quality.evaluations.view', 'quality.dashboard.view'],
            'wfm' => ['quality.evaluations.view', 'quality.evaluations.create', 'quality.feedback.create', 'quality.calibrations.create', 'quality.dashboard.view'],
            'director' => ['quality.evaluations.view', 'quality.dashboard.view'],
            'admin' => QualityRole::Admin->permissions(),
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
            if ($role) {
                $role->givePermissionTo($perms);
            }
        }

        $this->command?->info('✓ Roles quality configurados');
    }

    private function seedCriteria(): void
    {
        $criterios = [
            [
                'code' => 'SALUDO',
                'criterio_text' => 'Saludo inicial completo (nombre de la institución y del agente)',
                'puntaje' => 10,
            ],
            [
                'code' => 'IDENTIFICA',
                'criterio_text' => 'Identificación completa del usuario (nombre, cédula, teléfono)',
                'puntaje' => 10,
            ],
            [
                'code' => 'ESCUCHA',
                'criterio_text' => 'Escucha activa sin interrumpir al usuario',
                'puntaje' => 10,
            ],
            [
                'code' => 'TONO',
                'criterio_text' => 'Tono de voz y lenguaje corporal adecuado',
                'puntaje' => 5,
            ],
            [
                'code' => 'SOLUCION',
                'criterio_text' => 'Brinda solución o alternativa al requerimiento',
                'puntaje' => 15,
            ],
            [
                'code' => 'DESPEDIDA',
                'criterio_text' => 'Despedida cordial y ofrece seguimiento',
                'puntaje' => 5,
            ],
            [
                'code' => 'REGISTRO',
                'criterio_text' => 'Registra correctamente los datos de la gestión en el sistema',
                'puntaje' => 10,
            ],
            [
                'code' => 'NORMAS',
                'criterio_text' => 'Cumple con las normas de confidencialidad y privacidad',
                'puntaje' => 15,
            ],
            [
                'code' => 'TIEMPO',
                'criterio_text' => 'Gestiona el tiempo de forma eficiente',
                'puntaje' => 5,
            ],
            [
                'code' => 'RETENCION',
                'criterio_text' => 'Aplica técnicas de retención ante intento de cancelación',
                'puntaje' => 15,
            ],
        ];

        $queues = Queue::all()->keyBy('code');

        foreach ($criterios as $data) {
            $criteria = Criteria::firstOrCreate(
                ['code' => $data['code']],
                ['code' => $data['code']]
            );

            $version = CriteriaVersion::firstOrCreate(
                [
                    'criteria_id' => $criteria->id,
                    'version' => 1,
                ],
                [
                    'criteria_id' => $criteria->id,
                    'version' => 1,
                    'criterio_text' => $data['criterio_text'],
                    'puntaje' => $data['puntaje'],
                    'valid_from' => Carbon::parse('2025-01-01')->toDateString(),
                    'valid_to' => null,
                ]
            );

            // Asignar criterio a colas relevantes
            $queueCodes = match ($data['code']) {
                'RETENCION' => ['CM-Canc'],
                default => array_diff(QueueCode::all(), ['CM-Canc']),
            };

            foreach ($queueCodes as $code) {
                $queue = $queues->get($code);
                if (! $queue) {
                    continue;
                }

                QueueCriteria::firstOrCreate(
                    [
                        'queue_id' => $queue->id,
                        'criteria_version_id' => $version->id,
                    ],
                    [
                        'queue_id' => $queue->id,
                        'criteria_version_id' => $version->id,
                        'orden' => $this->getNextOrder($queue->id),
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command?->info(sprintf('✓ %d criterios de ejemplo creados con versiones', count($criterios)));
    }

    private int $nextOrder = 1;

    private function getNextOrder(string $queueId): int
    {
        return $this->nextOrder++;
    }
}
