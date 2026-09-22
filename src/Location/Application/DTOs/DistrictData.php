<?php

declare(strict_types=1);

namespace Src\Location\Application\DTOs;

final readonly class DistrictData
{
    public function __construct(
        public int $id,
        public int $provinceId,
        public string $name,
    ) {}

    /**
     * @param  object{id:int, province_id:int, name:string}  $model
     */
    public static function fromModel(object $model): self
    {
        return new self(
            id: (int) $model->id,
            provinceId: (int) $model->province_id,
            name: (string) $model->name,
        );
    }

    /**
     * @return array{id:int, province_id:int, name:string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'province_id' => $this->provinceId,
            'name' => $this->name,
        ];
    }
}
