<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\CoreModule\Models\Role;
use App\Modules\CoreModule\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $this->call([
            PanamaGeographySeeder::class,
            OrganizationModuleSeeder::class,
            EmploymentStatusSeeder::class,
            RolesAndPermissionsSeeder::class,
            ChannelsSeeder::class,
            UserFromEmployeeSeeder::class,
            AssignEmployeeRolesSeeder::class,
            EmployeeSeeder::class,
            TeamManagerSeeder::class,
            TeamMemberSeeder::class,
            CommunicationsModuleSeeder::class,
            QueueSeeder::class,
            CaseSubtypesSeeder::class,
            ScheduleSeeder::class,
            ScheduleCatalogsSeeder::class,
            CsvWeeklyScheduleSeeder::class,
            HelpdeskCategoriesSeeder::class,
            OperationalSettingsSeeder::class,
            VacacionesSeeder::class,
        ]);

        // Asegurar que el usuario administrador mantenga permisos totales
        $adminUser = User::query()->where('email', 'yhernandez@css.gob.pa')->first();
        $adminRole = Role::query()->where('name', 'admin')->where('guard_name', 'web')->first();

        if ($adminUser !== null && $adminRole !== null) {
            $adminUser->assignRole($adminRole->name);
        }
    }
}
