<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\TagDTO;
use App\Src\Platform\Domain\Entities\Tag;
use App\Src\Platform\Domain\Repositories\TagRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreateTagHandler
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(TagDTO $dto): Tag
    {
        return DB::transaction(function () use ($dto) {
            $existing = $this->tagRepository->findBySlug($dto->slug);

            if ($existing) {
                throw new \RuntimeException("Tag with slug '{$dto->slug}' already exists.");
            }

            $tag = Tag::create(
                name: $dto->name,
                slug: $dto->slug,
                color: $dto->color,
                isActive: $dto->isActive,
            );

            return $this->tagRepository->save($tag);
        });
    }
}
