<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AnnouncementController as ApiAnnouncementController;
use App\Http\Requests\AnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Models\StoredFile;
use App\Services\AnnouncementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Announcement screens (module 9.19): every user's feed with read state, and
 * the compose / manage page for managers, Department Admins and lecturers
 * (scheduling and read counts, report 51).
 */
class AnnouncementsController extends Controller
{
    public function __construct(
        private readonly AnnouncementService $announcements,
    ) {}

    public function feed(Request $request): Response
    {
        return Inertia::render('Announcements/Feed', [
            'announcements' => AnnouncementResource::collection($this->page($this->announcements->feedFor($request->user()))),
            'unreadCount' => $this->announcements->unreadCount($request->user()),
            'canManage' => $request->user()->can('manageAny', Announcement::class),
        ]);
    }

    public function manage(Request $request): Response
    {
        $this->authorize('manageAny', Announcement::class);
        $state = ApiAnnouncementController::stateFilter($request);

        $page = $this->page($this->announcements->managedBy($request->user(), $state));
        $this->announcements->attachReach($page->getCollection());

        return Inertia::render('Announcements/Manage', [
            'announcements' => AnnouncementResource::collection($page),
            'filters' => ['publish_state' => $state],
            'targets' => $this->announcements->targetOptions($request->user()),
            'canTargetGroups' => $this->announcements->manages($request->user()),
            'types' => Announcement::TYPES,
        ]);
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $this->authorize('create', Announcement::class);

        $publish = $request->boolean('publish');
        $announcement = $this->announcements->create($request->user(), $request->validated(), false);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->announcements->attachFile($announcement, $file, $request->user());
            }
        }

        if ($publish) {
            $this->announcements->publish($announcement, $request->user());
        } elseif ($request->filled('scheduled_for')) {
            $this->announcements->schedule($announcement, $request->user(), (string) $request->validated('scheduled_for'));

            return back()->with('success', 'Announcement scheduled.');
        }

        return back()->with('success', $publish ? 'Announcement published.' : 'Draft saved.');
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $this->announcements->update($announcement, $request->user(), $request->validated());

        if ($request->has('remove_attachment_ids')) {
            foreach ((array) $request->input('remove_attachment_ids') as $id) {
                $attachment = $announcement->attachments()->find($id);
                if ($attachment) {
                    $this->announcements->deleteAttachment($announcement, $attachment);
                }
            }
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $this->announcements->attachFile($announcement, $file, $request->user());
            }
        }

        return back()->with('success', 'Draft updated.');
    }

    public function downloadAttachment(Announcement $announcement, StoredFile $file): StreamedResponse
    {
        $this->authorize('view', $announcement);

        return $this->announcements->downloadAttachment($announcement, $file);
    }

    public function publish(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $this->announcements->publish($announcement, $request->user());

        return back()->with('success', 'Announcement published.');
    }

    public function schedule(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);
        $at = $request->validate(['scheduled_for' => ['required', 'date']])['scheduled_for'];

        $this->announcements->schedule($announcement, $request->user(), (string) $at);

        return back()->with('success', 'Announcement scheduled.');
    }

    public function unschedule(Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $this->announcements->unschedule($announcement);

        return back()->with('success', 'Back to drafts.');
    }

    public function read(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('read', $announcement);

        $this->announcements->markRead($announcement, $request->user());

        return back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $marked = $this->announcements->markAllRead($request->user());

        return back()->with('success', $marked === 1 ? '1 announcement marked read.' : "{$marked} announcements marked read.");
    }

    public function archive(Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $this->announcements->archive($announcement);

        return back()->with('success', 'Announcement archived.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $this->announcements->delete($announcement);

        return back()->with('success', 'Draft deleted.');
    }

    /**
     * @param  Builder<Announcement>  $query
     */
    private function page(Builder $query): LengthAwarePaginator
    {
        $page = $query->paginate(15)->withQueryString();
        $this->announcements->preloadTargets($page->getCollection());

        return $page;
    }
}
