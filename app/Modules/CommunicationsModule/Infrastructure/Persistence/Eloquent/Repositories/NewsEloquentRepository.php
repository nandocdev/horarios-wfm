<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\Repositories;

use App\Modules\CommunicationsModule\Domain\Aggregates\News;
use App\Modules\CommunicationsModule\Domain\Repositories\NewsRepository;
use App\Modules\CommunicationsModule\Domain\ValueObjects\ContentBody;
use App\Modules\CommunicationsModule\Domain\ValueObjects\DateRange;
use App\Modules\CommunicationsModule\Domain\ValueObjects\NewsContent;
use App\Modules\CommunicationsModule\Domain\ValueObjects\PersonId;
use App\Modules\CommunicationsModule\Domain\ValueObjects\Slug;
use App\Modules\CommunicationsModule\Infrastructure\Persistence\Eloquent\NewsModel;
use DateTimeImmutable;

final class NewsEloquentRepository implements NewsRepository
{
    public function save(News $news): void
    {
        $data = [
            'title' => $news->content()->title(),
            'slug' => $news->content()->slug()->value(),
            'excerpt' => $news->content()->excerpt(),
            'content' => $news->content()->body()->value(),
            'author_id' => $news->authorId()->value(),
            'is_active' => $news->isActive(),
            'status' => $news->status()->value,
            'scheduled_at' => $news->dateRange()->scheduledAt(),
            'archive_at' => $news->dateRange()->archiveAt(),
            'published_at' => $news->publishedAt(),
        ];

        if ($news->id() !== null) {
            $model = NewsModel::findOrFail($news->id());
            $model->update($data);
        } else {
            $model = NewsModel::create($data);
            $news->setId($model->id);
        }

        if (! empty($news->content()->title())) {
            $model->categories()->sync($this->resolveCategoryIds($news));
            $model->tags()->sync($this->resolveTagIds($news));
        }
    }

    public function findById(int $id): ?News
    {
        $model = NewsModel::with(['author', 'categories', 'tags'])->find($id);

        if ($model === null) {
            return null;
        }

        return $this->toDomain($model);
    }

    public function findScheduledToPublish(): array
    {
        return NewsModel::scheduledToPublish()
            ->get()
            ->map(fn (NewsModel $m) => $this->toDomain($m))
            ->all();
    }

    public function findScheduledToArchive(): array
    {
        return NewsModel::scheduledToArchive()
            ->get()
            ->map(fn (NewsModel $m) => $this->toDomain($m))
            ->all();
    }

    public function findPendingReview(): array
    {
        return NewsModel::pendingReview()
            ->get()
            ->map(fn (NewsModel $m) => $this->toDomain($m))
            ->all();
    }

    public function paginate(array $filters, int $perPage = 20, int $page = 1): array
    {
        $query = NewsModel::with('author');

        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'ilike', "%{$filters['search']}%")
                    ->orWhere('content', 'ilike', "%{$filters['search']}%");
            });
        }

        $paginator = $query->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())
            ->map(fn (NewsModel $m) => $this->toDomain($m))
            ->all();

        return [
            'items' => $items,
            'total' => $paginator->total(),
            'perPage' => $paginator->perPage(),
            'page' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'paginator' => $paginator,
        ];
    }

    public function count(array $filters): int
    {
        return NewsModel::count();
    }

    private function toDomain(NewsModel $model): News
    {
        $newsContent = new NewsContent(
            title: $model->title,
            slug: new Slug($model->slug),
            body: new ContentBody($model->content),
            excerpt: $model->excerpt,
        );

        $news = News::draft(
            content: $newsContent,
            authorId: new PersonId($model->author_id),
            dateRange: new DateRange(
                scheduledAt: $model->scheduled_at ? DateTimeImmutable::createFromMutable($model->scheduled_at) : null,
                archiveAt: $model->archive_at ? DateTimeImmutable::createFromMutable($model->archive_at) : null,
            ),
            isActive: $model->is_active,
        );

        if ($model->id) {
            $news->setId($model->id);
        }

        if ($model->published_at) {
            $ref = new \ReflectionProperty($news, 'publishedAt');
            $ref->setAccessible(true);
            $ref->setValue($news, DateTimeImmutable::createFromMutable($model->published_at));
        }

        return $news;
    }

    private function resolveCategoryIds(News $news): array
    {
        return [];
    }

    private function resolveTagIds(News $news): array
    {
        return [];
    }
}
