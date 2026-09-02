<?php

declare(strict_types=1);

namespace App\Modules\DirectoryModule\DTOs;

/**
 * DTO inmutable con los datos capturados de una unidad (piso) del directorio.
 */
readonly class UnitDTO
{
    /**
     * @param  array<int, array{name: string, is_person: bool, office_number: string|null, door_id: string|null, attention_hours: string|null, results_hours: string|null, contact_role: string|null, contact_extension: string|null, contact_email: string|null, phones: array<int, array{number: string, type: string, description: string|null}>}>  $services
     */
    public function __construct(
        public ?int $building_id,
        public ?string $new_building,
        public ?string $director_name,
        public ?string $subdirector_name,
        public ?string $administrator_name,
        public ?string $color_identifier,
        public ?string $description,
        public ?string $sector,
        public ?string $level,
        public ?string $new_level,
        public ?string $door_range,
        public ?string $wing_sector,
        public ?string $attention_schedule,
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
                $phones = array_values(array_filter(
                    array_map(static function (array $ph): array {
                        $num = preg_replace('/[^0-9\-]/', '', trim((string) ($ph['number'] ?? ''))) ?? '';
                        // inferir tipo si no viene
                        $type = ! empty($ph['type']) ? strtoupper(trim((string) $ph['type'])) : null;
                        if ($type === null && $num !== '') {
                            $type = match (true) {
                                preg_match('/^\d{5}$/', $num) === 1 => 'CISCO',
                                str_starts_with($num, '513-') => 'DIRECTO',
                                preg_match('/^6\d{3}-\d{4}$/', $num) === 1 => 'MOVIL',
                                str_contains($num, '6535') || str_contains($num, 'WhatsApp') => 'WHATSAPP_CHAT',
                                default => 'DIRECTO',
                            };
                        }

                        return [
                            'number' => $num,
                            'type' => $type ?? 'DIRECTO',
                            'description' => ! empty($ph['description']) ? trim((string) $ph['description']) : null,
                        ];
                    }, (array) ($row['phones'] ?? [])),
                    static fn (array $p): bool => $p['number'] !== ''
                ));

                // Compat: si no hay phones pero hay contact_extension legacy, convertir
                if (empty($phones) && ! empty($row['contact_extension'])) {
                    $num = preg_replace('/[^0-9\-]/', '', trim((string) $row['contact_extension'])) ?? '';
                    if ($num !== '') {
                        $phones[] = ['number' => $num, 'type' => 'CISCO', 'description' => null];
                    }
                }

                return [
                    'name' => trim((string) ($row['name'] ?? '')),
                    'is_person' => array_key_exists('is_person', $row) ? (bool) $row['is_person'] : true,
                    'office_number' => ! empty($row['office_number']) ? trim((string) $row['office_number']) : null,
                    'door_id' => ! empty($row['door_id']) ? trim((string) $row['door_id']) : null,
                    'attention_hours' => ! empty($row['attention_hours']) ? trim((string) $row['attention_hours']) : null,
                    'results_hours' => ! empty($row['results_hours']) ? trim((string) $row['results_hours']) : null,
                    'contact_role' => ! empty($row['contact_role']) ? trim((string) $row['contact_role']) : null,
                    'contact_extension' => ! empty($row['contact_extension']) ? preg_replace('/[^0-9\-]/', '', trim((string) $row['contact_extension'])) : null,
                    'contact_email' => ! empty($row['contact_email']) ? trim((string) $row['contact_email']) : null,
                    'phones' => $phones,
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
            color_identifier: ! empty($data['color_identifier']) ? trim((string) $data['color_identifier']) : null,
            description: ! empty($data['description']) ? trim((string) $data['description']) : null,
            sector: ! empty($data['sector']) ? trim((string) $data['sector']) : null,
            level: ! empty($data['level']) ? trim((string) $data['level']) : null,
            new_level: ! empty($data['new_level']) ? trim((string) $data['new_level']) : null,
            door_range: ! empty($data['door_range']) ? trim((string) $data['door_range']) : null,
            wing_sector: ! empty($data['wing_sector']) ? trim((string) $data['wing_sector']) : null,
            attention_schedule: ! empty($data['attention_schedule']) ? trim((string) $data['attention_schedule']) : null,
            is_active: (bool) ($data['is_active'] ?? true),
            services: $services,
        );
    }
}
