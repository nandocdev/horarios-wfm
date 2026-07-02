<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Livewire;

use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\PollResponse;
use App\Modules\CommunicationsModule\Models\Shoutout;
use App\Modules\CommunicationsModule\Models\Employee;
use App\Src\Platform\Application\DTOs\CommentDTO;
use App\Src\Platform\Application\DTOs\ReactionDTO;
use App\Src\Platform\Application\DTOs\ShoutoutDTO;
use App\Src\Platform\Application\Handlers\CreateCommentHandler;
use App\Src\Platform\Application\Handlers\CreateShoutoutHandler;
use App\Src\Platform\Application\Handlers\ToggleReactionHandler;
use App\Src\Platform\Domain\ValueObjects\ReactionType;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Comunicaciones')]
class Home extends Component
{
    public ?int $selectedNewsId = null;
    public bool $showComments = false;
    public bool $showNewsModal = false;
    public bool $showShoutoutModal = false;

    public array $commentForm = ['news_id' => null, 'content' => ''];
    public array $shoutoutForm = ['employee_id' => null, 'message' => ''];
    public array $pollForm = ['answer' => ''];

    public function openShoutoutModal(): void
    {
        $this->reset('shoutoutForm');
        $this->showShoutoutModal = true;
    }

    public function selectNewsForComment(int $newsId): void
    {
        $this->commentForm = ['news_id' => $newsId, 'content' => ''];
    }

    public function viewNews(int $newsId): void
    {
        $this->selectedNewsId = $newsId;
        $this->showNewsModal = true;
    }

    public function closeNewsModal(): void
    {
        $this->showNewsModal = false;
        $this->selectedNewsId = null;
    }

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

    public function submitComment(): void
    {
        $this->validate([
            'commentForm.content' => ['required', 'string', 'max:1000'],
            'commentForm.news_id' => ['required', 'integer', 'exists:communications_news,id'],
        ]);

        $handler = app(CreateCommentHandler::class);
        $dto = new CommentDTO(content: $this->commentForm['content']);

        $handler->execute($dto, (int) $this->commentForm['news_id'], auth()->id());

        $this->commentForm = ['news_id' => null, 'content' => ''];
        $this->dispatch('comment-added');
    }

    public function submitShoutout(): void
    {
        $this->validate([
            'shoutoutForm.employee_id' => ['required', 'integer', 'exists:personnel_employees,id'],
            'shoutoutForm.message' => ['required', 'string', 'max:200'],
        ]);

        $handler = app(CreateShoutoutHandler::class);
        $dto = ShoutoutDTO::fromArray($this->shoutoutForm);
        $handler->execute($dto, auth()->id());

        $this->showShoutoutModal = false;
        $this->reset('shoutoutForm');
        $this->dispatch('shoutout-created');
    }

    public function toggleReaction(int $shoutoutId, string $type): void
    {
        if (! auth()->check()) {
            return;
        }

        $handler = app(ToggleReactionHandler::class);
        $dto = new ReactionDTO(type: ReactionType::from($type));

        $handler->execute($dto, $shoutoutId, auth()->id());
    }

    public function submitPoll(): void
    {
        $this->validate([
            'pollForm.answer' => ['required', 'string', 'max:255'],
        ]);

        $activePoll = Poll::active()->first();

        if (! $activePoll) {
            return;
        }

        $exists = PollResponse::where('poll_id', $activePoll->id)
            ->where('user_id', auth()->id())
            ->exists();

        if ($exists) {
            $this->addError('pollForm.answer', 'Ya has votado en esta encuesta.');
            return;
        }

        PollResponse::create([
            'poll_id' => $activePoll->id,
            'user_id' => auth()->id(),
            'answer' => $this->pollForm['answer'],
        ]);

        $this->dispatch('poll-voted');
    }

    public function render()
    {
        $newsItems = News::published()
            ->with(['author', 'categories', 'comments.user'])
            ->withCount('comments')
            ->latest('published_at')
            ->take(6)
            ->get();

        $featuredShoutout = Shoutout::published()
            ->with('employee.user')
            ->latest()
            ->first();

        $shoutoutItems = Shoutout::published()
            ->with(['employee.user', 'employee.team', 'reactions'])
            ->latest()
            ->take(6)
            ->get();

        $activePoll = Poll::active()->first();
        $hasVotedInActivePoll = false;

        if ($activePoll && auth()->check()) {
            $hasVotedInActivePoll = PollResponse::where('poll_id', $activePoll->id)
                ->where('user_id', auth()->id())
                ->exists();
        }

        $viewingNews = null;
        if ($this->showNewsModal && $this->selectedNewsId) {
            $viewingNews = News::with(['author', 'categories', 'comments.user', 'media'])->find($this->selectedNewsId);
        }

        $employees = Employee::with('user')->get();

        $reactionTypes = [
            'like' => '👍',
            'love' => '❤️',
            'celebrate' => '🎉',
            'support' => '🤝',
        ];

        return view('platform::livewire.home', [
            'newsItems' => $newsItems,
            'featuredShoutout' => $featuredShoutout,
            'shoutoutItems' => $shoutoutItems,
            'activePoll' => $activePoll,
            'hasVotedInActivePoll' => $hasVotedInActivePoll,
            'viewingNews' => $viewingNews,
            'employees' => $employees,
            'reactionTypes' => $reactionTypes,
            'isAuthenticated' => auth()->check(),
        ]);
    }
}
