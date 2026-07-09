<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Modules\CoreModule\Models\User;
use App\Modules\DocumentationModule\Models\WikiArticle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class DocumentationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear permisos necesarios
        $permissions = [
            'articles.viewAny',
            'articles.view',
            'articles.manage',
            'menu.admin', // Requerido por el sidebar en algunos tests
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Crear un usuario para las pruebas
        $this->user = User::factory()->create();
    }

    public function test_an_authenticated_user_can_access_the_documentation_index()
    {
        $response = $this->actingAs($this->user)
            ->get(route('documentation.index'));

        $response->assertStatus(200);
        $response->assertSee('Documentación de Usuario');
    }

    public function test_users_can_view_a_published_article()
    {
        $article = WikiArticle::create([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'content' => 'Test content',
            'is_published' => true,
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('documentation.show', $article->slug));

        $response->assertStatus(200);
        $response->assertSee('Test Article');
        $response->assertSee('Test content');
    }

    public function test_users_cannot_view_an_unpublished_article()
    {
        $article = WikiArticle::create([
            'title' => 'Private Article',
            'slug' => 'private-article',
            'content' => 'Private content',
            'is_published' => false,
            'author_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('documentation.show', $article->slug));

        $response->assertStatus(404);
    }

    public function test_only_authorized_users_can_access_the_management_page()
    {
        $response = $this->actingAs($this->user)
            ->get(route('documentation.admin.articles'));

        $response->assertStatus(403);

        $this->user->givePermissionTo('articles.manage');

        $response = $this->actingAs($this->user)
            ->get(route('documentation.admin.articles'));

        $response->assertStatus(200);
        $response->assertSee('Gestión de Documentación');
    }
}
