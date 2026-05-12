<?php

declare(strict_types=1);

namespace App\Modules\CommunicationsModule\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CommunicationsModule\Actions\ModerateContentAction;
use App\Modules\CommunicationsModule\Http\Requests\ModerateContentRequest;
use App\Modules\CommunicationsModule\Models\News;
use App\Modules\CommunicationsModule\Models\Poll;
use App\Modules\CommunicationsModule\Models\Shoutout;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Controlador para moderación de contenido.
 *
 * Maneja aprobación, rechazo y gestión de estados de contenido.
 */
class ContentModerationController extends Controller
{
    /**
     * Muestra el panel de moderación con contenido pendiente.
     */
    public function index(): View
    {
        $this->authorize('viewPending', News::class);

        $pendingNews = News::pendingReview()
            ->with('author')
            ->latest()
            ->paginate(20);

        $pendingPolls = Poll::pendingReview()
            ->latest()
            ->paginate(20);

        $pendingShoutouts = Shoutout::pendingReview()
            ->with('employee')
            ->latest()
            ->paginate(20);

        return view('communications::admin.moderation.index', compact(
            'pendingNews',
            'pendingPolls',
            'pendingShoutouts'
        ));
    }

    /**
     * Aprueba contenido.
     */
    public function approve(
        ModerateContentRequest $request,
        ModerateContentAction $action
    ): RedirectResponse {
        $content = $request->getContent();

        $this->authorize('moderateContent', $content);

        $action->approve($content, $request->input('notes'));

        return redirect()
            ->back()
            ->with('success', 'Contenido aprobado correctamente.');
    }

    /**
     * Rechaza contenido.
     */
    public function reject(
        ModerateContentRequest $request,
        ModerateContentAction $action
    ): RedirectResponse {
        $content = $request->getContent();

        $this->authorize('moderateContent', $content);

        $action->reject($content, $request->input('notes'));

        return redirect()
            ->back()
            ->with('success', 'Contenido rechazado.');
    }

    /**
     * Archiva contenido.
     */
    public function archive(
        ModerateContentRequest $request,
        ModerateContentAction $action
    ): RedirectResponse {
        $content = $request->getContent();

        $this->authorize('moderateContent', $content);

        $action->archive($content);

        return redirect()
            ->back()
            ->with('success', 'Contenido archivado.');
    }

    /**
     * Envía contenido a revisión.
     */
    public function submitForReview(
        ModerateContentRequest $request,
        ModerateContentAction $action
    ): RedirectResponse {
        $content = $request->getContent();

        $action->submitForReview($content);

        return redirect()
            ->back()
            ->with('success', 'Contenido enviado a revisión.');
    }
}
