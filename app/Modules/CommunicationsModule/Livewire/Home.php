<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Livewire;

use App\Modules\CommunicationsModule\Models\Comment;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Notification;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Reaction;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Livewire\Component;

/**
 * Componente principal para la Home dinámica (Página de Bienvenida).
 * Orquesta la carga de noticias, reconocimientos y encuestas operativas.
 */
class Home extends Component
{
    public array $pollForm = [
        'answer' => null,
    ];

    public array $commentForm = [
        'content' => '',
        'news_id' => null,
    ];

    public array $shoutoutForm = [
        'employee_id' => null,
        'message' => '',
        'workflow_action' => 'submit_review',
    ];

    public ?int $selectedNewsId = null;

    public ?int $viewingNewsId = null;

    public bool $showNewsModal = false;

    public bool $showShoutoutModal = false;

    public bool $showComments = false;

    /**
     * Valida que la acción sea ejecutada por un usuario autenticado.
     */
    private function ensureAuthenticated(string $actionLabel): bool
    {
        if (auth()->check()) {
            return true;
        }

        toast("Inicia sesión para {$actionLabel}.", variant: 'warning');

        return false;
    }

    /**
     * Carga una noticia para mostrarla en el modal.
     */
    public function viewNews(int $newsId): void
    {
        $this->viewingNewsId = $newsId;
        $this->commentForm['news_id'] = $newsId;
        $this->showNewsModal = true;
    }

    /**
     * Cierra el modal de noticia.
     */
    public function closeNewsModal(): void
    {
        $this->showNewsModal = false;
        $this->viewingNewsId = null;
    }

    /**
     * Votación en la encuesta activa.
     */
    public function submitPoll(): void
    {
        if (! $this->ensureAuthenticated('participar en la encuesta')) {
            return;
        }

        /** @var Poll|null $poll */
        $poll = Poll::where('is_active', true)->latest()->first();

        if (! $poll) {
            toast('No hay encuestas activas en este momento.', variant: 'warning');

            return;
        }

        if ($poll->hasVoted((int) auth()->id())) {
            toast('Ya has participado en esta encuesta.', variant: 'error');

            return;
        }

        $this->validate([
            'pollForm.answer' => 'required|string',
        ], [], [
            'pollForm.answer' => 'respuesta',
        ]);

        $poll->responses()->create([
            'user_id' => auth()->id(),
            'answer' => $this->pollForm['answer'],
        ]);

        $poll->recordVote($this->pollForm['answer']);

        toast('¡Voto registrado! Gracias por participar.');
        $this->pollForm['answer'] = null;
    }

    /**
     * Crear un comentario en una noticia.
     */
    public function submitComment(): void
    {
        if (! $this->ensureAuthenticated('comentar esta noticia')) {
            return;
        }

        $this->validate([
            'commentForm.content' => 'required|string|max:1000',
            'commentForm.news_id' => 'required|exists:news,id',
        ], [], [
            'commentForm.content' => 'contenido del comentario',
            'commentForm.news_id' => 'noticia',
        ]);

        Comment::create([
            'news_id' => $this->commentForm['news_id'],
            'user_id' => auth()->id(),
            'content' => $this->commentForm['content'],
            'is_active' => true,
        ]);

        $this->commentForm['content'] = '';
        $this->selectedNewsId = null;
        $this->showComments = false;

        session()->flash('success', 'Comentario publicado correctamente.');
    }

    /**
     * Agregar o quitar reacción en un shoutout.
     */
    public function toggleReaction(int $shoutoutId, string $type): void
    {
        if (! $this->ensureAuthenticated('reaccionar a este reconocimiento')) {
            return;
        }

        $shoutout = Shoutout::findOrFail($shoutoutId);
        $userId = (int) auth()->id();

        $existingReaction = Reaction::where('shoutout_id', $shoutoutId)
            ->where('user_id', $userId)
            ->first();
        /** @var Reaction|null $existingReaction */
        if ($existingReaction) {
            if ($existingReaction->type === $type) {
                // Quitar reacción si es del mismo tipo
                $existingReaction->delete();
            } else {
                // Cambiar tipo de reacción
                $existingReaction->update(['type' => $type]);
            }
        } else {
            // Crear nueva reacción
            Reaction::create([
                'shoutout_id' => $shoutoutId,
                'user_id' => $userId,
                'type' => $type,
            ]);
        }
    }

    /**
     * Abre el modal para crear un nuevo reconocimiento.
     */
    public function openShoutoutModal(): void
    {
        if (! $this->ensureAuthenticated('dar un reconocimiento')) {
            return;
        }

        $this->reset('shoutoutForm');
        $this->shoutoutForm['workflow_action'] = 'submit_review';
        $this->showShoutoutModal = true;
    }

    /**
     * Cierra el modal de reconocimiento.
     */
    public function closeShoutoutModal(): void
    {
        $this->showShoutoutModal = false;
        $this->reset('shoutoutForm');
    }

    /**
     * Procesa la creación de un nuevo reconocimiento.
     */
    public function submitShoutout(\App\Modules\CommunicationsModule\Actions\CreateShoutoutAction $action): void
    {
        if (! $this->ensureAuthenticated('dar un reconocimiento')) {
            return;
        }

        $this->validate([
            'shoutoutForm.employee_id' => 'required|exists:employees,id',
            'shoutoutForm.message' => 'required|string|min:5|max:200',
        ], [], [
            'shoutoutForm.employee_id' => 'compañero',
            'shoutoutForm.message' => 'mensaje de reconocimiento',
        ]);

        $dto = \App\Modules\CommunicationsModule\DTOs\ShoutoutDTO::fromArray([
            'employee_id' => $this->shoutoutForm['employee_id'],
            'message' => $this->shoutoutForm['message'],
            'workflow_action' => $this->shoutoutForm['workflow_action'],
            'is_active' => true,
        ]);

        $action->execute($dto);

        toast('¡Reconocimiento enviado correctamente! Quedará visible una vez sea aprobado.');
        $this->closeShoutoutModal();
    }

    /**
     * Mostrar/ocultar comentarios de una noticia.
     */
    public function toggleComments(int $newsId): void
    {
        if ($this->selectedNewsId === $newsId && $this->showComments) {
            $this->showComments = false;
            $this->selectedNewsId = null;
        } else {
            $this->selectedNewsId = $newsId;
            $this->showComments = true;
        }
    }

    /**
     * Seleccionar noticia para comentar.
     */
    public function selectNewsForComment(int $newsId): void
    {
        if (! $this->ensureAuthenticated('comentar esta noticia')) {
            return;
        }

        $this->commentForm['news_id'] = $newsId;
        $this->selectedNewsId = $newsId;
    }

    /**
     * Renderizado del componente.
     */
    public function render()
    {
        $isAuthenticated = auth()->check();

        $newsItems = News::with('author', 'media', 'comments.user', 'categories')
            ->withCount('comments')
            ->where('is_active', true)
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('archive_at')
                    ->orWhere('archive_at', '>', now());
            })
            ->latest('published_at')
            ->take(4)
            ->get();

        $shoutoutItems = Shoutout::with(['employee.user', 'employee.team', 'reactions', 'media'])
            ->withCount('reactions')
            ->where('is_active', true)
            ->where('status', 'published')
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('archive_at')
                    ->orWhere('archive_at', '>', now());
            })
            ->latest()
            ->take(6)
            ->get();

        // Agregar reacciones del usuario actual a los shoutouts
        $shoutoutItems->each(function ($shoutout) use ($isAuthenticated) {
            $shoutout->user_reactions = $isAuthenticated
                ? $shoutout->reactions
                    ->where('user_id', auth()->id())
                    ->pluck('type')
                    ->toArray()
                : [];
        });

        $featuredShoutout = $shoutoutItems->sortByDesc('reactions_count')->first();

        /** @var Poll|null $activePoll */
        $activePoll = Poll::where('is_active', true)
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('scheduled_at')
                    ->orWhere('scheduled_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('archive_at')
                    ->orWhere('archive_at', '>', now());
            })
            ->latest()
            ->first();

        $selectedNews = $this->selectedNewsId
            ? News::with('comments.user')->find($this->selectedNewsId)
            : null;

        $viewingNews = $this->viewingNewsId
            ? News::with(['author', 'media', 'categories', 'comments.user' => function($q) {
                $q->where('is_active', true)->latest();
            }])->find($this->viewingNewsId)
            : null;

        $employees = $isAuthenticated
            ? \App\Modules\PersonnelModule\Models\Employee::active()
                ->where('id', '!=', auth()->user()->employee?->id) // No auto-shoutout
                ->orderBy('first_name')
                ->get(['id', 'first_name', 'last_name'])
            : collect();

        $recentNotifications = $isAuthenticated
            ? Notification::where('user_id', auth()->id())
                ->where('is_read', false)
                ->latest()
                ->take(5)
                ->get()
            : collect();

        /** @phpstan-ignore-next-line Livewire agrega layout() al view configurable de page components. */
        return view('communications::livewire.home', [
            'isAuthenticated' => $isAuthenticated,
            'newsItems' => $newsItems,
            'shoutoutItems' => $shoutoutItems,
            'activePoll' => $activePoll,
            'hasVotedInActivePoll' => $isAuthenticated && $activePoll !== null
                ? $activePoll->hasVoted((int) auth()->id())
                : false,
            'selectedNews' => $selectedNews,
            'viewingNews' => $viewingNews,
            'recentNotifications' => $recentNotifications,
            'featuredShoutout' => $featuredShoutout,
            'employees' => $employees,
        ])->layout('layouts.app', [
            'title' => 'Comunicaciones',
        ]);
    }
}
