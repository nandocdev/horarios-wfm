<?php

declare(strict_types=1);

namespace App\Modules\ConnectModule\Actions;

use RuntimeException;

class FetchCiscoAgentSnapshotAction
{
    public function __construct(
        private readonly FetchCiscoFinesseResourceAction $fetchCiscoFinesseResourceAction,
    ) {}

    /**
     * @return array{username:string,agent:array<string,mixed>,dialogs:list<array<string,mixed>>,fetched_at:string}
     */
    public function execute(?string $username = null): array
    {
        $resolvedUsername = trim($username ?: (string) config('contact-center.cisco.username', ''));

        if ($resolvedUsername === '') {
            throw new RuntimeException('No existe un usuario configurado para Cisco Finesse.');
        }

        $agent = $this->fetchCiscoFinesseResourceAction->execute('User/'.rawurlencode($resolvedUsername));

        return [
            'username' => $resolvedUsername,
            'agent' => $agent,
            'dialogs' => $this->fetchDialogs($resolvedUsername),
            'fetched_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchDialogs(string $username): array
    {
        try {
            $dialogs = $this->fetchCiscoFinesseResourceAction->execute('User/'.rawurlencode($username).'/Dialogs');
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'HTTP 404')) {
                return [];
            }

            throw $exception;
        }

        $items = $dialogs['Dialog'] ?? $dialogs;

        if ($items === [] || $items === null || $items === '') {
            return [];
        }

        if (! is_array($items)) {
            return blank($items) ? [] : [['value' => $items]];
        }

        if (array_is_list($items)) {
            return array_values(array_filter($items, static fn ($item) => is_array($item) && $item !== ['value' => '']));
        }

        return $items === ['value' => ''] ? [] : [$items];
    }
}
