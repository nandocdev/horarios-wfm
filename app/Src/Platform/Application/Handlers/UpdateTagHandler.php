<?php

declare(strict_types=1);

namespace App\Src\Platform\Application\Handlers;

use App\Src\Platform\Application\DTOs\TagDTO;
use App\Src\Platform\Domain\Entities\Tag;
use App\Src\Platform\Domain\Repositories\TagRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTagHandler
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
    ) {}

    public function execute(int $tagId, TagDTO $dto): Tag
    {
        return DB::transaction(function () use ($tagId, $dto) {
            $tag = $this->tagRepository->findById($tagId);

            if (! $tag) {
                throw new \RuntimeException("Tag with ID {$tagId} not found.");
            }

            $slugExists = $this->tagRepository->findBySlug($dto->slug);
            if ($slugExists && $slugExists->id() !== $tagId) {
                throw new \RuntimeException("Another tag with slug '{$dto->slug}' already exists.");
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
