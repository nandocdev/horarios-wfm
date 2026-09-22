<?php

declare(strict_types=1);

namespace Src\Location\Application\DTOs;

final readonly class TownshipData
{
    public function __construct(
        public int $id,
        public int $districtId,
        public string $name,
    ) {}

    /**
     * @param  object{id:int, district_id:int, name:string}  $model
     */
    public static function fromModel(object $model): self
    {
        return new self(
            id: (int) $model->id,
            districtId: (int) $model->district_id,
            name: (string) $model->name,
        );
    }

    /**
     * @return array{id:int, district_id:int, name:string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'district_id' => $this->districtId,
            'name' => $this->name,
        ];
    }
}
