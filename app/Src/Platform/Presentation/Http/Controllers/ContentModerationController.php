<?php

declare(strict_types=1);

namespace App\Src\Platform\Presentation\Http\Controllers;

use App\Src\Platform\Application\Handlers\ModerateContentHandler;
use App\Src\Platform\Presentation\Policies\ContentModerationPolicy;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class ContentModerationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewPending', ContentModerationPolicy::class);

        $pendingNews = News::pendingReview()->with('author')->latest()->paginate(20);
        $pendingPolls = Poll::pendingReview()->latest()->paginate(20);
        $pendingShoutouts = Shoutout::pendingReview()->with('employee')->latest()->paginate(20);

        return view('platform::livewire.admin.moderation.index', compact(
            'pendingNews',
            'pendingPolls',
            'pendingShoutouts'
        ));
    }

    public function approve(Request $request, ModerateContentHandler $handler): RedirectResponse
    {
        $content = $this->resolveContent($request);

        $this->authorize('moderateContent', $content);

        $handler->approve($content, $request->input('notes'));

        return redirect()->back()->with('success', 'Contenido aprobado correctamente.');
    }

    public function reject(Request $request, ModerateContentHandler $handler): RedirectResponse
    {
        $content = $this->resolveContent($request);

        $this->authorize('moderateContent', $content);

        $handler->reject($content, $request->input('notes', ''));

        return redirect()->back()->with('success', 'Contenido rechazado.');
    }

    public function archive(Request $request, ModerateContentHandler $handler): RedirectResponse
    {
        $content = $this->resolveContent($request);

        $this->authorize('moderateContent', $content);

        $handler->archive($content);

        return redirect()->back()->with('success', 'Contenido archivado.');
    }

    public function submitForReview(Request $request, ModerateContentHandler $handler): RedirectResponse
    {
        $content = $this->resolveContent($request);

        $handler->submitForReview($content);

        return redirect()->back()->with('success', 'Contenido enviado a revisión.');
    }

    private function resolveContent(Request $request): \Illuminate\Database\Eloquent\Model
    {
        $type = $request->input('content_type');
        $id = $request->input('content_id');

        return match ($type) {
            'news' => News::findOrFail($id),
            'poll' => Poll::findOrFail($id),
            'shoutout' => Shoutout::findOrFail($id),
            default => throw new \InvalidArgumentException("Invalid content type: {$type}"),
        };
    }
}
