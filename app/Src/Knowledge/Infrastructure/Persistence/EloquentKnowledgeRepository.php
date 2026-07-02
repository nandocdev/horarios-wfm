<?php

declare(strict_types=1);

namespace App\Src\Knowledge\Infrastructure\Persistence;

use App\Src\Knowledge\Application\Mappers\KnowledgeMapper;
use App\Src\Knowledge\Domain\Entities\Article;
use App\Src\Knowledge\Domain\Entities\Category;
use App\Src\Knowledge\Domain\Repositories\KnowledgeRepositoryInterface;
use Illuminate\Support\Str;

final class EloquentKnowledgeRepository implements KnowledgeRepositoryInterface
{
    public function saveArticle(Article $article): Article
    {
        $slug = Str::slug($article->title()) . '-' . ($article->id() ?? uniqid());

        $eloquent = EloquentArticle::updateOrCreate(
            ['id' => $article->id()],
            [
                'title' => $article->title(),
                'slug' => $slug,
                'summary' => $article->summary(),
                'content' => $article->content(),
                'category_id' => $article->categoryId(),
                'status' => $article->status(),
                'version' => $article->version(),
                'published_at' => $article->publishedAt()?->format('Y-m-d H:i:s'),
                'expires_at' => $article->expiresAt()?->format('Y-m-d H:i:s'),
                'created_by' => $article->createdBy(),
            ],
        );

        if (! empty($article->tagNames())) {
            $tagIds = [];
            foreach ($article->tagNames() as $name) {
                $tag = EloquentTag::firstOrCreate(['name' => Str::lower(trim($name))]);
                $tagIds[] = $tag->id;
            }
            $eloquent->tags()->sync($tagIds);
        }

        if (! empty($article->queueIds())) {
            $eloquent->queues()->sync($article->queueIds());
        }

        return KnowledgeMapper::articleToDomain($eloquent);
    }

    public function findArticleById(int $id): ?Article
    {
        $eloquent = EloquentArticle::with('tags', 'queues')->find($id);
        return $eloquent ? KnowledgeMapper::articleToDomain($eloquent) : null;
    }

    public function searchArticles(?string $query = null, ?int $categoryId = null, ?string $tag = null, ?string $status = null): array
    {
        $q = EloquentArticle::with('tags', 'queues');

        if ($status) {
            $q->where('status', $status);
        }

        if ($categoryId) {
            $q->where('category_id', $categoryId);
        }

        if ($tag) {
            $q->whereHas('tags', fn ($t) => $t->where('name', $tag));
        }

        if ($query) {
            $search = "%{$query}%";
            $q->where(function ($sub) use ($search) {
                $sub->where('title', 'ilike', $search)
                    ->orWhere('summary', 'ilike', $search)
                    ->orWhere('content', 'ilike', $search);
            });
        }

        return $q->latest()->get()
            ->map(fn (EloquentArticle $e) => KnowledgeMapper::articleToDomain($e))
            ->toArray();
    }

    public function deleteArticle(int $id): void
    {
        EloquentArticle::findOrFail($id)->delete();
    }

    public function findAllCategories(): array
    {
        return EloquentCategory::orderBy('name')->get()
            ->map(fn (EloquentCategory $e) => KnowledgeMapper::categoryToDomain($e))
            ->toArray();
    }

    public function findCategoryById(int $id): ?Category
    {
        $eloquent = EloquentCategory::find($id);
        return $eloquent ? KnowledgeMapper::categoryToDomain($eloquent) : null;
    }
}
