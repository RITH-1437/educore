<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\CatalogController as ApiCatalogController;
use App\Models\Course;
use App\Services\CatalogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The public course catalog pages (report 53): open to everyone, signed in or
 * not, like a Moodle front page's categories, course search and courses.
 */
class CatalogController extends Controller
{
    public function __construct(
        private readonly CatalogService $catalog,
    ) {}

    public function index(Request $request): Response
    {
        $filters = ApiCatalogController::filters($request);
        $page = $this->catalog->courses($filters);

        return Inertia::render('Catalog/Index', [
            'courses' => [
                'data' => $page->getCollection()->map(fn (Course $course) => $this->catalog->present($course))->values(),
                'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total(), 'links' => $page->linkCollection()],
            ],
            'departments' => $this->catalog->departments()->map(fn ($d) => ['id' => $d->id, 'code' => $d->code, 'name' => $d->name, 'courses_count' => $d->courses_count])->values(),
            'semester' => $this->catalog->presentSemester($this->catalog->semester()),
            'filters' => ['search' => $filters['search'] ?? '', 'department_id' => $filters['department_id'] ?? null, 'offered' => (bool) ($filters['offered'] ?? false)],
        ]);
    }

    public function show(int $course): Response
    {
        $found = $this->catalog->find($course);
        abort_if($found === null, 404);

        return Inertia::render('Catalog/Show', [
            'course' => $this->catalog->present($found, detail: true),
            'semester' => $this->catalog->presentSemester($this->catalog->semester()),
        ]);
    }
}
