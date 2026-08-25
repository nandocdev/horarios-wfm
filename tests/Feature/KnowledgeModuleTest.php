<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\CoreModule\Models\User;
use App\Modules\KnowledgeModule\Models\KnowledgeArticle;
use App\Modules\KnowledgeModule\Models\KnowledgeCategory;
use App\Modules\KnowledgeModule\Models\Queue;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pruebas de integración y feature para validar la Base de Conocimiento.
 */
class KnowledgeModuleTest extends TestCase
{
    protected User $operator;

    protected User $supervisor;

    protected Queue $queueLow;

    protected Queue $queueHigh;

    protected KnowledgeCategory $category;

    /**
     * Configuración inicial del entorno de prueba.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Registrar permisos
        $permissions = [
            'knowledge.viewAny',
            'knowledge.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Crear usuarios con roles/permisos diferenciados
        $this->operator = User::factory()->create();
        $this->operator->givePermissionTo('knowledge.viewAny');

        $this->supervisor = User::factory()->create();
        $this->supervisor->givePermissionTo('knowledge.manage');

        // Crear categoría y colas base para pruebas de prioridad
        $this->category = KnowledgeCategory::create([
            'name' => 'Procedimientos',
            'description' => 'Categoría de prueba.',
        ]);

        $this->queueLow = Queue::create([
            'name' => 'Solicitud Policlínicas',
            'priority' => 4,
            'is_active' => true,
        ]);

        $this->queueHigh = Queue::create([
            'name' => 'Cancelación Policlínicas',
            'priority' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * Verifica que un operador autenticado puede ver el índice de la base de conocimiento.
     */
    public function test_operator_can_access_knowledge_index(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('knowledge.index'));

        $response->assertStatus(200);
        $response->assertSee('Base de Conocimiento');
    }

    /**
     * Verifica que los resultados se ordenen de manera descendente por la prioridad de la cola asociada.
     */
    public function test_articles_are_ordered_by_max_queue_priority(): void
    {
        // Artículo A en cola de menor prioridad (4)
        $articleA = KnowledgeArticle::create([
            'title' => 'Low Priority Article',
            'slug' => 'low-priority-article',
            'content' => 'Low priority content',
            'category_id' => $this->category->id,
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $this->supervisor->id,
        ]);
        $articleA->queues()->sync([$this->queueLow->id]);

        // Artículo B en cola de mayor prioridad (10)
        $articleB = KnowledgeArticle::create([
            'title' => 'High Priority Article',
            'slug' => 'high-priority-article',
            'content' => 'High priority content',
            'category_id' => $this->category->id,
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $this->supervisor->id,
        ]);
        $articleB->queues()->sync([$this->queueHigh->id]);

        $response = $this->actingAs($this->operator)
            ->get(route('knowledge.index'));

        $response->assertStatus(200);

        // Validar que el artículo de alta prioridad aparece antes en el HTML
        $content = $response->getContent();
        $posB = strpos($content, 'High Priority Article');
        $posA = strpos($content, 'Low Priority Article');

        $this->assertNotFalse($posB);
        $this->assertNotFalse($posA);
        $this->assertTrue($posB < $posA, 'El artículo con cola de mayor prioridad debería aparecer antes en el DOM.');
    }

    /**
     * Verifica que los operadores no puedan ver artículos no publicados (borradores/revisión),
     * pero los supervisores sí.
     */
    public function test_operator_cannot_view_unpublished_articles(): void
    {
        $draftArticle = KnowledgeArticle::create([
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'content' => 'Draft content',
            'status' => 'draft',
            'created_by' => $this->supervisor->id,
        ]);

        // Operador sin permisos de gestión recibe 403
        $response = $this->actingAs($this->operator)
            ->get(route('knowledge.show', $draftArticle->slug));

        $response->assertStatus(403);

        // Supervisor con permisos de gestión recibe 200
        $response2 = $this->actingAs($this->supervisor)
            ->get(route('knowledge.show', $draftArticle->slug));

        $response2->assertStatus(200);
        $response2->assertSee('Draft Article');
    }

    /**
     * Verifica que solo los usuarios autorizados (supervisores) puedan entrar al panel de gestión.
     */
    public function test_only_authorized_users_can_access_admin_panel(): void
    {
        // Operador común recibe 403
        $response = $this->actingAs($this->operator)
            ->get(route('knowledge.admin'));

        $response->assertStatus(403);

        // Supervisor recibe 200
        $response2 = $this->actingAs($this->supervisor)
            ->get(route('knowledge.admin'));

        $response2->assertStatus(200);
        $response2->assertSee('Gestión de Base de Conocimiento');
    }

    /**
     * Verifica acceso a la vista de creación de artículos.
     */
    public function test_only_authorized_users_can_access_create_page(): void
    {
        $this->actingAs($this->operator)
            ->get(route('knowledge.create'))
            ->assertStatus(403);

        $this->actingAs($this->supervisor)
            ->get(route('knowledge.create'))
            ->assertStatus(200)
            ->assertSee('Nuevo Artículo');
    }

    /**
     * Verifica acceso a la vista de edición de artículos.
     */
    public function test_only_authorized_users_can_access_edit_page(): void
    {
        $article = KnowledgeArticle::create([
            'title' => 'Article to Edit',
            'slug' => 'article-to-edit',
            'content' => 'Content here',
            'created_by' => $this->supervisor->id,
        ]);

        $this->actingAs($this->operator)
            ->get(route('knowledge.edit', $article->id))
            ->assertStatus(403);

        $this->actingAs($this->supervisor)
            ->get(route('knowledge.edit', $article->id))
            ->assertStatus(200)
            ->assertSee('Editar Artículo');
    }
}
/**
 * [RIESGOS]
 * - Fugas de datos en testing → El uso del trait RefreshDatabase limpia y aisla las transacciones de BD para cada test individual.
 */
