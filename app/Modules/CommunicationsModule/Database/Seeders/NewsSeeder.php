<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Database\Seeders;

use App\Modules\CommunicationsModule\Models\Category;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Tag;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder para generar datos de prueba de noticias.
 */
class NewsSeeder extends Seeder
{
    public function run(): void
    {
        // $admin = ['id' => 3]; //User::whereLike("name", "Fernando")->first();

        // if (!$admin) {
        //     return;
        // }

        $newsData = [
            [
                'title' => 'Lanzamiento del Nuevo Sistema de Horarios WFM',
                'excerpt' => 'Hoy marcamos un hito con la implementación de nuestra nueva plataforma de gestión de fuerza de trabajo.',
                'content' => "## Bienvenidos a Antigravity\n\nEstamos emocionados de anunciar el lanzamiento oficial de **Antigravity**, nuestro nuevo sistema de gestión de horarios (WFM).\n\nEste sistema permitirá:\n\n* Visualización de turnos en tiempo real.\n* Solicitudes de cambios automatizadas.\n* Mejor comunicación entre equipos.\n\nEsperamos que esta herramienta facilite el día a día de todos nuestros colaboradores.",
                'author_id' => 3,
                'is_active' => true,
                'published_at' => now(),
                'status' => 'published',
            ],
            [
                'title' => 'Actualización de Políticas de Trabajo Híbrido',
                'excerpt' => 'Nuevas directrices para el balance entre vida laboral y personal en nuestra organización.',
                'content' => "Hemos actualizado nuestras políticas de trabajo para reflejar nuestro compromiso con el bienestar. A partir del próximo mes, los equipos podrán coordinar hasta 3 días de trabajo remoto según sus necesidades operativas.\n\nConsulta el manual completo en la sección de Recursos Humanos.",
                'author_id' => 3,
                'is_active' => true,
                'published_at' => now()->subDays(2),
                'status' => 'published',
            ],
            [
                'title' => 'Evento: Jornada de Salud y Bienestar 2026',
                'excerpt' => 'Acompáñanos este viernes en nuestras instalaciones para una jornada dedicada a tu salud.',
                'content' => "Tendremos:\n\n1. Chequeos médicos básicos.\n2. Sesiones de ergonomía.\n3. Charlas sobre salud mental.\n4. Refrigerios saludables.\n\n¡No faltes!",
                'author_id' => 3,
                'is_active' => true,
                'published_at' => now()->addDays(1),
                'status' => 'published',
            ],
            [
                'title' => 'Reconocimiento al Equipo de Soporte CSS',
                'excerpt' => 'Felicitamos a todo el equipo por alcanzar niveles récord de satisfacción al cliente este trimestre.',
                'content' => "Gracias a la dedicación y esfuerzo de cada uno de ustedes, hemos logrado superar nuestra meta de NPS en un 15%. Este logro es fruto del trabajo en equipo y la excelencia en el servicio.\n\n¡Sigan así!",
                'author_id' => 3,
                'is_active' => true,
                'published_at' => now()->subWeeks(1),
                'status' => 'published',
            ],
        ];

        foreach ($newsData as $data) {
            $news = News::firstOrCreate(
                ['slug' => Str::slug($data['title'])],
                $data
            );

            // Adjuntar categoría aleatoria
            $category = Category::inRandomOrder()->first();
            if ($category) {
                $news->categories()->sync([$category->id]);
            }

            // Adjuntar tags aleatorios
            $tags = Tag::inRandomOrder()->take(rand(1, 3))->pluck('id');
            if ($tags->isNotEmpty()) {
                $news->tags()->sync($tags);
            }
        }
    }
}
