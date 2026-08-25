<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Events\MentionCreated;
use App\Modules\CommunicationsModule\Models\Mention;
use App\Modules\PersonnelModule\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Procesa menciones en contenido de texto y las registra.
 *
 * Optimización: carga los usuarios en lote para evitar N+1 queries.
 */
class ProcessMentionsAction
{
    /**
     * Procesa menciones en el contenido y las registra.
     *
     * @param  string  $content  El contenido donde buscar menciones
     * @param  Model  $mentionable  El modelo donde ocurre la mención (News, Shoutout, Comment)
     * @param  int  $mentionerUserId  ID del usuario que hace la mención
     * @return array<int, Mention> Array de menciones creadas
     */
    public function execute(string $content, Model $mentionable, int $mentionerUserId): array
    {
        $mentions = [];

        // Extraer menciones del contenido (@usuario)
        preg_match_all('/@(\w+)/', $content, $matches);

        if (! empty($matches[1])) {
            $mentionedUsernames = array_unique($matches[1]);

            // Los usernames viven en employees; la mención se resuelve al
            // user_id del perfil (esquema actor = users.id).
            $mentionedUserIds = Cache::remember(
                "mentions_{$mentionerUserId}",
                60,
                fn () => Employee::whereIn('username', $mentionedUsernames)
                    ->whereNotNull('user_id')
                    ->pluck('user_id', 'username')
                    ->toArray()
            );

            DB::transaction(function () use ($mentionedUsernames, $mentionedUserIds, $mentionable, $mentionerUserId, &$mentions) {
                foreach ($mentionedUsernames as $username) {
                    $mentionedUserId = $mentionedUserIds[$username] ?? null;

                    if ($mentionedUserId && $mentionedUserId !== $mentionerUserId) {
                        $mention = Mention::create([
                            'mentioned_user_id' => $mentionedUserId,
                            'mentioner_user_id' => $mentionerUserId,
                            'mentionable_type' => get_class($mentionable),
                            'mentionable_id' => $mentionable->id,
                            'context' => $this->extractContext($content, $username),
                            'is_read' => false,
                        ]);

                        event(new MentionCreated($mention));
                        $mentions[] = $mention;
                    }
                }
            });
        }

        return $mentions;
    }

    /**
     * Extrae el contexto alrededor de la mención.
     */
    private function extractContext(string $content, string $username): string
    {
        $mention = "@{$username}";
        $position = strpos($content, $mention);

        if ($position === false) {
            return '';
        }

        $start = max(0, $position - 50);
        $end = min(strlen($content), $position + strlen($mention) + 50);

        $context = substr($content, $start, $end - $start);

        // Agregar indicadores si se truncó
        if ($start > 0) {
            $context = '...'.$context;
        }
        if ($end < strlen($content)) {
            $context .= '...';
        }

        return $context;
    }
}
