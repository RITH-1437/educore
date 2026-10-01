<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Services\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Room management screen (module 9.10).
 */
class RoomController extends Controller
{
    public function __construct(
        private readonly TimetableService $timetable,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Room::class);

        $search = trim((string) $request->query('search', ''));

        return Inertia::render('Rooms/Index', [
            'rooms' => RoomResource::collection(
                Room::query()
                    ->withCount('scheduleEntries')
                    ->when($search !== '', fn ($q) => $q->where(fn ($r) => $r->where('code', 'ilike', "%{$search}%")->orWhere('name', 'ilike', "%{$search}%")->orWhere('building', 'ilike', "%{$search}%")))
                    ->orderBy('code')
                    ->paginate(20)
                    ->withQueryString()
            ),
            'types' => Room::TYPES,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(RoomRequest $request): RedirectResponse
    {
        $this->authorize('create', Room::class);

        $this->timetable->createRoom($request->validated());

        return back()->with('success', 'Room created.');
    }

    public function update(RoomRequest $request, Room $room): RedirectResponse
    {
        $this->authorize('update', $room);

        $this->timetable->updateRoom($room, $request->validated());

        return back()->with('success', 'Room updated.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        $this->authorize('delete', $room);

        $this->timetable->deleteRoom($room);

        return back()->with('success', 'Room deleted.');
    }
}
