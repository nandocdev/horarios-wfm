<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\CommunicationsModule\Database\Seeders\CommunicationsPermissionSeeder;
use App\Modules\CommunicationsModule\Database\Seeders\NewsSeeder;
use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CommunicationsModule\Models\Tag;
use Illuminate\Database\Seeder;

class CommunicationsModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ejecutar seeder de permisos primero
        $this->call([
            CommunicationsPermissionSeeder::class,
            NewsSeeder::class,
        ]);

        // Categorías por defecto
        $categories = [
            [
                'name' => 'Anuncios Generales',
                'slug' => 'anuncios-generales',
                'description' => 'Comunicaciones generales para toda la organización',
                'color' => '#3B82F6',
                'sort_order' => 1,
            ],
            [
                'name' => 'Recursos Humanos',
                'slug' => 'recursos-humanos',
                'description' => 'Actualizaciones relacionadas con RRHH',
                'color' => '#10B981',
                'sort_order' => 2,
            ],
            [
                'name' => 'Eventos',
                'slug' => 'eventos',
                'description' => 'Información sobre eventos y actividades',
                'color' => '#F59E0B',
                'sort_order' => 3,
            ],
            [
                'name' => 'Políticas',
                'slug' => 'politicas',
                'description' => 'Actualizaciones de políticas y procedimientos',
                'color' => '#EF4444',
                'sort_order' => 4,
            ],
            [
                'name' => 'Reconocimientos',
                'slug' => 'reconocimientos',
                'description' => 'Celebraciones y reconocimientos',
                'color' => '#8B5CF6',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        // Tags por defecto
        $tags = [
            ['name' => 'urgente', 'slug' => 'urgente', 'color' => '#EF4444'],
            ['name' => 'importante', 'slug' => 'importante', 'color' => '#F59E0B'],
            ['name' => 'informativo', 'slug' => 'informativo', 'color' => '#3B82F6'],
            ['name' => 'positivo', 'slug' => 'positivo', 'color' => '#10B981'],
            ['name' => 'cambio', 'slug' => 'cambio', 'color' => '#8B5CF6'],
            ['name' => 'actualización', 'slug' => 'actualizacion', 'color' => '#6B7280'],
            ['name' => 'nuevo', 'slug' => 'nuevo', 'color' => '#059669'],
            ['name' => 'evento', 'slug' => 'evento', 'color' => '#DC2626'],
        ];

        foreach ($tags as $tag) {
            Tag::firstOrCreate(
                ['slug' => $tag['slug']],
                $tag
            );
        }
    }
}
