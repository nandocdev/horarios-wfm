<?php

declare(strict_types=1);

namespace Src\Location\Application\DTOs;

final readonly class ProvinceData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code = null,
    ) {}

    /**
     * @param  object{id:int, name:string, code?:?string}  $model
     */
    public static function fromModel(object $model): self
    {
        return new self(
            id: (int) $model->id,
            name: (string) $model->name,
            code: $model->code ?? null,
        );
    }

    /**
     * @return array{id:int, name:string, code:?string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
        ];
    }
}
