<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\DTOs;

/**
 * DTO inmutable con los datos capturados de una unidad (piso) del directorio.
 */
readonly class UnitDTO
{
    /**
     * @param  array<int, array{name: string, door_id: string|null, attention_hours: string, results_hours: string|null, contact_role: string, contact_extension: string, contact_email: string|null}>  $services
     */
    public function __construct(
        public ?int $building_id,
        public ?string $new_building,
        public ?string $director_name,
        public ?string $subdirector_name,
        public ?string $administrator_name,
        public ?string $sector,
        public ?string $level,
        public ?string $new_level,
        public bool $is_active,
        public array $services,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $services = array_values(array_filter(
            array_map(static function (array $row): array {
                return [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'door_id' => ! empty($row['door_id']) ? trim((string) $row['door_id']) : null,
                    'attention_hours' => trim((string) ($row['attention_hours'] ?? '')),
                    'results_hours' => ! empty($row['results_hours']) ? trim((string) $row['results_hours']) : null,
                    'contact_role' => trim((string) ($row['contact_role'] ?? '')),
                    'contact_extension' => trim((string) ($row['contact_extension'] ?? '')),
                    'contact_email' => ! empty($row['contact_email']) ? trim((string) $row['contact_email']) : null,
                ];
            }, (array) ($data['services'] ?? [])),
            static fn (array $row): bool => $row['name'] !== '',
        ));

        return new self(
            building_id: ! empty($data['building_id']) ? (int) $data['building_id'] : null,
            new_building: ! empty($data['new_building']) ? trim((string) $data['new_building']) : null,
            director_name: ! empty($data['director_name']) ? trim((string) $data['director_name']) : null,
            subdirector_name: ! empty($data['subdirector_name']) ? trim((string) $data['subdirector_name']) : null,
            administrator_name: ! empty($data['administrator_name']) ? trim((string) $data['administrator_name']) : null,
            sector: ! empty($data['sector']) ? trim((string) $data['sector']) : null,
            level: ! empty($data['level']) ? trim((string) $data['level']) : null,
            new_level: ! empty($data['new_level']) ? trim((string) $data['new_level']) : null,
            is_active: (bool) ($data['is_active'] ?? true),
            services: $services,
        );
    }
}
