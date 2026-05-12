<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Actions;

use App\Modules\CommunicationsModule\Events\MentionCreated;
use App\Modules\CommunicationsModule\Models\Mention;
use App\Modules\CoreModule\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Procesa menciones en contenido de texto y las registra.
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

            DB::transaction(function () use ($mentionedUsernames, $mentionable, $mentionerUserId, &$mentions) {
                foreach ($mentionedUsernames as $username) {
                    $user = User::where('username', $username)->first();

                    if ($user && $user->id !== $mentionerUserId) {
                        $mention = Mention::create([
                            'mentioned_user_id' => $user->id,
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
