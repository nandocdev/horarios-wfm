<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\CoreModule\Models\User;
use App\Modules\KnowledgeModule\Models\Category;
use App\Modules\KnowledgeModule\Models\Queue;
use App\Modules\KnowledgeModule\Models\Article;
use App\Modules\KnowledgeModule\Models\ArticleVersion;
use App\Modules\KnowledgeModule\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class KnowledgeBaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Categories
        $categoriesData = [
            ['id' => 1, 'name' => 'Procedimientos', 'description' => 'Guías paso a paso de los procesos operativos.'],
            ['id' => 2, 'name' => 'Preguntas Frecuentes', 'description' => 'Preguntas más comunes de los usuarios y pacientes.'],
            ['id' => 3, 'name' => 'Requisitos', 'description' => 'Documentación y requisitos para trámites.'],
            ['id' => 4, 'name' => 'Incidencias', 'description' => 'Flujo de reporte de fallas e incidencias.'],
            ['id' => 5, 'name' => 'Comunicados', 'description' => 'Informaciones y avisos importantes.'],
            ['id' => 6, 'name' => 'Políticas', 'description' => 'Regulaciones y lineamientos normativos.'],
            ['id' => 7, 'name' => 'Guiones de Atención', 'description' => 'Speechs y respuestas sugeridas para los operadores.'],
            ['id' => 8, 'name' => 'Escalamientos', 'description' => 'Rutas de escalamiento para casos especiales.'],
        ];

        foreach ($categoriesData as $cat) {
            Category::updateOrCreate(['id' => $cat['id']], $cat);
        }

        // 2. Seed Queues
        $queuesData = [
            ['name' => 'Solicitud / Atención General', 'priority' => 0],
            ['name' => 'Solicitud Policlínicas', 'priority' => 4],
            ['name' => 'Farmacia', 'priority' => 5],
            ['name' => 'Confirmación Policlínicas', 'priority' => 7],
            ['name' => 'Solicitud Laboratorio', 'priority' => 7],
            ['name' => 'Solicitud Odontología', 'priority' => 7],
            ['name' => 'SIPE', 'priority' => 8],
            ['name' => 'Información General', 'priority' => 8],
            ['name' => 'Quejas', 'priority' => 9],
            ['name' => 'Cancelación Policlínicas', 'priority' => 10],
            ['name' => 'Cancelación Laboratorio', 'priority' => 10],
            ['name' => 'Cancelación Odontología', 'priority' => 10],
        ];

        $queues = [];
        foreach ($queuesData as $qData) {
            $queues[$qData['name']] = Queue::updateOrCreate(['name' => $qData['name']], [
                'priority' => $qData['priority'],
                'is_active' => true,
            ]);
        }

        // Obtener un usuario de autor por defecto
        $author = User::first();
        if ($author === null) {
            $author = User::factory()->create([
                'name' => 'Supervisor WFM',
                'email' => 'supervisor_wfm@css.gob.pa',
            ]);
        }

        // 3. Seed Tags
        $tagsData = ['citas', 'cancelacion', 'laboratorio', 'policlinicas', 'urgencias', 'requisitos'];
        $tags = [];
        foreach ($tagsData as $tagName) {
            $tags[$tagName] = Tag::updateOrCreate(['name' => $tagName]);
        }

        // 4. Seed Sample Articles
        $articles = [
            [
                'title' => 'Cancelación por paciente - Policlínicas',
                'summary' => 'Proceso para cancelar una cita a solicitud directa del paciente en policlínicas.',
                'content' => 'Para realizar la cancelación a solicitud del paciente, siga estos pasos:<br>1. Valide la identidad del paciente con la cédula.<br>2. Busque la cita activa en el sistema.<br>3. Haga clic en Cancelar Cita y seleccione la opción "A solicitud del paciente".<br>4. Confirme el envío del correo de notificación.',
                'category_id' => 1, // Procedimientos
                'status' => 'published',
                'queues' => ['Cancelación Policlínicas', 'Solicitud / Atención General'],
                'tags' => ['cancelacion', 'policlinicas'],
            ],
            [
                'title' => 'Cancelación por sistema - Policlínicas',
                'summary' => 'Protocolo cuando el sistema cancela citas por inasistencia del médico o cierre.',
                'content' => 'Cuando una policlínica cancela citas masivamente por fuerza mayor:<br>1. Verifique el comunicado oficial de la policlínica.<br>2. Filtre los pacientes afectados.<br>3. Registre en la bitácora de contingencia.<br>4. Inicie llamadas para reprogramación inmediata.',
                'category_id' => 1, // Procedimientos
                'status' => 'published',
                'queues' => ['Cancelación Policlínicas'],
                'tags' => ['cancelacion', 'policlinicas'],
            ],
            [
                'title' => 'Reprogramación de Citas Médicas',
                'summary' => 'Guía de reprogramación cuando el cupo original es cancelado.',
                'content' => 'La reprogramación requiere:<br>1. Buscar cupos disponibles en la misma policlínica o aledañas.<br>2. Proponer al paciente 2 alternativas de fechas.<br>3. Confirmar la nueva fecha y generar el ticket.',
                'category_id' => 1, // Procedimientos
                'status' => 'published',
                'queues' => ['Cancelación Policlínicas', 'Solicitud Policlínicas'],
                'tags' => ['citas', 'policlinicas'],
            ],
            [
                'title' => '¿Puedo reagendar una cita cancelada?',
                'summary' => 'Respuestas sobre políticas de reagendamiento de citas de policlínica.',
                'content' => 'Sí, el paciente puede reagendar su cita cancelada en cualquier momento, sujeto a la disponibilidad del sistema. No se aplican penalizaciones por cancelación oportuna (mínimo 24 horas antes).',
                'category_id' => 2, // Preguntas Frecuentes
                'status' => 'published',
                'queues' => ['Cancelación Policlínicas', 'Confirmación Policlínicas'],
                'tags' => ['citas'],
            ],
            [
                'title' => '¿Cuántas veces puedo cancelar una cita?',
                'summary' => 'Límite de cancelaciones permitidas por paciente en el mes.',
                'content' => 'Un paciente puede cancelar hasta 3 citas consecutivas por año. Posterior a esto, el sistema requerirá validación de un supervisor para evitar bloqueos innecesarios de cupos.',
                'category_id' => 2, // Preguntas Frecuentes
                'status' => 'published',
                'queues' => ['Cancelación Policlínicas'],
                'tags' => ['cancelacion'],
            ],
            [
                'title' => 'Casos especiales de cancelación',
                'summary' => 'Escalamiento para pacientes de la tercera edad o con discapacidad.',
                'content' => 'Para pacientes vulnerables:<br>1. Si cancelan por enfermedad, se debe priorizar su reprogramación en la misma llamada.<br>2. Enviar el caso al supervisor si no hay cupos en 30 días.',
                'category_id' => 8, // Escalamientos
                'status' => 'published',
                'queues' => ['Cancelación Policlínicas', 'Cancelación Laboratorio'],
                'tags' => ['cancelacion'],
            ],
            [
                'title' => 'Procedimiento Cancelación Laboratorio',
                'summary' => 'Flujo para la cancelación de citas de laboratorio clínico.',
                'content' => 'El paciente debe proveer el número de trámite de laboratorio. Una vez cancelada, el reactivo se libera automáticamente en el stock diario de la policlínica seleccionada.',
                'category_id' => 1, // Procedimientos
                'status' => 'published',
                'queues' => ['Cancelación Laboratorio'],
                'tags' => ['cancelacion', 'laboratorio'],
            ],
        ];

        foreach ($articles as $art) {
            $slug = Str::slug($art['title']);
            $dbArticle = Article::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $art['title'],
                    'summary' => $art['summary'],
                    'content' => $art['content'],
                    'category_id' => $art['category_id'],
                    'status' => $art['status'],
                    'version' => 1,
                    'published_at' => now(),
                    'created_by' => $author->id,
                ]
            );

            // Relacionar colas
            $qIds = [];
            foreach ($art['queues'] as $qName) {
                if (isset($queues[$qName])) {
                    $qIds[] = $queues[$qName]->id;
                }
            }
            $dbArticle->queues()->sync($qIds);

            // Relacionar etiquetas
            $tIds = [];
            foreach ($art['tags'] as $tName) {
                if (isset($tags[$tName])) {
                    $tIds[] = $tags[$tName]->id;
                }
            }
            $dbArticle->tags()->sync($tIds);

            // Crear versión inicial
            ArticleVersion::updateOrCreate(
                [
                    'article_id' => $dbArticle->id,
                    'version' => 1,
                ],
                [
                    'content' => $dbArticle->content,
                    'created_by' => $author->id,
                    'created_at' => now(),
                ]
            );
        }
    }
}
