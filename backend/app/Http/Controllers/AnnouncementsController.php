<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AnnouncementController as ApiAnnouncementController;
use App\Http\Requests\AnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Announcement screens (module 9.19): every user's feed, and the compose /
 * manage page for managers and lecturers.
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
            'canManage' => $request->user()->can('manageAny', Announcement::class),
        ]);
    }

    public function manage(Request $request): Response
    {
        $this->authorize('manageAny', Announcement::class);
        $state = ApiAnnouncementController::stateFilter($request);

        return Inertia::render('Announcements/Manage', [
            'announcements' => AnnouncementResource::collection($this->page($this->announcements->managedBy($request->user(), $state))),
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
        $this->announcements->create($request->user(), $request->validated(), $publish);

        return back()->with('success', $publish ? 'Announcement published.' : 'Draft saved.');
    }

    public function update(AnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $this->announcements->update($announcement, $request->user(), $request->validated());

        return back()->with('success', 'Draft updated.');
    }

    public function publish(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->authorize('update', $announcement);

        $this->announcements->publish($announcement, $request->user());

        return back()->with('success', 'Announcement published.');
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
